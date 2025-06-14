<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'work_date',
        'started_at',
        'ended_at',
        'status',
        'note',
        'approval_status',
        'approved_at',
     
    ];

    protected $casts = [
        'work_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'approved_at' => 'datetime',
        
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(\App\Models\BreakTime::class);
    }

    public function correctionRequest()
    {
        return $this->hasOne(CorrectionRequest::class);
    }
    
    public function breaks()
    {
        return $this->hasMany(BreakTime::class); // モデル名は適宜調整
    }
}