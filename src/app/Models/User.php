<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable // 認証のためにAuthenticatableを継承
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // プロフィールとのリレーション
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    // 出品したアイテムとのリレーション
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    // いいね機能とのリレーション
    public function likes()
    {
        return $this->belongsToMany(Item::class, 'item_likes', 'user_id', 'item_id');
    }

    // コメントとのリレーション
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }
}
