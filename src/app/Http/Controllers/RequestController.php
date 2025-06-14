<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AttendanceRequest;
use Carbon\Carbon;
use App\Models\Attendance;

use Illuminate\Http\Request;



class RequestController extends Controller
{
    public function history(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'ログインしてください。');
        }

        $status = $request->input('status', CorrectionRequest::APPROVAL_PENDING);

        $requests = CorrectionRequest::where('user_id', $user->id)
            ->where('approval_status', $status)
            ->with(['user', 'attendance'])
            ->orderBy('created_at', 'desc')
            ->get();

        // ここでrouteNameをビューに渡す
        return view('request.history', [
            'requests' => $requests,
            'status' => $status,
         
        ]);
    }



    public function store(Request $request)
    {
        $attendance = // 勤怠データを取得する処理

            // 申請本体を保存
            $correctionRequest = CorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id' => auth()->id(),
                'requester_type' => CorrectionRequest::REQUESTER_USER, // 'user'の定数や文字列
                'status' => CorrectionRequest::STATUS_OFF,             // 'off'の定数や文字列
                'approval_status' => CorrectionRequest::APPROVAL_PENDING, // 'pending'など
                'started_at' => Carbon::parse($request->started_at), // 例：2025-06-12 09:00:00 の形にする
                'ended_at' => Carbon::parse($request->ended_at),
                'note' => $request->note,
            ]);

        // 休憩時間を複数保存（複数休憩がある場合）
        foreach ($request->input('breaks', []) as $break) {
            if (!empty($break['break_started_at']) && !empty($break['break_ended_at'])) {
                CorrectionBreak::create([
                    'correction_request_id' => $correctionRequest->id,
                    // time型なので時刻だけ（例：'12:00:00'）に変換
                    'break_started_at' => Carbon::parse($break['break_started_at'])->format('H:i:s'),
                    'break_ended_at' => Carbon::parse($break['break_ended_at'])->format('H:i:s'),
                ]);
            }
        }

        return redirect()->back()->with('success', '修正申請を保存しました。');
    }



    public function update(AttendanceRequest $request, $id)
    {
        
        $attendance = Attendance::findOrFail($id);

        Attendance::where('id', $attendance->id)->update(['approval_status' => 'pending']);

        // 承認待ちの修正申請が既に存在するかチェック
        $alreadyRequested = CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('approval_status', CorrectionRequest::APPROVAL_PENDING)
            ->exists();

        if ($alreadyRequested) {
            return redirect()->back()->with('error', 'すでに承認待ちの修正申請があります。');
        }

        // 勤怠記録の基準日（元の出勤時間の日付）を使う
        $baseDate = Carbon::parse($attendance->started_at)->format('Y-m-d');

        // 出勤・退勤の希望時刻を Carbon に変換
        $startedAt = $request->started_at ? Carbon::parse("$baseDate {$request->started_at}") : null;
        $endedAt = $request->ended_at ? Carbon::parse("$baseDate {$request->ended_at}") : null;


        $validated = $request->validated();

        // CorrectionRequest を保存（休憩時間は別テーブルにするため削除）
        $correctionRequest = CorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => auth()->id(),
            'requester_type' => CorrectionRequest::REQUESTER_USER,
            'status' => CorrectionRequest::STATUS_OFF,
            'approval_status' => CorrectionRequest::APPROVAL_PENDING,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'note' => $request->note,
        ]);

        // 複数休憩時間を CorrectionBreak として保存
        foreach ($request->input('breaks', []) as $break) {
            $breakStartedAt = $break['break_started_at'] ?? null;
            $breakEndedAt = $break['break_ended_at'] ?? null;

            \App\Models\CorrectionBreak::create([
                'correction_request_id' => $correctionRequest->id,
                'break_started_at' => $breakStartedAt ? Carbon::parse("$baseDate {$breakStartedAt}") : null,
                'break_ended_at' => $breakEndedAt ? Carbon::parse("$baseDate {$breakEndedAt}") : null,
            ]);
        }

        return redirect('/stamp_correction_request/list')->with('success', '修正申請を送信しました。');
    }
}





   
