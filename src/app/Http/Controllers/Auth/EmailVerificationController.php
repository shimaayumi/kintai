<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;



class EmailVerificationController extends Controller
{
    // メール認証ページを表示
    public function show()
    {
        // 現在認証済みのユーザー情報を取得
        $user = auth()->user();

        // ユーザー情報をビューに渡す
        return view('auth.verify-email', compact('user'));
    }

    // メール認証を確認
    public function verify(Request $request)
    {
        // ユーザーが既に認証されている場合
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('login'); 
        }

        // メールを認証する
        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user())); // 認証イベントを発火
        }

        // 認証後、
        return redirect()->route('login');
    }


    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home'); // 認証済みならリダイレクト
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', '認証メールを再送しました！');
    }

  
}

