<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;
use App\Models\Address;
use App\Models\User;

class PurchaseController extends Controller
{
    // ログインしていなければログイン画面へ遷移
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $items = Item::all(); // 変数名を複数形に修正
        return view('purchase', compact('items'));
    }

    public function show($id)
    {
        // 商品を取得
        $item = Item::find($id);

        if (!$item) {
            return redirect()->route('items.index')->with('error', '商品が見つかりません');
        }

        // 同じカテゴリの関連商品を取得（現在の商品を除外）
        $relatedItems = Item::where('category_id', $item->category_id)
            ->where('id', '!=', $id)
            ->get();

        // 現在のログインユーザーを取得（ログインしていない場合は null）
        $user = Auth::user();
        $user->load('address'); // address リレーションもロード

        return view('purchase', compact('item', 'relatedItems', 'user'));
    }

    // 購入確認
    public function confirmPurchase(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $user = Auth::user();

        if (!$user->profile) {
            return redirect()->route('user.editProfile')->with('error', 'プロフィールを作成してください。');
        }

        $shippingAddress = $user->profile->address;
        $paymentMethod = $request->input('payment_method');
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
    public function changeAddress($id)
    {
        // ユーザーがログインしているか確認
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        $user = auth()->user();
        $item = Item::findOrFail($id);

        // 住所情報が null の場合、デフォルト値をセット
        $userAddress = $user->address ?? null;

        return view('address_edit', compact('item', 'userAddress'));
    }

    public function updateAddress(Request $request, $id)
    {
        // ユーザーがログインしているかチェック
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        // バリデーション
        $request->validate([
            'postal_code' => 'required|string',
            'address' => 'required|string',
            'building' => 'nullable|string',
        ]);

        // ユーザー情報取得
        $user = auth()->user();
        $item = Item::findOrFail($id);

        // 住所情報の更新 or 新規作成
        if ($user->address) {
            // 既存の住所がある場合は更新
            $user->address()->update([
                'postal_code' => $request->postal_code,
                'address' => $request->address,
                'building' => $request->building,
            ]);
        } else {
            // 住所が存在しない場合は新規作成
            $user->address()->create([
                'postal_code' => $request->postal_code,
                'address' => $request->address,
                'building' => $request->building,
            ]);
        }

        return redirect()->route('purchase.show', ['id' => $id])->with('success', '住所を更新しました！');
    }

    // 購入ページの表示
    public function purchase($id)
    {
        $item = Item::findOrFail($id);
        return view('purchase', compact('item'));
    }

    public function showPurchaseHistory($id)
    {
        $user = auth()->user();

        // ユーザーが認証されているか、要求されたIDと一致しているか確認
        if (!$user || $user->id != $id) {
            return redirect()->route('login')->with('error', 'このページを表示する権限がありません');
        }

        // ユーザーの購入履歴を取得
        $purchasedItems = $user->purchasedItems;

        // ビューに変数を渡す
        return view('purchase', compact('purchasedItems'));
    }
}
