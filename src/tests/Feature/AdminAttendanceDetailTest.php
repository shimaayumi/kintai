<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Admin;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;
    protected User $admin;


    protected function setUp(): void
    {
        parent::setUp(); // これが必須！

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');
    }

    public function test管理者は勤怠詳細画面で正しい情報を確認できる()
    {
        // 管理者ユーザー作成
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        // 一般ユーザー作成
        $user = User::factory()->create();
        $now = now()->startOfDay();
        // 勤怠データ作成（特定の日時）
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'started_at' => '09:00',
            'ended_at' => '18:00',
        ]);

        // 休憩データ作成
        $break = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_started_at' => now()->setTime(12, 0),
            'break_ended_at' => now()->setTime(13, 0),
        ]);

        // 管理者として勤怠詳細ページにアクセス
        $response = $this->actingAs($admin)
            ->get(route('attendance.show', $attendance->id));

        $response->assertStatus(200);

        // 勤怠詳細画面に該当ユーザー名が表示されているか
        $response->assertSeeText($user->name);

        // 出勤時間・退勤時間の表示確認
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);

        // 休憩時間の表示（開始・終了時刻）
        $response->assertSee('value="12:00"', false); // 休憩開始時間
        $response->assertSee('value="13:00"', false); // 休憩終了時間など

        // 必要に応じて他の詳細情報もassertSeeTextで確認可能
    }





    public function test_出勤時間が退勤時間より後の場合にエラーメッセージが表示される()
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $attendance = Attendance::factory()->create();

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'started_at' => '19:00',  // 出勤時間が退勤時間より後
            'ended_at' => '18:00',
            'breaks' => [
                [
                    'break_started_at' => '17:00',
                    'break_ended_at' => '17:30',
                ]
            ],
        ]);

        $response->assertSessionHasErrors([
            'ended_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }


    public function test_休憩開始時間が退勤時間より後の場合にエラーメッセージが表示される()
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $attendance = Attendance::factory()->create();

        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'started_at' => '09:00',
            'ended_at' => '18:00',
            'breaks' => [
                [
                    'break_started_at' => '19:00', // 退勤より後
                    'break_ended_at' => '19:30',
                ]
            ],
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_started_at' => '休憩時間が勤務時間外です。',
        ]);
    }

    public function test_備考欄が未入力の場合にエラーメッセージが表示される()
    {
        // 管理者としてログイン
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        // 勤怠データ作成（もしくは用意）
        $attendance = Attendance::factory()->create();

        // 備考欄を空にして更新リクエスト送信
        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'started_at' => $attendance->started_at ? $attendance->started_at->format('H:i') : '09:00',
            'ended_at' => $attendance->ended_at ? $attendance->ended_at->format('H:i') : '18:00',
            'note' => '', // 備考空欄（バリデーションエラーになる）
        ]);

        // バリデーションエラーがセッションにあるか確認
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }


    
    public function test_管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        $admin = Admin::factory()->create();

        // 一般ユーザーを複数作成し、$users に代入
        $users = User::factory()->count(3)->sequence(
            ['email' => 'testuser1@example.com'],
            ['email' => 'testuser2@example.com'],
            ['email' => 'testuser3@example.com']
        )->create();

        // 出勤データなど必要なセットアップ
        foreach ($users as $user) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => Carbon::create(2025, 6, 15),
                'started_at' => '09:00',
                'ended_at' => '18:00',
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.staff.list'));

        $response->assertStatus(200);

        // $users の各ユーザーの名前とメールが表示されているかをチェック
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    public function test_ユーザーの勤怠情報が正しく表示される()
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();

        // ここで必ず $attendances を作成しているか
        $attendances = Attendance::factory()->count(3)->sequence(
            ['work_date' => now()->subDays(3)],
            ['work_date' => now()->subDays(2)],
            ['work_date' => now()->subDays(1)],
        )->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('admin.staff.monthly', ['id' => $user->id]));

        $response->assertStatus(200);

        foreach ($attendances as $attendance) {
            // 変換してassertSeeなど
        }
    }

    public function test_「前月」を押下した時に表示月の前月の情報が表示される()
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();

        // 表示したい「前月」の年月を取得（例：今月の1日から1ヶ月前）
        $targetDate = now()->startOfMonth()->subMonth();

        // 前月の勤怠データを作成
        $attendances = Attendance::factory()->count(3)->sequence(
            ['work_date' => $targetDate->copy()->addDays(0), 'started_at' => $targetDate->copy()->addDays(0)->setTime(9, 0)],
            ['work_date' => $targetDate->copy()->addDays(1), 'started_at' => $targetDate->copy()->addDays(1)->setTime(9, 0)],
            ['work_date' => $targetDate->copy()->addDays(2), 'started_at' => $targetDate->copy()->addDays(2)->setTime(9, 0)],
        )->create(['user_id' => $user->id]);

        // 勤怠一覧ページを、前月の年月をパラメータにしてGET
        $response = $this->get(route('admin.staff.monthly', [
            'id' => $user->id,
            'year' => $targetDate->year,
            'month' => $targetDate->month,
        ]));

        $response->assertStatus(200);

        foreach ($attendances as $attendance) {
            $workDate = $attendance->work_date instanceof \Carbon\Carbon
                ? $attendance->work_date
                : \Carbon\Carbon::parse($attendance->work_date);

            $weekdayJP = ['日', '月', '火', '水', '木', '金', '土'][$workDate->dayOfWeek];
            $expectedLabel = $workDate->format('m/d') . "({$weekdayJP})";

            $response->assertSeeText($expectedLabel);
            
        }
    }

    public function test_「翌月」を押下した時に表示月の前月の情報が表示される()
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();

        // 表示したい「翌月」の年月を取得（例：今月の1日から1ヶ月後）
        $targetDate = now()->startOfMonth()->addMonth();

        // 翌月の勤怠データを作成
        $attendances = Attendance::factory()->count(3)->sequence(
            ['work_date' => $targetDate->copy()->addDays(0), 'started_at' => $targetDate->copy()->addDays(0)->setTime(9, 0)],
            ['work_date' => $targetDate->copy()->addDays(1), 'started_at' => $targetDate->copy()->addDays(1)->setTime(9, 0)],
            ['work_date' => $targetDate->copy()->addDays(2), 'started_at' => $targetDate->copy()->addDays(2)->setTime(9, 0)],
        )->create(['user_id' => $user->id]);

        // 勤怠一覧ページを、翌月の年月をパラメータにしてGET
        $response = $this->get(route('admin.staff.monthly', [
            'id' => $user->id,
            'year' => $targetDate->year,
            'month' => $targetDate->month,
        ]));

        $response->assertStatus(200);

        foreach ($attendances as $attendance) {
            $workDate = $attendance->work_date instanceof \Carbon\Carbon
                ? $attendance->work_date
                : \Carbon\Carbon::parse($attendance->work_date);

            $weekdayJP = ['日', '月', '火', '水', '木', '金', '土'][$workDate->dayOfWeek];
            $expectedLabel = $workDate->format('m/d') . "({$weekdayJP})";

            $response->assertSeeText($expectedLabel);
        }
    }

    public function test_「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'started_at' => now()->setTime(9, 0),
            'ended_at' => now()->setTime(18, 0),
        ]);

        $response = $this->get(route('admin.staff.monthly', [
            'id' => $user->id,
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertStatus(200);

        $detailUrl = route('attendance.show', ['id' => $attendance->id]);
        $response->assertSee($detailUrl);

        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);

        // 日付表示の形式に合わせてチェック
        $detailResponse->assertSee(\Carbon\Carbon::parse($attendance->started_at)->format('Y年'));
        $detailResponse->assertSee(\Carbon\Carbon::parse($attendance->started_at)->format('n月'));
        $detailResponse->assertSee(\Carbon\Carbon::parse($attendance->started_at)->format('j日'));

        $detailResponse->assertSee(\Carbon\Carbon::parse($attendance->started_at)->format('H:i'));
        $detailResponse->assertSee(\Carbon\Carbon::parse($attendance->ended_at)->format('H:i'));
    }
}

