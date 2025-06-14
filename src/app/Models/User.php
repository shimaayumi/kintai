<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable implements MustVerifyEmail // MustVerifyEmailインターフェースを実装
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'admin',
    ];

    protected $casts = [
        'admin' => 'boolean',
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
        return $this->hasMany(Like::class);
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


    public function purchasedItems()
    {
        return $this->belongsToMany(Item::class, 'purchases', 'user_id', 'item_id');
    }
    public function favoriteItems()
    {
        return $this->belongsToMany(Item::class, 'favorites');
    }

   


}