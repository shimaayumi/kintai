<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'price',
        'category_id',
        'image',
        'sold_flag'
    ];

    // Likes リレーション
    public function likes()
    {
        return $this->hasMany(Like::class, 'items_id');
    }

    // Comments リレーション
    public function comments()
    {
        return $this->hasMany(Comment::class, 'item_id');
    }

    // Category リレーション
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // User リレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}