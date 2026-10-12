<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MemberPageController extends Controller
{
    /**
     * 루트(/) 진입점.
     * - 비로그인: 로그인 페이지로 이동
     * - 로그인: 리스트(home)로 이동
     */
    public function landing(): RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('home');
        }

        return redirect()->route('member.login');
    }

    /**
     * 로그인한 유저 메인 홈 (리스트 탭).
     */
    public function home(): InertiaResponse
    {
        return Inertia::render('Home');
    }

    /**
     * 채팅함 탭.
     */
    public function chats(): InertiaResponse
    {
        return Inertia::render('Chats');
    }

    /**
     * 투표하기 탭.
     */
    public function votes(): InertiaResponse
    {
        return Inertia::render('Votes');
    }

    /**
     * 요청하기 탭.
     */
    public function requests(): InertiaResponse
    {
        return Inertia::render('Requests');
    }
}
