<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Item;

class ProfileController extends Controller
{
    
    // プロフィール編集画面: /mypage/profile
    public function editProfile()
    {
        $user = auth()->user();
        $address = $user->address ?? null;

        return view('profile_edit', compact('user', 'address'));
    }

   

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'introduction' => 'nullable|string|max:1000',
            'postal_code' => 'nullable|string|max:10',
            'prefecture' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:100',
        ]);

        $user = auth()->user();

        // ユーザー名の更新
        $user->name = $request->name;
        $user->save();

        // プロフィールがある場合は更新、ない場合は作成
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['introduction' => $request->input('introduction')]
        );

        // 住所がある場合は更新、ない場合は作成
        $user->address()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'postal_code' => $request->postal_code,
                'prefecture' => $request->prefecture,
                'city' => $request->city,
                'street' => $request->street,
            ]
        );

        return redirect()->route('mypage')->with('success', 'プロフィールを更新しました！');
    }



    // マイページ（プロフィール画面）: /mypage
    public function showMypage(Request $request)
    {
        $user = auth()->user()->load(['address', 'profile']);

        if (!$user) {
            return redirect()->route('login')->with('error', 'ログインが必要です');
        }

        $page = $request->query('page', 'sell'); // デフォルトで'sell'

        // 出品商品（画像込み）
        $listedItems = Item::with('images')->where('user_id', $user->id)->get();

        // 購入商品（画像込み）
        $purchasedItems = Item::with('images')->whereHas('purchases', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        // タブに応じたアイテムリストを選択
        $items = ($page === 'sell') ? $listedItems : $purchasedItems;

        return view('profile', compact('user', 'items', 'page'));
    }
}