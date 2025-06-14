<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'breaks'; // 明示的に指定

    protected $fillable = [
        'attendance_id',
        'break_started_at',
        'break_ended_at',
    ];

    protected $casts = [
        'break_started_at' => 'datetime',
        'break_ended_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
