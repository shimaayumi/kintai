<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminLoginRequest;

class AdminAuthController extends Controller
{
    // ログインフォーム表示
    public function showLogin()
    {
        return view('admin.login');
    }

    // ログイン処理
    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        // 一般ユーザーとしてログインしていたらログアウトさせる
        Auth::logout();
        if (Auth::guard('admin')->attempt($credentials)) {
            // ログイン成功時は、勤怠一覧画面へリダイレクト
            return redirect()->route('admin.attendance.list');
        }

        // 認証失敗：ログイン情報が登録されていません
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->withInput($request->except('password'));
    }


    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }

    public function attendanceList()
    {
        return view('admin.attendance.daily');
    }

    
}
