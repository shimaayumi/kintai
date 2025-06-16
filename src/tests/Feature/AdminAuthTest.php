<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;


class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のユーザーを作成
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    // 1. メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_email_is_required()
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'validpassword123',
        ]);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    // 2. パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_password_is_required()
    {
        $response = $this->post('/admin/login', [
            'email' => 'user@example.com',
            'password' => '',
        ]);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    // 3. 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_invalid_credentials_show_error_message()
    {
        $response = $this->post('/admin/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertSessionHasErrors('email');
    }


    
}
