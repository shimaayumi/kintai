<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class TestPurchaseController extends Controller
{
    public function __invoke(Request $request, Item $item)
    {
        $item->update([
            'sold_flag' => 1,
        ]);

        return response()->json([
            'message' => 'Purchased',
        ]);
    }
}
