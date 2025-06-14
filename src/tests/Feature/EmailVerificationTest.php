<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録後に認証メールが送信されることをテスト
     */
    public function test_registration_sends_verification_email()
    {
        Notification::fake(); // ←これを先に！

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 認証誘導画面の「認証はこちらから」ボタンを押下すると認証メール送信リンクに遷移するテスト
     */
    public function test_verification_notice_page_displays()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee('認証はこちらから'); // 誘導ボタンの文言をチェック
    }

    

    //メール認証サイトのメール認証を完了すると、ログイン画面に遷移する
    public function test_email_verification_completes_and_redirects_to_attendance_page()
    {
        // ユーザーをメール未認証状態で作成
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // actingAs でログイン状態にする（セッション保持がポイント）
        $this->actingAs($user);

        // 署名付きの確認リンクを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 確認リンクにアクセス（ログイン状態のまま）
        $response = $this->get($verificationUrl);

        // 認証フラグがtrueになっているか確認
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // ログイン画面にリダイレクトされるか確認
        $response->assertRedirect(route('login'));
    }
}
