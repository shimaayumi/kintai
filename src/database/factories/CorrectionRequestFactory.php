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
            'approval_status' => CorrectionRequest::APPROVAL_PENDING,
            'approved_at' => null,
            'status' => CorrectionRequest::STATUS_OFF,
            'started_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    // 承認済み状態を作るメソッド
   public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'approval_status' => CorrectionRequest::APPROVAL_APPROVED,
                'approved_at' => now(),
                'note' => 'Saepe totam assumenda eius aut aut.', // ← テスト期待文言をここでセット
                'status' => CorrectionRequest::STATUS_OFF,
            ];
        });
    }
}