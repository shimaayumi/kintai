<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    // プロフィールのフィルター可能なカラム
    protected $fillable = [
        'user_id',
        'bio',
        'profile_image',  // ここを追加
    ];

    // リレーション: 1つのプロフィールは1人のユーザーに関連付けられている
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}