<?php

namespace Database\Seeders;

use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BreakTimeSeeder extends Seeder
{
    public function run(): void
    {
        // 先に attendance_id=1 のデータがあることを前提とする
        BreakTime::create([
            'id' => 1,
            'attendance_id' => 1,
            'break_started_at' => Carbon::parse('2025-06-09 12:00:00'),
            'break_ended_at' => Carbon::parse('2025-06-09 12:30:00'),
        ]);
    }
}
