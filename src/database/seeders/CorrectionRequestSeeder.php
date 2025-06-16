<?php

namespace Database\Seeders;

use App\Models\CorrectionBreak;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CorrectionRequestSeeder extends Seeder
{
    public function run(): void
    {
        // CorrectionRequestを作成
        $correction = CorrectionRequest::create([
            'id' => 1,
            'user_id' => 1,
            'attendance_id' => 1,
            'started_at' => Carbon::parse('2025-06-09 09:15:00'),
            'ended_at' => Carbon::parse('2025-06-09 17:05:00'),
            'note' => '会議が長引いたため出勤・退勤を修正',
            'status' => 'ended',
            'approval_status' => 'pending',
            'approved_at' => null,
        ]);

        // correction_breaks を1件追加（例：12:00〜13:00の休憩）
        CorrectionBreak::create([
            'correction_request_id' => $correction->id,
            'break_started_at' => Carbon::parse('2025-06-09 12:00:00'),
            'break_ended_at' => Carbon::parse('2025-06-09 13:00:00'),
        ]);
    }
}
