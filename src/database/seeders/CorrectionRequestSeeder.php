<?php

namespace Database\Seeders;

use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CorrectionRequestSeeder extends Seeder
{
  
    // ダミーデータを挿入
    public function run(): void
    {
        CorrectionRequest::create([
            'id' => 1,
            'user_id' => 1,
            'attendance_id' => 1,
            'started_at' => Carbon::parse('2025-06-09 09:15:00'), // 修正申請：少し遅めの出勤
            'ended_at' => Carbon::parse('2025-06-09 17:05:00'),   // 修正申請：少し残業
            
            'note' => '会議が長引いたため出勤・退勤を修正',
            'status' => 'ended',
            'approval_status' => 'pending',
            'approved_at' => null,
        ]);
    }
    
}
