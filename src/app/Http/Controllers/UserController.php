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
    // プロフィール画面
    public function showProfile(Request $request)
    {
        $user = auth()->user()->load('address');

        // ログインしていない場合
        if (!$user) {
            return redirect()->route('login')->with('error', 'ログインが必要です');
        }

        // タブの種類を取得（デフォルトは "sell"）
        $tab = $request->query('tab', 'sell');

        // ログインしているユーザーの出品した商品を取得（画像も一緒に取得）
        $listedItems = Item::with('images')->where('user_id', $user->id)->get();

        // 購入した商品があればそれも取得（画像も一緒に取得）
        $purchasedItems = Item::with('images')->whereHas('purchases', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

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

    public function edit()
    {
        $user = auth()->user(); // ログインしているユーザー情報を取得
        return view('mypage.edit', compact('user'));
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
    }
