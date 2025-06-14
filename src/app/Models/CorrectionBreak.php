<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'break_started_at',
        'break_ended_at',
    ];

    protected $casts = [
        'break_started_at' => 'datetime:H:i',
        'break_ended_at' => 'datetime:H:i',
    ];

    
    public function correctionRequest()
    {
        return $this->belongsTo(CorrectionRequest::class);
    }
}
