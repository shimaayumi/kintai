<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',    // ユーザーID
        'item_id',    // 商品ID
        'address_id', // 住所ID
        'quantity',   // 購入数
        'total_price' // 合計金額
    ];

    /**
     * 購入者（ユーザー）
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 購入された商品
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * 購入時の住所
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function failed()
    {
        return view('purchase.failed'); // エラー用のビューを表示
    }
}