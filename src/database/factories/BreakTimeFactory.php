<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakTime;  


class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition()
    {
        $start = $this->faker->dateTimeBetween('-1 week', 'now');
        $end = (clone $start)->modify('+1 hour');

        return [
            'attendance_id' => null, // 後でセットする想定
            'break_started_at' => $start,
            'break_ended_at' => $end,
        ];
    }
}