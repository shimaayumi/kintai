<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail;

class VerificationController extends Controller
{
    /**
     * メール認証の誘導画面を表示
     */
    public function show()
    {
        return view('auth.verify-email');
    }

    /**
     * 認証メールの再送信
     */
    public function resend(Request $request)
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('home')->with('status', '既にメール認証が完了しています');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', '認証メールを再送信しました');
    }
}
