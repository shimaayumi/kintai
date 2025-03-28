<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersSeeder extends Seeder
{
    public function run()
    {
        // ユーザーが存在しない場合にのみ新しく作成
        $user = User::firstOrCreate([
            'email' => 'testuser@example.com', // ユーザーのemailを条件にする
        ], [
            'name' => 'テストユーザー',
            'password' => Hash::make('password123'),  // パスワードをハッシュ化
        ]);

        // プロフィールを作成（もし必要なら）
        DB::table('profiles')->updateOrInsert(
            ['user_id' => $user->id],  // 既存のユーザーIDを条件にする
            [
                'profile_image' => 'default.png', // デフォルト画像
            ]
        );
    }
}
