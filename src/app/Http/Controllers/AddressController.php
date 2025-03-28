<?php

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function store(Request $request)
    {
        // バリデーションを行う
        $validated = $request->validate([
            'address' => 'required|string',
            'postal_code' => 'required|string', // postal_codeのバリデーションを追加
        ]);

        // アドレスを保存する処理
        Address::create([
            'user_id' => auth()->user()->id, // ログインユーザーID
            'address' => $request->input('address'), // 入力された住所
            'postal_code' => $request->input('postal_code'), // 入力された郵便番号
            'building' => $request->input('building'), // 入力された建物名（オプション）
        ]);

        // リダイレクト
        return redirect()->route('profile.show')->with('status', 'アドレスが保存されました');
    }
}
