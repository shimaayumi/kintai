<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request; 

class AuthController extends Controller
{

 
    // 会員登録画面を表示
    public function showRegister()
    {
        return view('auth.register');
    }

  
    // 会員登録処理
    public function create(Request $request)
    {
        // バリデーション
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // トランザクションで処理
        try {
            // ユーザーを作成
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // メール認証イベントを発火
            event(new Registered($user));

            // メール送信後、認証ページにリダイレクト
            return redirect()->route('verification.notice')->with('success', '会員登録が完了しました。確認メールをご確認ください。');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => '登録処理中にエラーが発生しました。']);
        }
    }


    // ログイン画面を表示
    public function showLogin()
    {
        return view('auth.login');
    }

    // ログイン処理
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            return redirect()->route('verification.notice');
        }

        return back()->withErrors(['email' => 'ログイン情報が登録されていません']);
    }

    // ログアウト処理
    public function logout()
    {
        Auth::logout();
        return redirect('/')->with('success', 'ログアウトしました');
    }
}
