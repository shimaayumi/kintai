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

class PurchaseController extends Controller
{
    public function checkout(PurchaseRequest $request, $item_id)
    {


        $validated = $request->validated();
       
        
        // 商品情報を取得
        $item = Item::findOrFail($item_id);

        // 商品名が設定されていない場合はエラーを返す
        if (!$item->item_name) {
            return response()->json(['error' => '商品名が設定されていません。'], 400);
        }

        // ユーザー情報を取得（ログインしているユーザー）
        $user = Auth::user();

        // StripeのAPIキーを設定
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // 商品をStripeで作成
            $stripeProduct = \Stripe\Product::create([
                'name' => $item->item_name,
                'description' => $item->description,
            ]);

            // 価格を作成
            $stripePrice = \Stripe\Price::create([
                'unit_amount' => $item->price,
                'currency' => 'jpy',
                'product' => $stripeProduct->id,      // 商品IDを指定
            ]);

            // Stripe Checkout セッションの作成
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $stripePrice->id,
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('purchase.success'),
                'cancel_url' => route('purchase.cancel'),
                'shipping_address_collection' => [
                    'allowed_countries' => ['JP'],
                ],
            ]);

            // 購入情報をDBに登録する
            $addressId = $user->address->id;
            $address = $user->address;

            Purchase::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'stripe_session_id' => $session->id,
                'amount' => $item->price,
                'status' => 'pending',
                'address_id' => $addressId,
                'price' => $item->price,
                'shipping_postal_code' => $address->postal_code,
                'shipping_address' => $address->address,
                'shipping_building' => $address->building,

            ]);

            // セッションURLをJSONで返す
            return response()->json(['url' => $session->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'エラーが発生しました: ' . $e->getMessage()], 500);
        }
    }


    // 購入確定後の処理（Stripe PaymentIntent）
    public function confirmPurchase(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $user = Auth::user();

        // プロフィールが作成されていない場合、プロフィール作成ページへ遷移
        if (!$user->profile) {
            return redirect()->route('user.editProfile')->with('error', 'プロフィールを作成してください。');
        }

        $shippingAddress = $user->profile->address;
        $paymentMethod = $request->input('payment_method');

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
                return redirect()->route('purchase.complete');
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
        $address = $user->address;
        $item = Item::findOrFail($item_id);

        return view('purchase', compact('item', 'address', 'user'));
    }


    public function cancel()
    {
        return view('purchase.cancel');
    }


    public function success()
    {
        return view('purchase_success'); // 成功時に表示するビュー
    }
}
