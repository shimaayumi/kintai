<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
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

        if ($adminCheck && !$webCheck) {
            $tab = request()->input('tab', 'pending');

            $pendingRequests = CorrectionRequest::with(['user', 'attendance'])
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(id)')
                        ->from('correction_requests')
                        ->groupBy('attendance_id');
                })
                ->where('approval_status', 'pending')
                ->latest()
                ->get();
                
            $approvedRequests = CorrectionRequest::with(['user', 'attendance'])
                ->where('approval_status', CorrectionRequest::APPROVAL_APPROVED)
                ->orderByDesc('created_at')
                ->get();

            return view('admin.request.list', compact(
                'pendingRequests',
                'approvedRequests',
                'month',
                'dates',
                'tab',
                'routeName'
            ));
        }

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
      
    }

    public function approve(Request $request, $id)
    {
        $correctionRequest = CorrectionRequest::findOrFail($id);

        \DB::transaction(function () use ($correctionRequest) {
            $correctionRequest->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'status' => 'ended',
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
        // 勤怠単位で最新の申請を取得
        $correctionRequest = CorrectionRequest::with(['user', 'attendance.breakTimes'])
            ->where('id', $id)
            ->latest('updated_at')
            ->firstOrFail();

      

        return view('admin.request.show', [
            'correctionRequest' => $correctionRequest,
        ]);
    }


}
