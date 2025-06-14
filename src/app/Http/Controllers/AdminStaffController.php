<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;


class AdminStaffController extends Controller
{
    public function showList()
    {
        $users = User::select('id', 'name', 'email')->get();
        return view('admin.staff.list', compact('users'));
    }

    public function monthly(Request $request, $id)
         {
        $user = User::findOrFail($id);
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $date = \Carbon\Carbon::createFromDate($year, $month, 1);

       
        $attendances = Attendance::with('breakTimes') // ← Eager load
            ->where('user_id', $user->id)
            ->whereYear('started_at', $year)
            ->whereMonth('started_at', $month)
            ->orderBy('started_at')
            ->get()
            ->keyBy(function ($attendance) {
                return \Carbon\Carbon::parse($attendance->started_at)->toDateString();
            });

        // 月の日数分ループして、「06/01(木)」形式の文字列を配列で作成
        $daysWithWeekday = [];
        for ($d = 1; $d <= $date->daysInMonth; $d++) {
            $day = $date->copy()->day($d);
            // 曜日を日本語に変換
            $weekday = ['日', '月', '火', '水', '木', '金', '土'][$day->dayOfWeek];
            $daysWithWeekday[$day->toDateString()] = $day->format('m/d') . "({$weekday})";
        }

        return view('admin.staff.monthly', [
            'user' => $user,
            'date' => $date,
            'attendances' => $attendances,
            'daysWithWeekday' => $daysWithWeekday, // 追加
        ]);
    }



    public function exportCsv(Request $request, User $user)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('started_at', $year)
            ->whereMonth('started_at', $month)
            ->with('breaks') // ← 休憩時間を取得
            ->orderBy('started_at')
            ->get();

        $csvData = [];
        $csvData[] = ['日付', '出勤時間', '退勤時間', '休憩時間', '勤務時間', '備考'];

        foreach ($attendances as $attendance) {
            $start = $attendance->started_at ? Carbon::parse($attendance->started_at)->format('H:i') : '';
            $end = $attendance->ended_at ? Carbon::parse($attendance->ended_at)->format('H:i') : '';

            // 勤務時間（単純な差）
            $workMinutes = '';
            if ($attendance->started_at && $attendance->ended_at) {
                $workMinutesRaw = Carbon::parse($attendance->started_at)->diffInMinutes(Carbon::parse($attendance->ended_at));
                $workMinutes = sprintf('%02d:%02d', floor($workMinutesRaw / 60), $workMinutesRaw % 60);
            }

            // 休憩時間（breaks テーブルから合計）
            $breakTotalMinutes = 0;
            foreach ($attendance->breaks as $break) {
                if ($break->break_started_at && $break->break_ended_at) {
                    $breakTotalMinutes += Carbon::parse($break->break_started_at)->diffInMinutes(Carbon::parse($break->break_ended_at));
                }
            }
            $breakTime = $breakTotalMinutes ? sprintf('%02d:%02d', floor($breakTotalMinutes / 60), $breakTotalMinutes % 60) : '';

            $csvData[] = [
                Carbon::parse($attendance->work_date)->format('Y-m-d'),
                $start,
                $end,
                $breakTime,
                $workMinutes,
                $attendance->note ?? '',
            ];
        }

        $filename = "{$user->name}_{$year}_{$month}_勤怠一覧.csv";

        return response()->streamDownload(function () use ($csvData) {
            $stream = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($stream, $row);
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}