<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;

    protected $table = 'correction_requests';

    protected $fillable = [
        'user_id',
        'admin_id',           // 管理者修正者IDを追加
        'attendance_id',
        'started_at',
        'ended_at',
       
        'note',
        'status',
        'approval_status',
        'approved_at',
        'requester_type',     // 申請者タイプを追加
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
      
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 勤怠状態（status）
    const STATUS_OFF = 'off';
    const STATUS_WORKING = 'working';
    const STATUS_ON_BREAK = 'on_break';
    const STATUS_ENDED = 'ended';

    // 申請承認状態（approval_status）
    const APPROVAL_UNSUBMITTED = 'Unsubmitted';
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';

    // 申請者タイプ
    const REQUESTER_USER = 'user';
    const REQUESTER_ADMIN = 'admin';

    public static function approvalStatusList()
    {
        return [
            self::APPROVAL_UNSUBMITTED => self::APPROVAL_UNSUBMITTED,
            self::APPROVAL_PENDING => self::APPROVAL_PENDING,
            self::APPROVAL_APPROVED => self::APPROVAL_APPROVED,
        ];
    }

    // 申請者タイプ一覧
    public static function requesterTypeList()
    {
        return [
            self::REQUESTER_USER => '一般ユーザー',
            self::REQUESTER_ADMIN => '管理者',
        ];
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // 一般ユーザー（申請者）
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 管理者ユーザー（管理者修正者）
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // 休憩申請のリレーション
    public function correctionBreaks()
    {
        return $this->hasMany(CorrectionBreak::class);
    }

    // アクセサ例（時間をH:i形式で返す）
    public function getWorkStartTimeAttribute()
    {
        return $this->started_at ? $this->started_at->format('H:i') : null;
    }

    public function getWorkEndTimeAttribute()
    {
        return $this->ended_at ? $this->ended_at->format('H:i') : null;
    }

    public function getBreakStartTimeAttribute()
    {
        return $this->break_started_at ? $this->break_started_at->format('H:i') : null;
    }

    public function getBreakEndTimeAttribute()
    {
        return $this->break_ended_at ? $this->break_ended_at->format('H:i') : null;
    }

    public function breaks()
    {
        return $this->hasMany(CorrectionBreak::class);
    }
}