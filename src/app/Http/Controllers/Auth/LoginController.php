<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

            // メール認証されていなければ認証ページへ
            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            // 元のページにリダイレクト
            return redirect()->intended('/');
        }

        return back()->withErrors(['email' => 'ログイン情報が正しくありません。']);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('index');
    }
   
}

