<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;
use App\Models\Address;
use App\Models\User;
use App\Models\Purchase;
use Stripe\Stripe;
use App\Http\Requests\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\CheckPaymentStatus;




class PurchaseController extends Controller
{
    public function checkout(PurchaseRequest $request, $item_id)
    {
        Log::info('リクエストデータ: ', $request->all());

        $validated = $request->validated();
        $item = Item::findOrFail($item_id);
        $user = Auth::user();

        Stripe::setApiKey(env('STRIPE_SECRET'));

        DB::beginTransaction();

        try {
            $existing = Purchase::where('user_id', $user->id)
                ->where('item_id', $item->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'error' => 'すでにこの商品は購入手続き中です。',
                ], 400);
            }
            $addressId = $user->profile->address->id;
            $temporaryAddress = session('temporary_address');

            $purchase = Purchase::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'address_id' => $addressId,
                'payment_method' => $validated['payment_method'],
                'price' => $item->price,
                'status' => 'pending',
                'shipping_postal_code' => $temporaryAddress['postal_code'] ?? $user->profile->address->postal_code,
                'shipping_address' => $temporaryAddress['address'] ?? $user->profile->address->address,
                'shipping_building' => $temporaryAddress['building'] ?? $user->profile->address->building,
            ]);

            if ($validated['payment_method'] === 'credit_card') {
                // クレジットカード決済（Stripe Checkout使用）
                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'jpy',
                            'product_data' => [
                                'name' => $item->item_name,
                                'description' => $item->description,
                            ],
                            'unit_amount' => $item->price,
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => route('purchase.success') . '?session_id={CHECKOUT_SESSION_ID}&item_id=' . $item->id,
                    'payment_intent_data' => [
                        'metadata' => [
                            'purchase_id' => $purchase->id,
                        ],
                    ],
                    'metadata' => [
                        'purchase_id' => $purchase->id,
                    ],
                ]);

                $purchase->stripe_session_id = $session->id;
                $purchase->save();

                DB::commit();

                

                return response()->json(['url' => $session->url]);
            } elseif ($validated['payment_method'] === 'convenience_store') {
                // コンビニ決済（PaymentIntentを直接作成）
                $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
                $paymentIntent = $stripe->paymentIntents->create([
                    'amount' => $item->price,
                    'currency' => 'jpy',
                    'payment_method_types' => ['konbini'],
                    'metadata' => [
                        'purchase_id' => $purchase->id,
                    ],
                ]);

                $purchase->payment_intent_id = $paymentIntent->id;
                $purchase->save();

                DB::commit();

                return response()->json([
                    'payment_intent' => $paymentIntent,
                    'payment_method' => 'convenience_store', // ← 追加
                    'payment_intent_client_secret' => $paymentIntent->client_secret, // ← Stripe用に必要
                    'message' => 'コンビニ決済用の支払い情報を作成しました。',
                ]);
            } else {
                throw new \Exception('未対応の決済方法です。');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('決済エラー: ' . $e->getMessage());
            return response()->json(['error' => 'エラーが発生しました: ' . $e->getMessage()], 500);
        }
    }



    // 購入確定後の処理
    public function confirmPurchase(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $user = Auth::user();

        if (!$user->profile) {
            return redirect()->route('edit')->with('error', 'プロフィールを作成してください。');
        }

        // 購入情報取得（pending のみ対象）
        $purchase = Purchase::where('item_id', $item->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$purchase) {
            return redirect()->route('purchase.failed')->with('error', '購入情報が見つかりませんでした。');
        }
        // ここで $purchase が null でないことを確認した上でジョブをディスパッチ
        dispatch(new \App\Jobs\CheckPaymentStatus($purchase));
        $paymentMethod = $purchase->payment_method;

        if ($paymentMethod === 'convenience_store') {
            $paymentIntentId = $request->input('payment_intent_id');
            if (!$paymentIntentId) {
                return redirect()->route('purchase.failed')->with('error', '支払い情報が正しくありません。');
            }

            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            try {
                // PaymentIntentを取得
                $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);
                Log::info('Stripe payment intent response: ', (array) $paymentIntent);
            } catch (\Exception $e) {
                Log::error('Stripeエラー: ' . $e->getMessage());
                return redirect()->route('purchase.failed')->with('error', '支払い情報の取得に失敗しました。');
            }

            Log::info('支払いステータス: ' . $paymentIntent->status);

            if ($paymentIntent->status === 'succeeded') {
                DB::beginTransaction();
                try {
                    // 購入ステータスを「確定」に変更
                    $purchase->status = 'confirmed';
                    $purchase->save();

                    // 商品を「売却済み」に変更
                    $item->sold_flag = 1;
                    $item->save();

                    DB::commit();

                    Log::info('購入情報（更新後）: ', $purchase->toArray());
                    Log::info('商品情報（更新後）: ', $item->toArray());

                    return redirect()->route('index')->with('message', '購入が完了しました。ありがとうございました！');
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('購入確定エラー: ' . $e->getMessage());
                    return redirect()->route('purchase.failed')->with('error', '購入確定中にエラーが発生しました。');
                }
            } else {
                // 支払い確認を後で再チェック
                CheckPaymentStatus::dispatch($purchase);

                return redirect()->route('index')->with('message', 'お支払い情報を確認中です。しばらくお待ちください。');
            }
        }

        return redirect()->route('purchase.failed')->with('error', '不正な支払い方法です。');
    }




    public function success(Request $request)
    {

        Stripe::setApiKey(env('STRIPE_SECRET'));
        $session_id = $request->input('session_id');
        $item_id = $request->input('item_id');

      
            $session = \Stripe\Checkout\Session::retrieve($session_id);

            if ($session->payment_status === 'paid') {
                $purchase = Purchase::where('stripe_session_id', $session_id)
                    ->where('item_id', $item_id)
                    ->first();
                 session()->forget('temporary_address');
                if ($purchase) {
                    DB::beginTransaction();

                    // ステータスを確定に更新
                    $purchase->update(['status' => 'confirmed']);

                    // 該当商品のソールドフラグを1に更新
                    $item = Item::find($item_id);
                    if ($item) {
                        $item->sold_flag = 1;
                        $item->save();
                    }

                    DB::commit();

                return redirect()->route('index')->with('message', '購入が完了しました。ありがとうございました！');
                }
          
        } 
    }



    // 購入失敗時の処理
    public function failed(Request $request)
    {

        $error = $request->session()->get('error', '購入処理中にエラーが発生しました。');
        return view('purchase', ['error' => $error]);
    }




    public function show($item_id)
    {
        $user = auth()->user();
        $item = Item::findOrFail($item_id);

        $purchase = Purchase::where('user_id', $user->id)
            ->where('item_id', $item_id)
            ->first();

        return view('purchase', compact('item', 'user', 'purchase'));
    }


    public function cancel()
    {
        return view('index');
    }


    
 }
