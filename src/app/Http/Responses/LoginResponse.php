<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        
        if ($request->user()->hasVerifiedEmail()) {
            
            return redirect('/attendance');
        }

        // メール認証前の場合
        return redirect()->route('verification.notice');
    }
}