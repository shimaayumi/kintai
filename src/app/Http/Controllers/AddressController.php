<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddressRequest;
use App\Models\Item;
use App\Models\Purchase;

use App\Models\User;

class AddressController extends Controller
{

    public function edit($item_id)
    {
        $item = Item::find($item_id);
        if (!$item) {
            return redirect()->route('index')->with('error', '商品が見つかりません');
        }

        $user = Auth::user();

        // 最新のpurchaseを取得
        $purchase = Purchase::where('user_id', $user->id)
            ->where('item_id', $item_id)
            ->first();

        // 購入情報があればそれを使用、なければユーザーの住所を使用
        $postal_code = $purchase->shipping_postal_code ?? $user->address->postal_code ?? '';
        $address_detail = $purchase->shipping_address ?? $user->address->address ?? '';
        $building = $purchase->shipping_building ?? $user->address->building ?? '';

        return view('address_edit', compact('item', 'user', 'postal_code', 'address_detail', 'building', 'purchase'));
    }

    public function update(AddressRequest $request, $item_id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'ログインしてください');
        }

        $user = Auth::user();

        $address = $user->address;

        if (!$address) {
            return redirect()->back()->with('error', '住所が登録されていません');
        }

        $item = Item::findOrFail($item_id); // ←ここで商品取得！

        $purchase = Purchase::firstOrNew([
            'user_id' => $user->id,
            'item_id' => $item_id,
        ]);

    

        $purchase->address_id = $address->id;
        $purchase->shipping_postal_code = $request->input('postal_code');
        $purchase->shipping_address = $request->input('address');
        $purchase->shipping_building = $request->input('building');
        $purchase->price = $item->price; // ←ここ！！

        $purchase->save();

        return redirect()->route('purchase.show', ['item_id' => $item_id])->with('success', '送り先住所を更新しました');
    }
}
