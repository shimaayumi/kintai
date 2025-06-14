<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;


class RegisterTest extends TestCase
{
    use RefreshDatabase;

    // 名前が未入力の場合、バリデーションメッセージが表示される
    public function test_name_is_required_for_registration()
    {
        $response = $this->post('/register', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_email_is_required_for_registration()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_password_is_required_for_registration()
    {
        
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // パスワードが8文字未満の場合、バリデーションメッセージが表示される
    public function test_password_must_be_at_least_8_characters()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // パスワードと確認用パスワードが一致しない場合、バリデーションメッセージが表示される
    public function test_password_confirmation_must_match()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'mismatch123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_password_is_required_and_error_message_is_displayed()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect('/register'); // エラー時に元のページに戻ることを確認

        // 日本語のメッセージを検証（必要に応じて）
        $this->assertStringContainsString('パスワードを入力してください', session('errors')->first('password'));
    }


    //フォームに内容が入力されていた場合、ユーザーが保存される
    public function test_user_is_registered_when_all_fields_are_valid()
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'securepassword',
            'password_confirmation' => 'securepassword',
        ]);

        $response->assertRedirect('/email/verify'); // メール確認画面にリダイレクトされる

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }
}