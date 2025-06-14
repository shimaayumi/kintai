<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use Carbon\Carbon;


class AttendanceSeeder extends Seeder
{
    public function run()
    {
        Attendance::create([
            'id' => 1,
            'user_id' => 1,
            'work_date' => Carbon::parse('2025-06-09'),
            'started_at' => Carbon::parse('2025-06-09 09:00:00'),
            'ended_at' => Carbon::parse('2025-06-09 17:00:00'),
            'status' => 'ended',
            'note' => '特になし',
            'approval_status' => 'pending',
            'approved_at' => null,
        ]);

        
    }
}
