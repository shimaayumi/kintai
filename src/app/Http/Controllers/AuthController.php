<?php

namespace App\Http\Controllers;



use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    // 会員登録画面を表示
    public function showRegister()
    {
        return view('auth.register');
    }

    // 会員登録処理
    public function register(RegisterRequest $request)
    {

        
        // ユーザー登録処理
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 自動ログイン
        Auth::login($user);

        return redirect()->route('auth.login')->with('success', '会員登録が完了しました');
    }

    // ログイン画面を表示
    public function showLogin()
    {
        return view('auth.login');
    }

    // ログイン処理
    public function login(LoginRequest $request)
    {
        if (Auth::attempt($request->only('email', 'password'))) {
            return redirect('/')->with('success', 'ログインしました');
        }

        return back()->withErrors(['email' => 'メールアドレスまたはパスワードが間違っています']);
    }

    // ログアウト処理
    public function logout()
    {
        Auth::logout();
        return redirect('/')->with('success', 'ログアウトしました');
    }
}
