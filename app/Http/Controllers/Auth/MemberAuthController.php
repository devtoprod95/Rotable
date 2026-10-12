<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EventRound;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MemberAuthController extends Controller
{
    /**
     * 입장/등록 가능한 회차 목록을 셀렉트박스 옵션 형태로 반환.
     * 진행중(S) 상태의 회차만 노출한다.
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function roundOptions(): array
    {
        return EventRound::query()
            ->where('status', EventRound::STATUS_STARTED)
            ->orderBy('event_time')
            ->get()
            ->map(fn (EventRound $round): array => [
                'id'    => $round->id,
                'label' => $round->label,
            ])
            ->all();
    }

    /**
     * 핸드폰번호에서 하이픈/공백 등 숫자가 아닌 문자를 모두 제거한다.
     */
    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    /**
     * 입장등록(회원가입) 화면.
     */
    public function showRegister(): InertiaResponse
    {
        return Inertia::render('Auth/Register', [
            'rounds' => $this->roundOptions(),
        ]);
    }

    /**
     * 입장등록 처리.
     */
    public function register(Request $request): RedirectResponse
    {
        // 하이픈 등 비숫자 제거 후 검증한다. (예: 010-1234-5678 → 01012345678)
        $request->merge(['phone' => $this->normalizePhone($request->input('phone'))]);

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:event_rounds,id'],
            'phone'    => [
                'required',
                'string',
                'regex:/^010\d{8}$/',
                Rule::unique('members', 'phone')->where(
                    fn ($query) => $query->where('event_id', $request->input('event_id'))
                ),
            ],
            'name'     => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:4'],
        ], [
            'event_id.required'  => '회차를 선택해주세요.',
            'event_id.exists'    => '선택한 회차가 올바르지 않습니다.',
            'phone.required'     => '핸드폰번호를 입력해주세요.',
            'phone.regex'        => '핸드폰번호는 010으로 시작하는 11자리 숫자여야 합니다.',
            'phone.unique'       => '해당 회차에 이미 등록된 핸드폰번호입니다.',
            'name.required'      => '이름을 입력해주세요.',
            'password.required'  => '비밀번호를 입력해주세요.',
            'password.min'       => '비밀번호는 최소 4자 이상이어야 합니다.',
        ]);

        $member = Member::create([
            'event_id' => $validated['event_id'],
            'phone'    => $validated['phone'],
            'name'     => $validated['name'],
            'password' => $validated['password'],
        ]);

        Auth::guard('web')->login($member);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    /**
     * 입장(로그인) 화면.
     */
    public function showLogin(): InertiaResponse
    {
        return Inertia::render('Auth/Login', [
            'rounds' => $this->roundOptions(),
        ]);
    }

    /**
     * 입장 처리. 회차 + 핸드폰번호 + 비밀번호로 인증한다.
     * (핸드폰번호는 회차별로 중복될 수 있으므로 회차가 인증 조건에 포함된다.)
     */
    public function login(Request $request): RedirectResponse
    {
        // 하이픈 등 비숫자 제거 후 검증/인증한다.
        $request->merge(['phone' => $this->normalizePhone($request->input('phone'))]);

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:event_rounds,id'],
            'phone'    => ['required', 'string', 'regex:/^010\d{8}$/'],
            'password' => ['required', 'string'],
        ], [
            'event_id.required' => '회차를 선택해주세요.',
            'event_id.exists'   => '선택한 회차가 올바르지 않습니다.',
            'phone.required'    => '핸드폰번호를 입력해주세요.',
            'phone.regex'       => '핸드폰번호는 010으로 시작하는 11자리 숫자여야 합니다.',
            'password.required' => '비밀번호를 입력해주세요.',
        ]);

        // 비밀번호를 평문 저장하므로 Hash 기반 attempt() 대신 직접 비교한다.
        $member = Member::query()
            ->where('event_id', $validated['event_id'])
            ->where('phone', $validated['phone'])
            ->first();

        if ($member === null || ! hash_equals($member->password, $validated['password'])) {
            throw ValidationException::withMessages([
                'phone' => '회차, 핸드폰번호 또는 비밀번호가 일치하지 않습니다.',
            ]);
        }

        Auth::guard('web')->login($member);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * 로그아웃 처리.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
