<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;
    //現在の日時情報がUIと同じ形式で出力されている
    public function 勤怠打刻画面に現在の日時が表示されている()
    {
        // テスト時の日時を固定（テストの再現性を保つ）
        Carbon::setTestNow(Carbon::create(2025, 5, 28, 14, 30)); // 2025-05-28 14:30

        // 勤怠打刻画面にアクセス
        $response = $this->get('/attendance/record');

        // 表示されるはずの日時
        $expectedDateTime = Carbon::now()->format('Y-m-d H:i');

        // 表示内容に期待する日時が含まれているかを確認
        $response->assertStatus(200);
        $response->assertSee($expectedDateTime);

        // Carbon::setTestNow() を解除
        Carbon::setTestNow();
    }





    //勤務外の場合、勤怠ステータスが正しく表示される
    public function test_status_shows_off_duty()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'off',
        ]);

        $response = $this->loginAndAccessAttendance($user);
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    //出勤中の場合、勤怠ステータスが正しく表示される
    public function test_status_shows_working()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'working',
        ]);

        $response = $this->loginAndAccessAttendance($user);
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }


    //休憩中の場合、勤怠ステータスが正しく表示される
    public function test_status_shows_on_break()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'on_break',
        ]);

        $response = $this->loginAndAccessAttendance($user);
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }


    //退勤済の場合、勤怠ステータスが正しく表示される
    public function test_status_shows_ended()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'ended',
        ]);

        $response = $this->loginAndAccessAttendance($user);
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }


    //ユーザーをログインさせた状態で /attendance にアクセスする処理
    private function loginAndAccessAttendance(User $user)
    {
        return $this->actingAs($user)->get('/attendance');
    }
}
