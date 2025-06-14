<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\CorrectionRequest; 
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Attendance;


class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者ユーザーが承認待ちの修正申請一覧を閲覧できる()
    {
        // 管理者ユーザーを作成・ログイン
        $admin = User::factory()->create(['admin' => true]);
        $this->actingAs($admin);

        // 承認待ち（pending）
        $pendingRequests = CorrectionRequest::factory()->count(3)->create([
            'status' => CorrectionRequest::STATUS_OFF,
            'approval_status' => CorrectionRequest::APPROVAL_PENDING,
        ]);

        // 承認済み（approved）
        $approvedRequests = CorrectionRequest::factory()->count(2)->create([
            'status' => CorrectionRequest::STATUS_OFF,
            'approval_status' => CorrectionRequest::APPROVAL_APPROVED,
        ]);

        // 却下（rejected）
        $rejectedRequests = CorrectionRequest::factory()->count(1)->create([
            'status' => CorrectionRequest::STATUS_OFF,
            'approval_status' => CorrectionRequest::APPROVAL_REJECTED,
        ]);

        // 承認待ちのタブを開くURL（例）
        $response = $this->get(route('admin.request.list', ['tab' => 'pending']));

        $response->assertStatus(200);

        // 画面に承認待ちの修正申請3件の内容が表示されていることを確認
        foreach ($pendingRequests as $request) {
            $response->assertSee(e($request->note)); // `note`を表示してる前提（title → noteに修正）
        }

        // 承認済みや却下の申請は表示されていないことを確認
        foreach ($approvedRequests as $request) {
            $response->assertDontSee($request->note, false);
        }
        foreach ($rejectedRequests as $request) {
            $response->assertDontSee($request->note, false);
        }
    }


    public function test_管理者ユーザーが承認済みの修正申請一覧を閲覧できる()
    {
        // 管理者ユーザーを作成・ログイン
        $admin = User::factory()->create(['admin' => true]);
        $this->actingAs($admin);

        // 承認待ち（pending）
        $pendingRequests = CorrectionRequest::factory()->count(3)->create([
            'status' => CorrectionRequest::STATUS_OFF,
            'approval_status' => CorrectionRequest::APPROVAL_PENDING,
        ]);

        // 承認済み（approved）
        $approvedRequests = CorrectionRequest::factory()->count(2)->create([
            'status' => CorrectionRequest::STATUS_OFF,
            'approval_status' => CorrectionRequest::APPROVAL_APPROVED,
        ]);

        // 却下（rejected）
        $rejectedRequests = CorrectionRequest::factory()->count(1)->create([
            'status' => CorrectionRequest::STATUS_OFF,
            'approval_status' => CorrectionRequest::APPROVAL_REJECTED,
        ]);

        // 承認済みのタブを開くURL（tab=approved）
        $response = $this->get(route('admin.request.list', ['tab' => 'approved']));

        $response->assertStatus(200);

        // 画面に承認済みの修正申請2件の内容が表示されていることを確認
        foreach ($approvedRequests as $request) {
            $response->assertSee(e($request->note)); // noteを表示している前提
        }

        // 承認待ちや却下の申請は表示されていないことを確認
        foreach ($pendingRequests as $request) {
            $response->assertDontSee($request->note, false);
        }
        foreach ($rejectedRequests as $request) {
            $response->assertDontSee($request->note, false);
        }
    }

    public function test_修正申請の承認処理が正しく行われる()
    {
      
        // 管理者ユーザーを作成＆ログイン
        $admin = User::factory()->create(['admin' => true]);
        $this->actingAs($admin);

        // ユーザーと勤怠データを作成
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-06-02',
            'status' => 'working',
            'started_at' => '2024-06-01 09:00',
            'ended_at' => '2024-06-01 18:00',
        ]);

        // 修正申請データを作成（別テーブルに保存）
        $correctionRequest = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'started_at' => '2024-06-01 10:00:00',
            'ended_at' => '2024-06-01 19:00:00',
            'status' => 'working',
            'approval_status' => 'pending',
        ]);

        // 承認処理を実行
        $response = $this->post(route('stamp_correction_request.approve', $correctionRequest->id));

        // リダイレクト確認
        $response->assertRedirect(route('stamp_correction_request.index'));

        // 修正申請のステータス確認
        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'approval_status' => 'approved',
        ]);

        // 勤怠情報が修正内容に更新されたことを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'started_at' => '2024-06-01 10:00',
            'ended_at' => '2024-06-01 19:00',
        ]);
    }
}