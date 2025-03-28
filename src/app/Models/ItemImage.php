<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemImage extends Model
{
    use HasFactory;

    // テーブル名がデフォルトと異なる場合は明示的に指定
    // protected $table = 'item_images';

    protected $fillable = [
        'item_id',  // 商品ID
        'image_url' // 画像URL
    ];

    /**
     * 商品に関連する画像
     */
    public function item()
    {
        // 1対多のリレーションで、ItemImageはItemに属している
        return $this->belongsTo(Item::class);
    }

    /**
     * アップロードされた画像のURLをフルパスで返す
     * 画像URLが `storage/` を含む場合に、フルパスを返すように修正
     */
    public function getImageUrlAttribute($value)
    {
        return asset('storage/' . $value); // public/storage/ 以下にアクセスできるURL
    }
}
