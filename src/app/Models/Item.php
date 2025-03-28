<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    // テーブル名を指定 (省略可能: Laravelはモデル名を小文字の複数形と見なすので、'items'は省略可能)
    protected $table = 'items';

    // マスアサインメント可能な属性
    protected $fillable = [
        'user_id',
        'category_id',
        'items_name',
        'brand_name',
        'description',
        'price',
        'status',
        'sold_flag',
    ];

    // ユーザーとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class); // 一つのアイテムは一人のユーザーに属する
    }

   

    // カテゴリとのリレーション
    public function category()
    {
        return $this->belongsTo(Category::class); // 一つのアイテムは一つのカテゴリに属する
    }

    // 画像とのリレーション (ItemImageとの関連付け)
    public function images()
    {
        return $this->hasMany(ItemImage::class); // アイテムは複数の画像を持つ
    }

    // 購入とのリレーション (Purchaseとの関連付け)
    public function purchases()
    {
        return $this->hasMany(Purchase::class); // アイテムは複数の購入に関連付けられる
    }

    // アイテムの状態（新品・中古・リファービッシュ）のフィルタリングを簡単にするためのメソッド
    public function getStatusLabel()
    {
        $statusLabels = [
            'new' => '新品',
            'used' => '中古',
            'refurbished' => 'リファービッシュ'
        ];

        return $statusLabels[$this->status] ?? '不明';
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'item_likes', 'item_id', 'user_id');
    }


    public function comments()
    {
        return $this->hasMany(Comment::class); // Itemは多くのコメントを持つ
    }

   
    
}