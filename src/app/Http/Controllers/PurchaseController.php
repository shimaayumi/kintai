<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;

class PurchaseController extends Controller
{
    // 商品購入ページの表示
    public function show($itemId)
    {
        $item = Item::findOrFail($itemId);
        $user = Auth::user(); // ログインユーザーを取得

        return view('purchase', compact('item', 'user'));
    }

    // 購入確認
    public function confirmPurchase(Request $request, $itemId)
    {
        // 商品とユーザー情報を取得
        $item = Item::findOrFail($itemId);
        $user = Auth::user();
        $user->purchasedItems()->attach($item->id);

        // ユーザーがプロフィールを持っていない場合
        if (!$user->profile) {
            return redirect()->route('user.editProfile')->with('error', 'プロフィールを作成してください。');
        }

        // 住所を取得
        $shippingAddress = $user->profile->address;

        // 支払い方法の取得
        $paymentMethod = $request->input('payment_method');

        // Stripe APIの初期化
        $stripe = new StripeClient('your-stripe-secret-key');

        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $item->price * 100,
                'currency' => 'jpy',
                'payment_method' => $paymentMethod,
                'confirmation_method' => 'manual',
                'confirm' => true,
            ]);

            if ($paymentIntent->status === 'succeeded') {
                $item->status = 'sold';
                $item->save();

                $user->purchasedItems()->attach($item->id);

                return redirect()->route('purchase.complete');
            } else {
                return redirect()->route('purchase.failed')->with('error', '支払いが失敗しました。');
            }
        } catch (\Exception $e) {
            return redirect()->route('purchase.failed')->with('error', '支払い処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    // 住所変更画面の表示
    public function changeAddress($item_id)
    {
        $item = Item::findOrFail($item_id);
        $userAddress = auth()->user()->address;
        return view('address_edit', compact('item', 'userAddress'));
    }

    // 住所更新処理
    public function updateAddress(Request $request, $item_id)
    {
        $request->validate([
            'postal_code' => 'required|string',
            'address' => 'required|string',
            'building' => 'nullable|string',
        ]);

        $user = auth()->user();
        $user->address->update([
            'postal_code' => $request->postal_code,
            'address' => $request->address,
            'building' => $request->building,
        ]);

        return redirect()->route('purchase.show', ['item_id' => $item_id])->with('success', '住所を更新しました！');
    }
}
