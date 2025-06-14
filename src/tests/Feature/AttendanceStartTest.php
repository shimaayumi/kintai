<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceStartTest extends TestCase
{
    use RefreshDatabase;

    //出勤ボタンが正しく機能する
    public function test_勤務外ユーザーには出勤ボタンが表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'off',
        ]);

        $response = $this->get('/attendance'); // 出勤ボタンが表示されるページ
        $response->assertSee('出勤');
    }

    
    public function test_出勤処理を行うとステータスが勤務中になる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/attendance', ['action' => 'startWork']);
        $response->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'working',
        ]);
    }

    //出勤は一日一回のみできる
    public function test_一日一回しか出勤できない()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'ended', // 退勤済
        ]);

        

        $response = $this->get('/attendance'); // 出勤ボタンの表示チェック
        $response->assertStatus(200);
        $response->assertSee('お疲れ様でした');
    }

   //出勤時刻が管理画面で確認できる
    public function test_出勤時刻が管理画面で確認できる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2025, 5, 26, 9, 0, 0));

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'off',
        ]);

        $this->post('/attendance', ['action' => 'startWork']);
      
        $attendance = Attendance::where('user_id', $user->id)->where('work_date', today())->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->started_at);
        $this->assertEquals('2025-05-26 09:00:00', $attendance->started_at->format('Y-m-d H:i:s'));
    }
}
