<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
\Illuminate\Support\Facades\DB::enableQueryLog();

use Illuminate\Support\Facades\DB;

class StampCorrectionRequestController extends Controller
{
    public function index()
    {
        $monthStr = request()->input('month') ?? now()->format('Y-m');
        $month = Carbon::createFromFormat('Y-m', $monthStr);
        $dates = CarbonPeriod::create($month->startOfMonth(), $month->endOfMonth())->toArray();
        $routeName = 'stamp_correction_request.index';

        $adminCheck = auth('admin')->check();
        $webCheck = auth('web')->check();

        // 管理者ログインのみの場合
        if ($adminCheck && !$webCheck) {
            $tab = request()->input('tab', 'pending');

            $pendingRequests = CorrectionRequest::with('user')
                ->where('approval_status', 'pending')
                ->latest()
                ->take(1)
                ->get();

            $approvedRequests = CorrectionRequest::with('user')
                ->where('approval_status', 'approved')
                ->latest()
                ->take(1)
                ->get();

            return view('admin.request.list', compact('pendingRequests', 'approvedRequests', 'month', 'dates', 'tab', 'routeName'));
        }

        // 一般ユーザーのみログインの場合
        if ($webCheck && !$adminCheck) {
            $user = auth('web')->user();
            $status = request()->input('status', 'pending');

            $requests = CorrectionRequest::where('user_id', $user->id)
                ->where('approval_status', $status)
                ->with(['user', 'attendance'])
                ->latest()
                ->paginate(10);

            return view('request.history', compact('requests', 'status', 'month', 'dates'));
        }

        // 両方ログイン、またはどちらもログインしていない状態は異常として403
        logger()->warning('認証状態異常', [
            'admin_check' => $adminCheck,
            'web_check' => $webCheck,
            'admin_id' => auth('admin')->id(),
            'web_id' => auth('web')->id(),
        ]);
        abort(403, 'Unauthorized');
    }

    public function approve(Request $request, $attendance_correct_request)
    {
        $correctionRequest = CorrectionRequest::findOrFail($attendance_correct_request);

        \DB::transaction(function () use ($correctionRequest) {
            $correctionRequest->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]);

            $attendance = $correctionRequest->attendance;
            if ($attendance) {
                $attendance->update([
                    'started_at' => $correctionRequest->started_at,
                    'ended_at' => $correctionRequest->ended_at,
                    'break_started_at' => $correctionRequest->break_started_at,
                    'break_ended_at' => $correctionRequest->break_ended_at,
                    'note' => $correctionRequest->note,
                ]);
            }
        });

        return redirect()->back()->with('success', '承認しました');
    }

    public function show($id)
    {
        $correctionRequest = CorrectionRequest::with('attendance.breakTimes')->find($id);
        if (!$correctionRequest) {
            abort(404, 'データが見つかりません');
        }

        return view('admin.request.show', [
            'correctionRequest' => $correctionRequest,
        ]);
    }


}
