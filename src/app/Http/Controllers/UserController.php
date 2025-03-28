<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // プロフィール画面
    public function showProfile(Request $request)
    {
        $user = auth()->user();

        // ログインしていない場合
        if (!$user) {
            return redirect()->route('login')->with('error', 'ログインが必要です');
        }

        // タブの種類を取得（デフォルトは "sell"）
        $tab = $request->query('tab', 'sell');

        // ログインしているユーザーの出品した商品を取得
        $listedItems = $user->items;

        // 購入した商品があればそれも取得
        $purchasedItems = $user->purchasedItems;

        // ビューを返す
        return view('profile', compact('user', 'listedItems', 'purchasedItems', 'tab'));
    }

    // プロフィール編集画面
    public function editProfile()
    {
        $user = auth()->user();
        $address = $user->address ? $user->address : null;  // 住所情報の取得
        

        return view('profile_edit', compact('user', 'address'));
    }

    // 購入履歴表示
    public function purchaseHistory()
    {
        $purchasedItems = Item::where('buyer_id', auth()->id())->get();
        return view('user.purchaseHistory', compact('purchasedItems'));
    }

    // 出品した商品一覧を表示するメソッド
    public function listingHistory()
    {
        // ログインしているユーザーが出品した商品を取得
        $items = Item::where('user_id', Auth::id())->get();

        // ビューに出品商品を渡す
        return view('mypage', ['items' => $items]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        // バリデーション
        $request->validate([
            'name' => 'required|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'required|string|max:255',
        ]);

        // プロフィール画像の更新処理
        if ($request->hasFile('profile_image')) {
            // 既存のプロフィール画像を削除
            if ($user->profile && $user->profile->profile_image) {
                Storage::delete('public/profiles/' . $user->profile->profile_image);
            }

            // ファイルを保存する際に一意な名前を生成（タイムスタンプを使用）
            $file = $request->file('profile_image');
            $filename = time() . '.' . $file->getClientOriginalExtension();  // タイムスタンプを使って一意なファイル名を作成
            $file->storeAs('public/profiles', $filename);  // 公開フォルダに保存

            // 新しい画像のパス
            $profileImage = $filename;
        } else {
            // プロフィール画像がない場合はデフォルト画像を設定
            $profileImage = $user->profile ? $user->profile->profile_image : 'default.png';
        }

        // ユーザーのプロフィールを更新または新たに作成
        if ($user->profile) {
            $user->profile->update([
                'profile_image' => $profileImage,
            ]);
        } else {
            $user->profile()->create([
                'profile_image' => $profileImage,
            ]);
        }

        // ユーザー名の更新
        $user->name = $request->input('name');

        // 住所の更新または新規作成
        if ($user->address) {
            $user->address->update([
                'address' => $request->input('address'),
            ]);
        } else {
            // 住所がない場合、新たに作成
            $user->address()->create([
                'address' => $request->input('address'),
            ]);
        }

        $user->save();

        return redirect()->route('user.profile')->with('success', 'プロフィールが更新されました');
    }
}