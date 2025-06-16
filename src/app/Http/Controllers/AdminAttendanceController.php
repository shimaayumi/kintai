<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Http\Requests\AttendanceRequest;
use App\Models\CorrectionRequest;


class AdminAttendanceController extends Controller
{

    // 管理者向け全スタッフのの勤怠一覧画面
    public function dailyAll(Request $request)
    {
        $date = $request->input('date') ?? now()->format('Y-m-d');

        $attendances = Attendance::with([
            'user',
            'breakTimes',
            'correctionRequest.correctionBreaks', 
        ])->whereDate('started_at', $date)->get();

        $attendancesFormatted = $attendances->map(function ($attendance) {
            $correction = $attendance->correctionRequest;

            if ($correction) {
                // 修正申請がある場合 → correction_requestsのデータを優先
                $start = $correction->started_at ? Carbon::parse($correction->started_at) : null;
                $end = $correction->ended_at ? Carbon::parse($correction->ended_at) : null;

                $correctionBreakMinutes = 0;
                $correctionBreaksFormatted = [];

                foreach ($correction->correctionBreaks as $cBreak) {
                    $startBreak = $cBreak->break_started_at ? Carbon::parse($cBreak->break_started_at) : null;
                    $endBreak = $cBreak->break_ended_at ? Carbon::parse($cBreak->break_ended_at) : null;

                    if ($startBreak && $endBreak) {
                        $duration = $startBreak->diffInMinutes($endBreak);
                        $correctionBreakMinutes += $duration;

                        $correctionBreaksFormatted[] = [
                            'start' => $startBreak->format('H:i'),
                            'end' => $endBreak->format('H:i'),
                            'duration_minutes' => $duration,
                        ];
                    }
                }

                $workMinutes = 0;
                $breakMinutes = $correctionBreakMinutes;

                if ($start && $end) {
                    $workMinutes = $start->diffInMinutes($end) - $breakMinutes;
                }

                return [
                    'id' => $attendance->id,
                    'user_name' => $attendance->user->name ?? '不明',
                    'started_at' => $start ? $start->format('H:i') : '',
                    'ended_at' => $end ? $end->format('H:i') : '',
                    'break_time' => $breakMinutes ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '',
                    'work_time' => $workMinutes ? sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60) : '',
                    'correction_requested' => true,
                    'correction_status' => $correction->status,
                    'correction_breaks' => $correctionBreaksFormatted,
                    'correction_break_total' => $breakMinutes ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '',
                ];
            } else {
                // 修正申請がない場合は通常の勤怠と休憩時間を使う
                $workMinutes = 0;
                $breakMinutes = 0;

                if ($attendance->started_at && $attendance->ended_at) {
                    $start = Carbon::parse($attendance->started_at);
                    $end = Carbon::parse($attendance->ended_at);
                    $workMinutes = $start->diffInMinutes($end);

                    foreach ($attendance->breakTimes as $break) {
                        if ($break->break_started_at && $break->break_ended_at) {
                            $breakStart = Carbon::parse($break->break_started_at);
                            $breakEnd = Carbon::parse($break->break_ended_at);
                            $breakMinutes += $breakStart->diffInMinutes($breakEnd);
                        }
                    }

                    $workMinutes -= $breakMinutes;
                }

                return [
                    'id' => $attendance->id,
                    'user_name' => $attendance->user->name ?? '不明',
                    'started_at' => $attendance->started_at ? Carbon::parse($attendance->started_at)->format('H:i') : '',
                    'ended_at' => $attendance->ended_at ? Carbon::parse($attendance->ended_at)->format('H:i') : '',
                    'break_time' => $breakMinutes ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '',
                    'work_time' => $workMinutes ? sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60) : '',
                    'correction_requested' => false,
                    'correction_status' => null,
                    'correction_breaks' => [],
                    'correction_break_total' => '',
                ];
            }
        });
      
        return view('admin.attendance.daily', [
            'attendances' => $attendancesFormatted,
            'date' => $date,
        ]);
    }

    public function update(AttendanceRequest $request, $id)
    {

        $attendance = Attendance::findOrFail($id);
        $admin = auth()->guard('admin')->user(); // 管理者用    
        $requesterType = 'admin';

        $attendance = Attendance::find($id);
        if (!$attendance) {
            abort(404, 'Attendance not found.');
        }

        $start = \Carbon\Carbon::createFromFormat('H:i', $request->started_at);
        $end = \Carbon\Carbon::createFromFormat('H:i', $request->ended_at);
        $workDate = \Carbon\Carbon::parse($attendance->work_date);


    
        $correctionRequest = CorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => auth()->id(),
            'requester_type' => $requesterType,  
            'started_at' => $workDate->copy()->setTimeFrom($start),
            'ended_at' => $workDate->copy()->setTimeFrom($end),
            'note' => $request->input('note'),
            'status' => 'ended',
            'approval_status' => 'pending', // 未承認の状態
        ]);

     
        foreach ($request->input('breaks', []) as $index => $break) {
            $breakStart = isset($break['break_started_at']) ? \Carbon\Carbon::createFromFormat('H:i', $break['break_started_at']) : null;
            $breakEnd   = isset($break['break_ended_at']) ? \Carbon\Carbon::createFromFormat('H:i', $break['break_ended_at']) : null;

            if ($breakStart && $breakEnd) {
                if ($breakStart->lt($start) || $breakEnd->gt($end)) {
                    return back()->withErrors([
                        "breaks.$index.break_started_at" => '休憩時間が勤務時間外です。',
                    ])->withInput();
                }

                $correctionRequest->breaks()->create([
                    'break_started_at' => $workDate->copy()->setTimeFrom($breakStart),
                    'break_ended_at'   => $workDate->copy()->setTimeFrom($breakEnd),
                ]);
            }
        }

        return redirect()->route('admin.attendance.list', $attendance->id);
    }

    public function show($id)
    {

    
        if (auth('admin')->check() && !auth('web')->check()) {
            // 管理者処理
            $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);

            $correctionRequest = CorrectionRequest::with('correctionBreaks')
                ->where('attendance_id', $attendance->id)
                ->latest()
                ->first();

            return view('admin.attendance.show', compact('attendance', 'correctionRequest'));
        } elseif (auth('web')->check() && !auth('admin')->check()) {
            // 一般ユーザー処理
            $user = auth('web')->user();

            $attendance = Attendance::with(['user', 'breakTimes'])
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $correctionRequest = CorrectionRequest::with('correctionBreaks')
                ->where('attendance_id', $attendance->id)
                ->latest()
                ->first();

            return view('attendance.show', compact('attendance', 'correctionRequest'));
        } else {
            logger()->warning('認証状態異常', [
                'admin_check' => auth('admin')->check(),
                'web_check' => auth('web')->check(),
                'admin_id' => auth('admin')->id(),
                'web_id' => auth('web')->id(),
            ]);
            abort(403, 'Unauthorized');
        }
    }
}
