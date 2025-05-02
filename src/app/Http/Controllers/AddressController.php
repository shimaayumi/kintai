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
        $user = Auth::user();
        $item = Item::findOrFail($item_id);

        return view('address_edit', [
            'item' => $item,
            'postal_code' => $user->address->postal_code ?? '',
            'address_detail' => $user->address->address ?? '',
            'building' => $user->address->building ?? '',
        ]);
    }



    public function update(AddressRequest $request, $item_id)
    {
        $validated = $request->validate([
            'postal_code' => 'required|string|max:10',
            'address' => 'required|string|max:255',
            'building' => 'nullable|string|max:255',
        ]);

        // セッションに一時住所を保存
        session()->put('temporary_address', $validated);

        return redirect()->route('purchase.show', ['item_id' => $item_id])
            ->with('success', '配送先を一時的に更新しました');
    }
}