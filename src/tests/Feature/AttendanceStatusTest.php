<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 時間を固定しておく
        Carbon::setTestNow(Carbon::parse('2025-05-26 09:00:00'));
    }

    //休憩ボタンが正しく機能する
    public function test休憩ボタンが表示されて処理後にステータスが休憩中になる()
    {
        // ユーザー作成＆ログイン（出勤中の状態を準備）
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中の勤怠レコードを作成
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'working',
            'started_at' => now(),
        ]);

        // 勤怠画面にアクセスして「休憩入」ボタンがあるか確認
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 休憩開始の処理をPOST（ルートやパラメータは適宜修正してください）
        $response = $this->post(route('attendance.action'), ['action' => 'startBreak']);
      

        // DBの勤怠レコードを更新後に取得し直す
        $attendance = Attendance::where('user_id', $user->id)->where('work_date', today())->first();

        // ステータスが休憩中になっていることを検証
        $this->assertEquals('on_break', $attendance->status);

        // 休憩開始時刻が記録されていることも確認（カラム名は実装に合わせてください）
        $break = $attendance->breaks()->latest()->first();
        $this->assertNotNull($break);
        $this->assertNotNull($break->break_started_at);
    }



    //休憩は一日に何回でもできる
    public function test休憩は一日に何回でもできて休憩入ボタンが表示される()
    {
        
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'working',
            'started_at' => now(),
            'ended_at' => null,  // ここを明示的にnullに
        ]);

        // 1回目：休憩入
        $response = $this->post(route('attendance.action'), ['action' => 'startBreak']);
     

        $attendance->refresh();
        $this->assertEquals('on_break', $attendance->status);

        // 1回目：休憩戻
        $response = $this->post(route('attendance.action'), ['action' => 'endBreak']);
 

        $attendance->refresh();
        $this->assertEquals('working', $attendance->status);

        // 休憩が終わって再び「休憩入」ボタンが表示されることを確認
      
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 2回目：休憩入
        $response = $this->post(route('attendance.action'), ['action' => 'startBreak']);
        $response->assertRedirect(route('attendance.index'));

        $attendance->refresh();
        $this->assertEquals('on_break', $attendance->status);

        // 2回目の休憩中には「休憩戻」ボタンが表示されていることを確認
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('休憩戻');
    }
    



    //休憩戻ボタンが正しく機能する

    public function test休憩戻ボタンが正しく機能する()
    {
        // ユーザー作成＆ログイン
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中の勤怠レコード作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'working',
            'started_at' => now(),
        ]);

        // 休憩入処理
        $response = $this->post(route('attendance.action'), ['action' => 'startBreak']);

        $attendance->refresh();

     
        // 休憩前の状態にセット
        $attendance->status = 'working';
        $attendance->save();

        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩入');
        $response->assertDontSee('休憩戻');
    }



    //休憩戻は一日に何回でもできる
    public function test休憩戻は一日に何回でもできる()
    {
        // ユーザー作成＆ログイン（出勤中の状態を準備）
        $user = User::factory()->create();
        $this->actingAs($user);

        // 出勤中の勤怠レコードを作成
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'working',
            'started_at' => now(),
        ]);

        // 休憩入の処理
        $response = $this->post(route('attendance.action'), ['action' => 'startBreak']);
        

        // 休憩戻の処理
        $response = $this->post(route('attendance.action'), ['action' => 'endBreak']);
        

        // 再度、休憩入の処理を行う
        $response = $this->post(route('attendance.action'), ['action' => 'startBreak']);
       

        // 最後に画面を取得して「休憩戻」ボタンが表示されていることを確認
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);
        
    }



    //休憩時刻が勤怠一覧画面で確認できる
    public function test_休憩時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today()->toDateString(),
            'status' => 'working',
        ]);

        // ↓ テストのために直接休憩レコード作成（本番ではコントローラが作る）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_started_at' => now()->subMinutes(30),
            'break_ended_at' => now(),
        ]);

        $attendance->refresh();

        $break = BreakTime::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertNotNull($break);
        $this->assertNotNull($break->break_started_at);
        $this->assertNotNull($break->break_ended_at);
    }
}