<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            // ログインしていなければ管理者ログイン画面へリダイレクト
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}