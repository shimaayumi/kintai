<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_requires_email_to_login()
    {
        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function it_requires_password_to_login()
    {
        $response = $this->post('/login', [
            'email' => 'user@example.com',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function testLoginWithInvalidCredentials()
    {
        // ログインページを開く
        $response = $this->get('/login');

        // 必要項目が登録されていない情報を入力する（例えば、間違ったメールアドレスとパスワード）
        $response = $this->post('/login', [
            'email' => 'invalid@example.com',  // 存在しないメールアドレス
            'password' => 'wrongpassword',     // 間違ったパスワード
        ]);

        // バリデーションメッセージ「ログイン情報が登録されていません」が表示されることを確認
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function testLoginWithCorrectCredentials()
    {
        // テスト用ユーザーを作成（必要に応じて事前にデータベースにユーザーを作成）
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'), // パスワードはハッシュ化する
        ]);

        // ログインページを開く
        $response = $this->get('/login');

        // 正しい情報を入力してログインフォームを送信する
        $response = $this->post('/login', [
            'email' => 'user@example.com',  // 正しいメールアドレス
            'password' => 'password',       // 正しいパスワード
        ]);

     

        // ユーザーが認証されていることを確認
        $this->assertAuthenticatedAs($user);  // ログインが成功し、認証されていることを確認
    }

    public function testLogout()
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        // ログインする
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

      
        // ユーザーが認証されていることを確認
        $this->assertAuthenticatedAs($user);

        // ログアウトする
        $response = $this->post('/logout');

       
        // ユーザーがログアウトされていることを確認
        $this->assertGuest();  // ユーザーがゲスト状態であることを確認
    }

}

