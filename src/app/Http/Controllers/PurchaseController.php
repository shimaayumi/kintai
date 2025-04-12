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
use Stripe\Checkout\Session;

class PurchaseController extends Controller
{
    public function purchaseConfirm(Request $request, $itemId)
    {
        // 商品情報を取得
        $item = Item::findOrFail($itemId);

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
                'unit_amount' => $item->price * 100,  // 商品価格（100倍して小数点を消す）
                'currency' => 'jpy',                  // 通貨（日本円）
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
                'amount' => $item->price * 100,  // 価格を100倍して小数点を削除
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
        // エラーメッセージがある場合はそれを表示
        $error = $request->session()->get('error', '購入処理中にエラーが発生しました。');
        return view('purchase', ['error' => $error]);
    }

    // 住所変更ページの表示
    public function changeAddress($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        $user = auth()->user();
        $item = Item::findOrFail($id);  // 商品IDでアイテムを取得
        $userAddress = $user->address ?? null;

        return view('address_edit', compact('item', 'userAddress'));
    }

    // 住所更新処理
    public function updateAddress(Request $request, $id)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        // 住所更新のためのバリデーション
        $request->validate([
            'postal_code' => 'required|string',
            'address' => 'required|string',
            'building' => 'nullable|string',
        ]);

        $user = auth()->user();
        $item = Item::findOrFail($id);

        // 住所がすでに存在する場合は更新、存在しない場合は新規作成
        if ($user->address) {
            $user->address()->update([
                'postal_code' => $request->postal_code,
                'address' => $request->address,
               
                'building' => $request->building,
            ]);
        } else {
            $user->address()->create([
                'postal_code' => $request->postal_code,
                'address' => $request->address,
                'building' => $request->building,
            ]);
        }

        return redirect()->route('purchase.show', ['id' => $id])->with('success', '住所を更新しました！');
    }

    // 購入ページの表示
    public function purchase($itemId)
    {
        $item = Item::findOrFail($itemId);
        $user = Auth::user(); // ログインユーザー情報を取得
        return view('purchase', compact('item', 'user')); // item と user をビューに渡す
    }

    

    // プロフィールページの表示
    public function showProfile()
    {
        $user = Auth::user()->load('address');
        return view('profile.show', compact('user'));
    }

    

    public function cancel()
    {
        return view('purchase.cancel');
    }

    // 商品購入
    public function store(Request $request, $itemId)
    {
        // $request を利用して支払い方法を取得
        $paymentMethod = $request->input('payment_method');
        
        // 支払い方法が正しいか再確認
        if ($paymentMethod !== 'convenience_store' && $paymentMethod !== 'credit_card') {
           
            return redirect()->route('items.index')->with('error', '無効な支払い方法が選択されました。');
        }

        // 商品の情報を取得
        $item = Item::findOrFail($itemId);

        // すでに売り切れの場合、購入できないようにする
        if ($item->sold_flag) {
            return redirect()->route('items.index')->with('error', 'この商品はすでに売り切れています。');
        }

        // 購入処理を保存
        $purchase = new Purchase();
        $purchase->user_id = Auth::id();
        $purchase->item_id = $item->id;
        $purchase->price = $item->price;
        $purchase->address_id = Auth::user()->address->id;
        $purchase->payment_method = $paymentMethod; // 正しく取得した支払い方法を保存

        $purchase->save();

        // 商品の状態を「sold」に更新
        $item->sold_flag = 1;
        $item->save();

        // 購入完了後、プロフィールページにリダイレクト
        return redirect()->route('profile.purchases')->with('success', '購入が完了しました。');
    }

    // 購入履歴ページの表示
    public function showPurchaseHistory($id)
    {
        $user = auth()->user();

        if (!$user || $user->id != $id) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        // 購入履歴を取得
        $purchases = Purchase::where('user_id', $user->id)->with('item')->get();

        return view('purchase_history', compact('purchases'));
    }

    public function success()
    {
        return view('purchase_success'); // 成功時に表示するビュー
    }

    public function handleWebhook(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                env('STRIPE_WEBHOOK_SECRET')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook error: ' . $e->getMessage()], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            // 購入情報を更新
            $purchase = Purchase::where('stripe_session_id', $session->id)->first();
            if ($purchase) {
                $purchase->status = 'completed';
                $purchase->save();
            }
        }

        return response()->json(['status' => 'success']);
    }

 
}
