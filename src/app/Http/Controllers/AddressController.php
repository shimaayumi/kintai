<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddressRequest;
use App\Models\Item;

use App\Models\User;

class AddressController extends Controller
{

    public function edit($item_id)
    {
        // 商品を取得
        $item = Item::find($item_id);

        if (!$item) {
            return redirect()->route('index')->with('error', '商品が見つかりません');
        }

        // 現在のログインユーザーを取得
        $user = Auth::user();

        // ユーザーに関連する住所情報を取得
        $address = $user->address; // ログインユーザーの住所を取得

        // 住所がない場合、デフォルトの空のアドレスを設定（またはエラーハンドリング）
        if (!$address) {
            return redirect()->route('address.create')->with('error', '住所情報がありません。');
        }

        return view('address_edit', compact('item', 'user', 'address'));
    }



    public function update(AddressRequest $request, $item_id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'ログインしてください');
        }

        $user = Auth::user();

        $user->address()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $request->input('name'),
                'postal_code' => $request->input('postal_code'),
                'address' => $request->input('address'),
                'building' => $request->input('building'),
            ]
        );

        return redirect()->route('purchase.show', ['item_id' => $item_id])->with('success', '住所情報を更新しました');
    }
}
