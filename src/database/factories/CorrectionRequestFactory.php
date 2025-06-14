<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\CorrectionRequest;

class CorrectionRequestFactory extends Factory
{
    protected $model = \App\Models\CorrectionRequest::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'attendance_id' => \App\Models\Attendance::factory(),
            'note' => $this->faker->sentence(),
            'approval_status' => CorrectionRequest::APPROVAL_PENDING, // 承認待ち
            'approved_at' => null,
            'status' => CorrectionRequest::STATUS_OFF, // 勤怠状態（例: off, working, etc）
            'started_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}