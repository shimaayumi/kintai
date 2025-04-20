<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\AddressRequest;
use Illuminate\Support\Facades\Validator;



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




    //プロフィール編集画面を表示
    public function edit()
    {
        $user = auth()->user(); // 現在ログイン中のユーザーを取得
        $address = $user->address;

        return view('profile_edit', compact('user', 'address'));
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

        // 名前の更新
        $user->name = $validated['name'];
        $user->save();

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

        return redirect()->route('edit.Profile')->with('success', 'プロフィールが更新されました');
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


    public function store(Request $request)
    {
        $user = Auth::user();

        // 1. AddressRequest を手動バリデーション
        $addressValidator = app(AddressRequest::class);
        $addressData = $this->validate($request, $addressValidator->rules(), $addressValidator->messages());

        // 2. ProfileRequest を手動バリデーション
        $profileValidator = app(ProfileRequest::class);
        $profileData = $this->validate($request, $profileValidator->rules(), $profileValidator->messages());

        
        // プロフィール画像の保存
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('public/profiles');
            $filename = basename($path);

            // プロフィール画像を保存
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                ['profile_image' => $filename]
            );
        }

        // ユーザー名の更新（必要なら）
        if ($request->filled('name')) {
            $user->name = $request->input('name');
            $user->save();
        }

        // 住所の保存
        $user->address()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'postal_code' => $request->input('postal_code'),
                'address' => $request->input('address'),
                'building' => $request->input('building'),
            ]
        );

        return redirect()->route('mypage')->with('success', 'プロフィール情報を保存しました。');
    }
}