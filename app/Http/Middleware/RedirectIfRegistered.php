<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 이미 입장등록(로그인)된 회원이 입장/입장등록 화면에 접근하면 메인(홈)으로 보낸다.
 * 단, 특정 예외 이름의 회원은 그대로 접근을 허용한다. (디버깅/운영 편의용)
 */
class RedirectIfRegistered
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = Auth::guard('web')->user();

        if ($member instanceof Member) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
