<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\StampCorrectionRequestController;



// ===========================
// 一般ユーザー認証系
// ===========================
Route::post('/register', [RegisterController::class, 'store'])->name('register');

Route::get('/login', [LoginController::class, 'show'])->name('login.show');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// ===========================
// メール認証
// ===========================
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');
Route::get('/email/verify', [EmailVerificationController::class, 'show'])->middleware('auth')->name('verification.notice');



// ===========================
// 一般ユーザー向け（auth）
// ===========================
Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'handleAction'])->name('attendance.action');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    
    Route::get('/attendance/{id}/pending', [AttendanceController::class, 'pendingShow'])->name('attendance.pending');
    Route::post('/attendance/{id}/approve', [AttendanceController::class, 'approve'])->name('attendance.approve');
    

    Route::post('/attendance/{id}/request', [RequestController::class, 'store'])->name('request.store');
    Route::put('/attendance/{id}', [RequestController::class, 'update'])->name('user.attendance.update');
    
});


Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
->middleware(['auth.any:web,admin']) // 両方認証可
    ->name('attendance.show');

Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])
    ->middleware(['auth.any:web,admin']) // 両方認証可
    ->name('stamp_correction_request.index');


// ✅ 詳細表示 → GET（ブラウザで開くもの）
Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [StampCorrectionRequestController::class, 'show'])
    ->name('admin.stamp_correction_request.show');
// ✅ 承認処理 → POST（ボタンやフォームから送信するもの）
Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [StampCorrectionRequestController::class, 'approve'])
    ->name('admin.stamp_correction_request.approve');



// ===========================
// 管理者向け（auth:admin）
// ===========================
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// 一般ユーザー用
Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {
    Route::put('/attendance/{id}', [AttendanceController::class, 'update'])->name('attendance.update');
});
// 管理者用
Route::middleware(['auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');
});

Route::prefix('admin')->middleware(['auth:admin', 'admin'])->name('admin.')->group(function () {
    //勤怠一覧画面（管理者）  
    Route::get('/attendance/list', [AdminAttendanceController::class, 'dailyAll'])->name('attendance.list');


    //スタッフ一覧画面（管理者）
    Route::get('/staff/list', [AdminStaffController::class, 'showList'])->name('staff.list');
    //スタッフ別勤怠一覧画面（管理者）
    Route::get('/attendance/staff/{id}', [AdminStaffController::class, 'monthly'])->name('staff.monthly');
    Route::get('/attendance/staff/{user}/csv', [AdminStaffController::class, 'exportCsv'])->name('staff.exportCsv');
});
