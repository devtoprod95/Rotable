<?php

namespace App\Http\Middleware;

use App\Models\EventRound;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 종료된 행사의 회원 접속 가능 기간(행사 일시 + EventRound::MEMBER_ACCESS_DAYS 일)이 지나면 접속을 막는다.
 * - 이미 로그인해 있던 회원도 다음 요청에서 로그아웃시키고 로그인 화면으로 보낸다.
 * - 관리자가 "회원으로 로그인"한 세션(admin_impersonating)은 확인용이므로 제한하지 않는다.
 */
class EnsureMemberAccessAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user('web');

        if ($member === null || $request->session()->get('admin_impersonating') === true) {
            return $next($request);
        }

        $event = $request->attributes->get('member_event') ?? EventRound::find($member->event_id);

        if ($event?->isMemberAccessExpired()) {
            // web(회원) guard 만 로그아웃한다. (관리자 로그인은 유지)
            Auth::guard('web')->logout();
            $request->session()->regenerate();

            return redirect()->route('member.login')
                ->with('error', '접속 가능 기간(행사 후 ' . EventRound::MEMBER_ACCESS_DAYS . '일)이 지나 더 이상 접속할 수 없어요.');
        }

        return $next($request);
    }
}
