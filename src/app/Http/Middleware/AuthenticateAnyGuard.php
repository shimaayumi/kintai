<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateAnyGuard
{
    /**
     * Handle an incoming request.
     * web または admin のどちらかのガードで認証されていれば通す
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard); // ← これで auth() はその guard を使うようになる
                return $next($request);
            }
        }

        // どのガードも認証されていなければログイン画面へリダイレクトなど
        return redirect()->route('login'); // または403エラー等に変更可能
    }
}
