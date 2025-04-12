<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Address;
use App\Models\User;

class UserController extends Controller
{
   

    // 出品した商品一覧を表示するメソッド
  
    public function listingHistory()
    {
        // ログインしているユーザーが出品した商品を取得（画像も一緒に取得）
        $items = Item::with('images')->where('user_id', Auth::id())->get();

        // ビューに出品商品を渡す
        return view('mypage', ['items' => $items]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        // バリデーション
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'postal_code' => ['required', 'string', 'regex:/^\d{3}-\d{4}$/'], // 郵便番号の形式を追加
            'address' => 'required|string|max:255',
            'building' => 'nullable|string|max:255',
        ]);

        // 画像アップロード処理
        if ($request->hasFile('profile_image')) {
            $filename = $request->file('profile_image')->store('public/profiles');
            $validated['profile_image'] = basename($filename);
        } else {
            $validated['profile_image'] = $user->profile ? $user->profile->profile_image : 'default.png';
        }

        // プロフィール更新
        $user->name = $validated['name'];
        $user->save();

        // ユーザープロフィールを更新または作成
        $user->profile()->updateOrCreate([], ['profile_image' => $validated['profile_image']]);

        // 住所情報を更新または作成
        $user->address()->updateOrCreate([], [
            'postal_code' => $validated['postal_code'],
            'address' => $validated['address'],
            'building' => $validated['building'],
        ]);

        return redirect()->route('mypage.edit')->with('success', 'プロフィールが更新されました');
    }

    public function showMyList()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // ユーザーのお気に入り商品を取得（item と item_images を同時にロード）
        $favoriteItems = $user->likes()->with('item.images')->get()->pluck('item');

        return view('index', compact('favoriteItems'));
    }
    public function mypage()
    {
        $tab = request()->get('tab', 'sell');
        $user = auth()->user();

        $purchasedItems = $user->purchasedItems()->with('images')->get(); // ここ修正
        $likedItems = $user->likes()->with('item')->get();
        $sellItems = $user->items()->with('images')->get();

        return view('profile', compact('tab', 'user', 'purchasedItems', 'likedItems', 'sellItems'));
    }
    public function editProfile()
    {
        $user = auth()->user();
        $address = $user->address;  // アドレス情報を取得（適宜修正）

        return view('profile_edit', compact('user', 'address'));  // ビューに渡す
    }

    
    }
