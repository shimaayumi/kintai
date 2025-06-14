<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'work_date' => today(), 
            'status' => 'off',
            'started_at' => null,
            'ended_at' => null,
            'note' => null,
            'approval_status' => 'pending',
        ];
    }
    
    public function withWorkTimes()
    {
        return $this->state(function (array $attributes) {
            $date = today();
            return [
                'started_at' => $date->copy()->setTime(9, 0),
                'ended_at' => $date->copy()->setTime(18, 0),
            ];
        });
    }

    public function withStartedAtByWorkDate($workDate)
    {
        return $this->state(function () use ($workDate) {
            $carbonDate = \Carbon\Carbon::parse($workDate);
            return [
                'started_at' => $carbonDate->copy()->setTime(9, 0, 0),
                'ended_at' => $carbonDate->copy()->setTime(18, 0, 0),
            ];
        });
    }
   
}
