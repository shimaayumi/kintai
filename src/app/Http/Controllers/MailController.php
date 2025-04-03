<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public function sendTestEmail()
    {
        Mail::raw('テストメールです。', function ($message) {
            $message->to('shimaayumi0203@gmail.com')->subject('テストメール');
        });

        return 'メールが送信されました';
    }
}