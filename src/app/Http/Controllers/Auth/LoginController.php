<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // 管理者がログインしていたらログアウトさせる
            auth('admin')->logout();

            // メール認証されていなければ認証ページへ
            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            // 元のページにリダイレクト
            return redirect()->intended('attendance');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->withInput($request->except('password'));
    }


    public function logout()
    {
        Auth::logout();

        // セッションを無効化したい場合（任意）
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login'); // トップページにリダイレクト
    }

    
}
