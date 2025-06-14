<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $users;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザー作成（adminなどのフラグが必要であれば調整）
        $this->admin = User::factory()->create([
            'admin' => true,
        ]);

        // 一般ユーザーとその勤怠データ作成
        $this->users = User::factory()->count(3)->create();

        $this->users->each(function ($user) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => Carbon::today()->toDateString(),
                'started_at' => '09:00',
                'ended_at' => '18:00',
             
            ]);
        });
     
}

    //その日になされた全ユーザーの勤怠情報が正確に確認できる
    /** @test */
    public function 管理者はその日の全ユーザーの勤怠情報を正確に確認できる()
{
    // 管理者ユーザーを作成
    $admin = User::factory()->create([
        'admin' => true,
    ]);

    // 一般ユーザー3人と当日の勤怠データを作成
    $users = User::factory()->count(3)->create();
    $today = Carbon::today()->toDateString();

    foreach ($users as $user) {
        // 勤怠データ作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $today,
            'started_at' => '09:00',
            'ended_at' => '18:00',
          
        ]);
        
        // 休憩データも作成（Breakモデルがあれば）
        // もし休憩データがAttendance内のカラムで管理されているなら不要
        // $tomorrow の代わりに $today を使うなど
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_started_at' => Carbon::parse($today . ' 12:00'),
            'break_ended_at' => Carbon::parse($today . ' 13:00'),
        ]);
    }

    // 管理者としてアクセス
    $response = $this->actingAs($admin)->get('/admin/attendance/list');

    // レスポンス確認
    $response->assertStatus(200);

    // 各ユーザーの勤怠情報が正確に表示されているか確認
    foreach ($users as $user) {
        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');  // 休憩1時間の表示チェック
        $response->assertSee('8:00');  // 勤務時間8時間の表示チェック
        }
}


    //遷移した際に現在の日付が表示される
    /** @test */
    public function test_勤怠一覧画面に現在の日付が表示される()
    {
        // 管理者ユーザーを作成＆ログイン
        $admin = User::factory()->create([
            'admin' => true,
        ]);
        $this->actingAs($admin);

        $today = now();
        $todayFormatted = $today->format('Y年n月j日'); // ビュー表示に合わせる

        $response = $this->get('/admin/attendance/list?date=' . $today->toDateString());

        $response->assertStatus(200);
        $response->assertSee($todayFormatted);
    }



    //「前日」を押下した時に前の日の勤怠情報が表示される
    /** @test */
    public function test_前日ボタンで前日の勤怠情報が表示される()
    {
        $admin = User::factory()->create([
            'admin' => true,
        ]);

        $this->actingAs($admin);

        // 今日ではなく「前日」の日付をセット
        $yesterday = now()->subDay()->startOfDay();

        foreach ($this->users as $user) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $yesterday->toDateString(),
                'status' => 'off',
                'started_at' => $yesterday->copy()->setTime(9, 0),
                'ended_at' => $yesterday->copy()->setTime(18, 0),
                'note' => 'テスト',
                'approval_status' => 'pending',
            ]);

            BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'break_started_at' => $yesterday->copy()->setTime(12, 0),
                'break_ended_at' => $yesterday->copy()->setTime(13, 0),
            ]);
        }

        // 勤怠一覧を「前日の日付」で取得
        $response = $this->get('/admin/attendance/list?date=' . $yesterday->toDateString());

        $response->assertStatus(200);
        $response->assertSee($yesterday->format('Y年n月j日') . 'の勤怠');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');  // 休憩1時間の表示チェック
        $response->assertSee('8:00');  // 勤務時間8時間の表示チェック
    }



    public function test_翌日ボタンで翌日の勤怠情報が表示される()
    {
        $admin = User::factory()->create([
            'admin' => true,
        ]);

        $this->actingAs($admin);

        // 今日ではなく「翌日」の日付をセット
        $tomorrow = now()->addDay()->startOfDay();

        foreach ($this->users as $user) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $tomorrow->toDateString(),
                'status' => 'off',
                'started_at' => $tomorrow->copy()->setTime(9, 0),
                'ended_at' => $tomorrow->copy()->setTime(18, 0),
                'note' => 'テスト（翌日）',
                'approval_status' => 'pending',
            ]);

            BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'break_started_at' => $tomorrow->copy()->setTime(12, 0),
                'break_ended_at' => $tomorrow->copy()->setTime(13, 0),
            ]);
        }

        // 勤怠一覧を「翌日の日付」で取得
        $response = $this->get('/admin/attendance/list?date=' . $tomorrow->toDateString());

        $response->assertStatus(200);
        $response->assertSee($tomorrow->format('Y年n月j日') . 'の勤怠');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');  // 休憩1時間の表示チェック
        $response->assertSee('8:00');  // 勤務時間8時間の表示チェック
    }

    
}
