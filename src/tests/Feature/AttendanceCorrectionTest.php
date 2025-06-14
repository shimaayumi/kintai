<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }


    //出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_出勤時間が退勤時間より後ならバリデーションエラー()
    {
        $response = $this->actingAs($this->user)->put(route('attendance.update', $this->attendance->id), [
            'started_at' => '18:00',   // 修正
            'ended_at' => '09:00',     // 修正
            'breaks' => [              // 配列で休憩時間を送る
                [
                    'break_started_at' => '12:00',
                    'break_ended_at' => '13:00',
                ],
            ],
            'note' => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'ended_at' => '退勤時間は出勤時間より後である必要があります',
        ]);
    }


    //休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_休憩開始時間が退勤時間より後ならバリデーションエラー()
    {
        $response = $this->actingAs($this->user)->put(route('attendance.update', $this->attendance->id), [
            'started_at' => '09:00',
            'ended_at' => '18:00',
            'breaks' => [
                [
                    'break_started_at' => '19:00', // 退勤時間より後
                    'break_ended_at' => '20:00',
                ]
            ],
            'note' => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_started_at' => '出勤時間もしくは退勤時間が不適切な値です', // 実際のメッセージに合わせて調整
        ]);
    }


    //休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_休憩終了時間が退勤時間より後ならバリデーションエラー()
    {
        $response = $this->actingAs($this->user)->put(route('attendance.update', $this->attendance->id), [
            'started_at' => '09:00',
            'ended_at' => '18:00',
            'breaks' => [
                [
                    'break_started_at' => '17:00', // 退勤時間より後
                    'break_ended_at' => '20:00',
                ]
            ],
            'note' => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_ended_at' => '出勤時間もしくは退勤時間が不適切な値です', // 実際のメッセージに合わせて調整
        ]);
    }


    //備考欄が未入力の場合のエラーメッセージが表示される
    public function test_備考欄が未入力ならバリデーションエラー()
    {
        $response = $this->actingAs($this->user)->put(route('attendance.update', $this->attendance->id), [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
            'note' => '',
        ]);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }


    //修正申請処理が実行される
    public function test_修正申請処理が実行される()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('request.store', $this->attendance->id), [
            'started_at' => now()->setTime(9, 0)->toDateTimeString(),
            'ended_at' => now()->setTime(18, 0)->toDateTimeString(),
            'break_started_at' => now()->setTime(12, 0)->toDateTimeString(),
            'break_ended_at' => now()->setTime(13, 0)->toDateTimeString(),
            'note' => '修正申請テスト',
        ]);

      

        // ✅ エラーがなければリダイレクトしているはず
        $response->assertRedirect(); // これが失敗してる理由を上で確認
        $response->assertSessionHasNoErrors(); // バリデーションエラーがあればここで失敗

        // DBにデータが入っているか確認
        $this->assertDatabaseHas('correction_requests', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'approval_status' => CorrectionRequest::APPROVAL_PENDING,
            'status' => CorrectionRequest::STATUS_OFF,
            'note' => '修正申請テスト',
        ]);
    }
    


    //「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_承認済みに承認された修正申請が表示される()
    {
        // 管理者ユーザー
        $admin = User::factory()->create([
            'admin' => true,
        ]);

        // 一般ユーザー
        $user = User::factory()->create();

        // 承認済みの修正申請データを作成（reasonを指定）
        $correctionRequest = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'approval_status' => 'approved',
            'status' => 'off',
            
        ]);

        $response = $this->actingAs($admin)->get(route('stamp_correction_request.index'));
        
        // テキストが画面に表示されているか確認
        $response->assertSee('承認済み');
     
    }


    //各申請の「詳細」を押下すると申請詳細画面に遷移する
    public function test_申請詳細画面に遷移できる()
    {
        $user = User::factory()->create(['admin' => true]); // 管理者としてログイン
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $correction = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'note' => '詳細画面テスト',
            'status' => 'off',
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(
            route('stamp_correction_request.show', ['attendance_correct_request' => $correction->id])
        );

        $response->assertStatus(200);
        $response->assertSee('詳細画面テスト');
    }
}
