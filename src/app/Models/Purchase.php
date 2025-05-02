<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    
        
    protected $fillable = [
        'user_id',
        'item_id',
        'address_id',
        'payment_method',
        'price',
        'status',
        'stripe_session_id',
        'shipping_postal_code',
        'shipping_address',
        'shipping_building',
    ];

    /**
     * 購入者（ユーザー）
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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


}