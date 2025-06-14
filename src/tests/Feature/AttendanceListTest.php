<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025/05/01');
    }


    //自分が行った勤怠情報が全て表示されている
    public function test_自分の勤怠情報が全て表示されている()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id, 
            'work_date' => '2025-05-01',
            'status' => 'ended',
            'started_at' => '2025-05-01 09:00:00',
            'ended_at' => '2025-05-01 18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_started_at' => '2025-05-01 12:00:00',
            'break_ended_at' => '2025-05-01 13:00:00',
        ]);

        $response = $this->get('/attendance/list?month=2025-05');

        $response->assertStatus(200);
        $response->assertSee('05/01');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
    }


    //勤怠一覧画面に遷移した際に現在の月が表示される
    public function test勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $response = $this->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('2025/05');
    }


    //「前月」を押下した時に表示月の前月の情報が表示される
    public function test前月を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create();
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $previousMonth . '-10',
        ]);

        $this->actingAs($user);
        $response = $this->get(route('attendance.list', ['month' => $previousMonth]));

        $response->assertStatus(200);
        $response->assertSee('2025/04');
        $response->assertSee('10');
    }


    //「翌月」を押下した時に表示月の前月の情報が表示される
    public function test翌月を押下した時に表示月の翌月の情報が表示される()
    {
        $user = User::factory()->create();
        $nextMonth = Carbon::now()->addMonth()->format('Y-m');
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $nextMonth . '-05',
        ]);

        $this->actingAs($user);
        $response = $this->get(route('attendance.list', ['month' => $nextMonth]));

        $response->assertStatus(200);
        $response->assertSee('2025/06');
        $response->assertSee('05');
    }


    //「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test詳細ボタンを押下するとその日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-05-01',
        ]);

        $this->actingAs($user);
        $response = $this->get(route('attendance.show', ['id' => $attendance->id]));

        $response->assertStatus(200);
        $response->assertSee('2025-05-01');
    }


}