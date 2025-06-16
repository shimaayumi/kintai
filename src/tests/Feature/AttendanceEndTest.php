<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;


class AttendanceEndTest extends TestCase
{
    use RefreshDatabase;

    //退勤ボタンが正しく機能する
    public function test退勤ボタンが正しく機能して退勤済ステータスになる()
    {
        // ユーザー作成＆ログイン
        $user = User::factory()->create();
        $this->actingAs($user);
    
        // 勤務中の勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'working',
            'started_at' => now()->subHours(3),
        ]);
    
        // 退勤ボタンが表示されているか確認（GETリクエスト）
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('退勤');
    
        // 退勤処理（POST）
        $response = $this->post(route('attendance.action'), ['action' => 'afterWork']);
        $response->assertRedirect(route('attendance.index'));
    
        // DB再取得してステータス確認
        $attendance->refresh();
        $this->assertEquals('ended', $attendance->status);
        $this->assertNotNull($attendance->ended_at);
    }


    //退勤時刻が管理画面で確認できる
    public function test退勤時刻が管理画面で確認できる()
    {
        // 管理者ユーザー（Adminモデル）作成
        $admin = Admin::factory()->create();

        // 管理者としてログイン
        $this->actingAs($admin, 'admin');

        // 管理画面アクセス
        $response = $this->get(route('admin.attendance.list', ['date' => today()->toDateString()]));
        $response->assertStatus(200);

        // 一般ユーザー作成
        $generalUser = User::factory()->create();

        // 出勤データ作成
        $attendance = Attendance::factory()->create([
            'user_id' => $generalUser->id,
            'work_date' => today()->toDateString(),
            'status' => 'working',
            'started_at' => now(),
            'ended_at' => null,
        ]);

        // 一般ユーザーとして退勤処理を実行
        $this->actingAs($generalUser);
        $response = $this->post(route('attendance.action'), ['action' => 'afterWork']);
        $response->assertRedirect(route('attendance.index'));

        // 最新状態を取得
        $attendance->refresh();

        // ended_at が null でないことを確認
        $this->assertNotNull($attendance->ended_at, '退勤時刻が null のままです');

        // 管理者として再度ログイン（省略可）
        $this->actingAs($admin, 'admin');

        // 管理画面に退勤時刻が表示されるか確認
        $response = $this->get(route('admin.attendance.list', ['date' => today()->toDateString()]));
        $response->assertStatus(200);

        $formattedTime = $attendance->ended_at->format('H:i');
        $response->assertSee($formattedTime);
}

}