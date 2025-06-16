<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use App\Models\CorrectionRequest;
use App\Http\Requests\AttendanceRequest;




class AttendanceController extends Controller
{

    public function __construct()
    {
        App::setLocale('ja');
        Carbon::setLocale('ja');
    }

    // 勤怠トップ画面表示（GET /attendance）
        public function index(Request $request)
        {
            $user = Auth::user();

            // クエリパラメータからwork_dateを取得。なければ今日の日付を使う
            $workDate = $request->query('work_date', today()->toDateString());

            // 表示用の日付文字列（例: 2025年5月30日(金)）
            $date = Carbon::parse($workDate)->isoFormat('YYYY年M月D日(dd)');

            // 表示用の時刻は現在時刻のまま
            $time = Carbon::now()->format('H:i');

            // 指定された日付の勤怠データを取得
            $attendance = Attendance::where('user_id', $user->id)
                ->where('work_date', $workDate)
                ->latest()
                ->first();

            $status = $this->getStatus($attendance);

            return view('attendance.index', compact('attendance', 'date', 'time', 'status'));
        }

    // POSTで各種打刻処理を振り分ける
    public function handleAction(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'startWork' => $this->startWork($request),
            'afterWork' => $this->afterWork($request),
            'startBreak' => $this->startBreak($request),
            'endBreak' => $this->endBreak($request),
            default => abort(400, '不正なアクションです。'),
        };
    }

    // 出勤処理
    protected function startWork(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        // 既に勤務中のレコードがあれば処理を中断
        $alreadyWorking = Attendance::where('user_id', $user->id)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->first();

        if ($alreadyWorking) {
            return redirect()->route('attendance.index')->with('success', 'すでに出勤打刻済みです。');
        }

        // 今日の勤務記録を取得、なければ新規作成
        $attendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
        ]);

        $attendance->started_at = $now;
        $attendance->status = 'working';
        $attendance->save();

        return redirect()->route('attendance.index')->with('success', '出勤を打刻しました。');
    }

    // 退勤処理
    protected function afterWork(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $now->toDateString())
            ->whereNull('ended_at')
            ->latest()
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.index')->with('error', '出勤記録が見つかりません。');
        }

        $attendance->ended_at = $now;
        $attendance->status = 'ended';

        if ($attendance->save()) {
            return redirect()->route('attendance.index')->with('success', '退勤を打刻しました。');
        } else {
            return redirect()->route('attendance.index')->with('error', '退勤の打刻に失敗しました。');
        }
    }

    // 休憩開始
    protected function startBreak(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $now->toDateString())
            ->whereNull('ended_at')
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.index')->with('error', '出勤記録が見つかりません。');
        }

        $activeBreak = $attendance->breakTimes()->whereNull('break_ended_at')->first();
        if ($activeBreak) {
            return redirect()->route('attendance.index')->with('error', 'すでに休憩中です。');
        }

        $attendance->breakTimes()->create([
            'break_started_at' => $now,
        ]);

        $attendance->status = 'on_break';
        $attendance->save();

        return redirect()->route('attendance.index')->with('success', '休憩を開始しました。');
    }

    // 休憩終了
    protected function endBreak(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $now->toDateString())
            ->whereNull('ended_at')
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.index')->with('error', '出勤記録が見つかりません。');
        }

        $activeBreak = $attendance->breakTimes()->whereNull('break_ended_at')->latest()->first();

        if (!$activeBreak) {
            return redirect()->route('attendance.index')->with('error', '休憩中の記録が見つかりません。');
        }

        $activeBreak->update([
            'break_ended_at' => $now,
        ]);

        $attendance->status = 'working';
        $attendance->save();

        return redirect()->route('attendance.index')->with('success', '休憩を終了しました。');
    }



    public function list(Request $request)
    {
        $user = auth()->user();

        $monthInput = $request->input('month');

        $month = $monthInput
            ? Carbon::createFromFormat('Y-m', $monthInput)
            : Carbon::now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        // 全日付の配列を生成
        $dates = [];
        for ($dateIter = $startOfMonth->copy(); $dateIter->lte($endOfMonth); $dateIter->addDay()) {
            $dates[] = $dateIter->copy();
        }

        // DBから勤怠データ取得
        $attendancesRaw = Attendance::with(['breakTimes', 'correctionRequest.correctionBreaks'])
            ->where('user_id', $user->id)
            ->whereBetween('started_at', [
                $startOfMonth->format('Y-m-d 00:00:00'),
                $endOfMonth->format('Y-m-d 23:59:59'),
            ])
            ->get();

        $attendances = [];
        $totalWorkMinutes = 0;

        // 初期化: すべての日付分の配列を作っておく（空データ）
        foreach ($dates as $date) {
            $key = $date->format('Y-m-d');
            $attendances[$key] = [
                'id' => null,
                'date' => $date->format('m/d') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek] . ')',
                'started_at' => '',
                'ended_at' => '',
                'break' => '',
                'work_time' => '',
            ];
        }

        // 勤怠がある日付だけ上書き
        foreach ($attendancesRaw as $attendance) {
            $dateKey = Carbon::parse($attendance->started_at)->format('Y-m-d');
            $correction = $attendance->correctionRequest;
            \Log::info("Attendance ID: {$attendance->id}");
            \Log::info("Correction: ", $correction ? $correction->toArray() : ['none']);
            $start = $correction && $correction->started_at
                ? Carbon::parse($correction->started_at)
                : ($attendance->started_at ? Carbon::parse($attendance->started_at) : null);

            $end = $correction && $correction->ended_at
                ? Carbon::parse($correction->ended_at)
                : ($attendance->ended_at ? Carbon::parse($attendance->ended_at) : null);

            $breaks = $correction && $correction->correctionBreaks->isNotEmpty()
                ? $correction->correctionBreaks
                : $attendance->breakTimes;

            $totalBreakMinutes = 0;
            foreach ($breaks as $break) {
                if ($break->break_started_at && $break->break_ended_at) {
                    $breakStart = Carbon::parse($break->break_started_at);
                    $breakEnd = Carbon::parse($break->break_ended_at);
                    $totalBreakMinutes += $breakStart->diffInMinutes($breakEnd);
                }
            }

            $workMinutes = 0;
            if ($start && $end) {
                $workMinutes = $start->diffInMinutes($end) - $totalBreakMinutes;
                $totalWorkMinutes += max(0, $workMinutes);
            }

            // 該当日を上書き
            $attendances[$dateKey] = [
                'id' => $attendance->id,
                'date' => $start ? $start->format('m/d') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$start->dayOfWeek] . ')' : '',
                'started_at' => $start ? $start->format('H:i') : '',
                'ended_at' => $end ? $end->format('H:i') : '',
                'break' => $totalBreakMinutes > 0 ? sprintf('%d:%02d', intdiv($totalBreakMinutes, 60), $totalBreakMinutes % 60) : '',
                'work_time' => $workMinutes > 0 ? sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60) : '',
            ];
        }

        $totalWorkTimeStr = sprintf('%d時間%02d分', intdiv($totalWorkMinutes, 60), $totalWorkMinutes % 60);
        $date = $request->input('date') ?? now()->format('Y-m-d');

        return view('attendance.list', [
            'dates' => $dates,
            'attendances' => $attendances,
            'month' => $month,
            'totalWorkTime' => $totalWorkTimeStr,
            'date' => $date,
        ]);
    }

    

    //ステータスの更新
    public function approve(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // すでに承認待ち or 承認済みなら申請不可
        if (in_array($attendance->approval_status, ['pending', 'approved'])) {
            return redirect()->back()->with('error', 'すでに申請済み、または承認済みです。');
        }

        $attendance->approval_status = '承認待ち';
        $attendance->save();

        return redirect()->route('attendance.show', $attendance->id)->with('success', '申請が完了しました。');
    }



    // 勤怠状態を判定
    private function getStatus($attendance)
    {
        if (!$attendance) return 'off';

        return match ($attendance->status) {
            'off', '勤務外' => 'off',
            'working', '出勤中' => 'working',
            'on_break', '休憩中' => 'on_break',
            'ended', '退勤済' => 'ended',
            default => 'off',
        };
    }

    
}