<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddressRequest;

use App\Models\User;

class AddressController extends Controller
{
    public function store(AddressRequest $request)
    {
        // ユーザーがログインしているか確認
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'ログインしてください');
        }

      

        // 住所を保存（すでにある場合は更新）
        $user = Auth::user();
        
        if ($user->address) {
            $user->address->update($validated);
        } else {
            $user->address()->create($validated);
        }

        return redirect()->route('profile.show')->with('status', 'アドレスが保存されました');
    }


    public function show($id)
    {
        // 商品を取得
        $item = Item::find($id);

        if (!$item) {
            return redirect()->route('items.index')->with('error', '商品が見つかりません');
        }

        // 現在のログインユーザーを取得
        $user = Auth::user();
        $user->load('address'); // 最新の住所を取得

        return view('purchase', compact('item', 'user'));
    }   
}
