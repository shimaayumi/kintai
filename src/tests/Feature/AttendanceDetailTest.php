<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    //勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    //勤怠詳細画面の「日付」が選択した日付になっている
    //「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    //「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_勤怠詳細画面にログインユーザーの情報が正しく表示される()
    {
        // ログインユーザー作成
        $user = User::factory()->create([
            'name' => '山田 太郎',
            
        ]);
        $this->actingAs($user);

        // 勤怠データ作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-05-01',
            'started_at' => '2025-05-01 09:00:00',
            'ended_at' => '2025-05-01 18:00:00',
        ]);

        // 休憩時間作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_started_at' => '2025-05-01 12:00:00',
            'break_ended_at' => '2025-05-01 13:00:00',
        ]);

        // 詳細ページにアクセス
        $response = $this->get("/attendance/{$attendance->id}");

        // ステータスコードと表示内容を確認
        $response->assertStatus(200);

        // 氏名の表示を確認（例：山田 太郎）
        $response->assertSee('山田 太郎');

     
      
        $response->assertSee('2025年');
        $response->assertSee('5月1日');

        // 出勤・退勤時刻
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 休憩時間（例：1:00）
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}