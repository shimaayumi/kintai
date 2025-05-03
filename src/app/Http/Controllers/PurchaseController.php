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
            $addressId = $user->profile->address->id;
            $temporaryAddress = session('temporary_address');
            // ✅ まず Purchase を作成（ステータスは pending）
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

            $paymentMethodMap = [
                'credit_card' => 'card',
                'convenience_store' => 'konbini',
            ];

            $stripePaymentMethod = $paymentMethodMap[$validated['payment_method']] ?? 'card';

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => [$stripePaymentMethod],
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
               
                // ← この中に入れること
                'payment_intent_data' => [
                    'metadata' => [
                        'purchase_id' => $purchase->id,
                    ],
                ],
                // ← checkout.session.completed 用
                'metadata' => [
                    'purchase_id' => $purchase->id,
                ],
            ]);


            // ✅ セッションIDを保存（任意）
            $purchase->stripe_session_id = $session->id;
            $purchase->save();

            DB::commit();

            return response()->json(['url' => $session->url]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'エラーが発生しました: ' . $e->getMessage()], 500);
        }
    }



    // 購入確定後の処理
    public function confirmPurchase(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $user = Auth::user();

        // プロフィールが作成されていない場合、プロフィール作成ページへ遷移
        if (!$user->profile) {
            return redirect()->route('edit')->with('error', 'プロフィールを作成してください。');
        }

        $shippingAddress = $user->profile->address;
        $paymentMethod = $request->input('payment_method');


        if (!$paymentMethod) {
            return redirect()->route('purchase.failed')->with('error', '支払い方法が選択されていません');
        }

        // Stripeクライアントを初期化
        $stripe = new StripeClient(env('STRIPE_SECRET'));

        try {
            // PaymentIntentを作成
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $item->price,
                'currency' => 'jpy',
                'payment_method' => $paymentMethod,
                'confirmation_method' => 'manual',
                'confirm' => true,
            ]);

            // 支払いが成功した場合の処理
            if ($paymentIntent->status === 'succeeded') {
                $user->purchase()->attach($item->id);  // 購入アイテムをユーザーに関連付け



                return redirect()->route('index');
            } else {
                return redirect()->route('purchase.failed')->with('error', '支払いが失敗しました。');
            }
        } catch (\Exception $e) {
            return redirect()->route('purchase.failed')->with('error', '支払い処理中にエラーが発生しました: ' . $e->getMessage());
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
 }

