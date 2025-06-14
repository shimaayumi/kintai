<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


class UsersSeeder extends Seeder
{
    public function run()
    {
        admin::create([
            'id' => 1,
            'email' => 'admin@example.com',
            'password' => Hash::make('aaaaaaaa'),
        
        ]);

        User::create([
            'id' => 1,
            'name' => '一般ユーザー',  // ユーザー名
            'email' => 'user@example.com',  // メールアドレス
            'email_verified_at' => now(),
            'password' => Hash::make('aaaaaaaa'),  // パスワード
        ]);
    }
}

