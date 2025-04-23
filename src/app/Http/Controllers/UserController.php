<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Address;
use App\Models\User;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\AddressRequest;
use Illuminate\Support\Facades\Validator;



class UserController extends Controller
{

    // マイページの表示
    public function mypage()
    {
        $page = request()->get('page', 'sell');
        $user = auth()->user();

        $sellItems = $user->items()->with('images')->get();
        $purchasedItems = $user->purchasedItems()->with('images')->get();
        $likedItems = $user->likes()->with('item')->get();

        return view('profile', compact('page', 'user', 'sellItems', 'purchasedItems', 'likedItems'));
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



    public function editProfile(Request $request)
    {
        $user = auth()->user();

        // 1. AddressRequestのバリデーションを実行
        $addressValidator = Validator::make($request->all(), (new AddressRequest)->rules(), (new AddressRequest)->messages());
        if ($addressValidator->fails()) {
            return back()->withErrors($addressValidator)->withInput();
        }

        // 2. ProfileRequestのバリデーションを実行
        $profileValidator = Validator::make($request->all(), (new ProfileRequest)->rules(), (new ProfileRequest)->messages());
        if ($profileValidator->fails()) {
            return back()->withErrors($profileValidator)->withInput();
        }

        // バリデーション通過後、値を取得
        $validated = array_merge($addressValidator->validated(), $profileValidator->validated());

        // プロフィール画像の処理
        if ($request->hasFile('profile_image')) {
            $filename = $request->file('profile_image')->store('public/profiles');
            $profileImage = basename($filename);
        } else {
            $profileImage = $user->profile ? $user->profile->profile_image : 'default.png';
        }

      

        // プロフィール画像の保存または更新
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['profile_image' => $profileImage]
        );

        // 住所情報の保存または更新
        $user->address()->updateOrCreate([], [
            'postal_code' => $validated['postal_code'],
            'address' => $validated['address'],
            'building' => $validated['building'],
        ]);

        return redirect()->route('mypage')->with('success', 'プロフィールが更新されました');
    }




    //プロフィール編集画面を表示
    public function edit()
    {
        $user = auth()->user(); // 現在ログイン中のユーザーを取得
        $address = $user->address;

        return view('profile_edit', compact('user', 'address'));
    }
}