<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationSeat;
use App\Models\RotationTable;
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
     * - $statuses 에 해당하는 상태의 회차를 노출한다.
     * - $includeRecentClosed=true 이면 종료(X) 회차 중 event_time이 최근 2일 이내인 것도 추가로 노출한다.
     *
     * @param  array<int, string>  $statuses
     * @return array<int, array{id: int, label: string}>
     */
    private function roundOptions(array $statuses, bool $includeRecentClosed = false): array
    {
        return EventRound::query()
            ->where(function ($query) use ($statuses, $includeRecentClosed) {
                $query->whereIn('status', $statuses);

                if ($includeRecentClosed) {
                    $query->orWhere(function ($q) {
                        $q->where('status', EventRound::STATUS_CLOSED)
                            ->where('event_time', '>=', now()->subDays(2));
                    });
                }
            })
            ->orderBy('event_time', 'desc')
            ->get()
            ->map(fn (EventRound $round): array => [
                'id'    => $round->id,
                'label' => $round->label,
            ])
            ->all();
    }

    /**
     * 핸드폰 번호에서 하이픈/공백 등 숫자가 아닌 문자를 모두 제거한다.
     */
    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    /** 출생연도 선택 하한 (고정) */
    private const BIRTH_YEAR_MIN = 1980;

    /** 성인 기준 나이 (만 나이) */
    private const ADULT_AGE = 19;

    /**
     * 선택 가능한 출생연도의 상한(가장 최근 연도)을 반환한다.
     * 현재 연도 기준 성인(만 19세)이 되는 출생연도까지만 허용한다.
     */
    private function birthYearMax(): int
    {
        return (int) now()->year - self::ADULT_AGE;
    }

    /**
     * 출생연도 셀렉트박스 옵션 목록. 최근 연도가 위로 오도록 내림차순 정렬한다.
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function birthYearOptions(): array
    {
        $max = $this->birthYearMax();

        return collect(range($max, self::BIRTH_YEAR_MIN))
            ->map(fn (int $year): array => [
                'id'    => $year,
                'label' => $year . '년생',
            ])
            ->all();
    }

    /**
     * 입장등록(회원가입) 화면.
     */
    public function showRegister(): InertiaResponse
    {
        return Inertia::render('Auth/Register', [
            'rounds'         => $this->roundOptions([EventRound::STATUS_PENDING, EventRound::STATUS_STARTED, EventRound::STATUS_VOTING]),
            'birthYears'     => $this->birthYearOptions(),
            'tablesByEvent'  => $this->tablesByEvent(),
        ]);
    }

    /**
     * 입장/등록 가능한 회차별 테이블(자리) 옵션 맵.
     * 참가자가 입장등록 시 자기 자리를 직접 고르도록 제공한다.
     *
     * 1:1 소개팅(one)은 테이블당 남/여 각 1명이 원칙이므로, 각 테이블에
     * "이미 남/여가 앉았는지"(taken_male / taken_female)와 행사 타입(is_one)을 함께 내려
     * 프론트에서 같은 성별이 찬 테이블을 선택지에서 제외할 수 있게 한다.
     *
     * @return array<int, array<int, array{id: int, label: string, is_one: bool, taken_male: bool, taken_female: bool}>>
     */
    private function tablesByEvent(): array
    {
        // 노출 대상 행사(대기/진행/투표중)만 대상으로 한다.
        $events = EventRound::query()
            ->whereIn('status', [EventRound::STATUS_PENDING, EventRound::STATUS_STARTED, EventRound::STATUS_VOTING])
            ->get()
            ->keyBy('id');

        // 각 테이블에 이미 앉은 성별 집합. rotation_seats + members.gender 로 집계.
        // 결과: [rotation_table_id => ['M' => bool, 'F' => bool]]
        $occupancy = RotationSeat::query()
            ->with('member:id,gender')
            ->get()
            ->groupBy('rotation_table_id')
            ->map(function ($seats) {
                return [
                    'M' => $seats->contains(fn ($s) => $s->member?->gender === Member::GENDER_MALE),
                    'F' => $seats->contains(fn ($s) => $s->member?->gender === Member::GENDER_FEMALE),
                ];
            });

        return RotationTable::query()
            ->orderBy('event_id')
            ->orderBy('table_no')
            ->get()
            ->groupBy('event_id')
            ->map(fn ($tables, $eventId) => $tables->map(function (RotationTable $t) use ($events, $occupancy): array {
                $isOne = ($events[$t->event_id] ?? null)?->isOneToOne() ?? false;
                $occ   = $occupancy[$t->id] ?? ['M' => false, 'F' => false];

                return [
                    'id'           => $t->id,
                    'label'        => $t->table_no . '번 테이블',
                    'is_one'       => $isOne,
                    'taken_male'   => (bool) $occ['M'],
                    'taken_female' => (bool) $occ['F'],
                ];
            })->values()->all())
            ->all();
    }

    /**
     * 입장등록 처리.
     */
    public function register(Request $request): RedirectResponse
    {
        // 하이픈 등 비숫자 제거 후 검증한다. (예: 010-1234-5678 → 01012345678)
        $request->merge(['phone' => $this->normalizePhone($request->input('phone'))]);

        $rules = [
            'event_id' => ['required', 'integer', 'exists:event_rounds,id'],
            'phone'    => [
                'required',
                'string',
                'regex:/^010\d{8}$/',
                Rule::unique('members', 'phone')->where(
                    fn ($query) => $query->where('event_id', $request->input('event_id'))
                ),
            ],
            'name'       => ['required', 'string', 'max:50'],
            'password'   => ['required', 'string', 'size:4'],
            'gender'     => ['required', Rule::in([Member::GENDER_MALE, Member::GENDER_FEMALE])],
            'birth_year' => ['required', 'integer', 'between:' . self::BIRTH_YEAR_MIN . ',' . $this->birthYearMax()],
            'job'        => ['required', 'string', 'max:50'],
            'height'     => ['required', 'integer', 'between:100,250'],
            'mbti'       => ['required', 'string', 'size:4'],
            'region'     => ['required', 'string', 'max:50'],
            'is_smoker'  => ['required', 'boolean'],
            // 참가자가 직접 고르는 기준(홈) 테이블. 선택한 회차의 테이블이어야 한다.
            'rotation_table_id' => [
                'required',
                'integer',
                Rule::exists('rotation_tables', 'id')->where(
                    fn ($query) => $query->where('event_id', $request->input('event_id'))
                ),
                // 1:1 소개팅은 테이블당 남/여 각 1명이 원칙. 같은 성별이 이미 앉은 테이블은 선택 불가.
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $event = EventRound::find($request->input('event_id'));
                    if ($event === null || ! $event->isOneToOne()) {
                        return; // 1:1 이 아니면 제한 없음.
                    }

                    $gender = $request->input('gender');
                    $exists = RotationSeat::query()
                        ->where('rotation_table_id', $value)
                        ->whereHas('member', fn ($q) => $q->where('gender', $gender))
                        ->exists();

                    if ($exists) {
                        $fail('이미 같은 성별이 앉은 테이블입니다. 다른 테이블을 선택해주세요.');
                    }
                },
            ],
            // 추가 프로필 (모든 유형 공통 필수) — 이상형/장점/취미/TMI
            'ideal_type' => ['required', 'string', 'max:100'],
            'merit'      => ['required', 'string', 'max:100'],
            'hobby'      => ['required', 'string', 'max:100'],
            'tmi'        => ['required', 'string', 'max:100'],
        ];

        $validated = $request->validate($rules, [
            'event_id.required'          => '회차를 선택해주세요.',
            'event_id.exists'            => '선택한 회차가 올바르지 않습니다.',
            'phone.required'             => '핸드폰 번호를 입력해주세요.',
            'phone.regex'                => '핸드폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.',
            'phone.unique'               => '해당 회차에 이미 등록된 핸드폰 번호입니다.',
            'name.required'              => '닉네임을 입력해주세요.',
            'password.required'          => '비밀번호를 입력해주세요.',
            'password.size'              => '비밀번호는 4자리로 입력해주세요.',
            'gender.required'            => '성별을 선택해주세요.',
            'gender.in'                  => '성별 선택이 올바르지 않습니다.',
            'birth_year.required'        => '출생연도를 선택해주세요.',
            'birth_year.between'         => '출생연도 선택이 올바르지 않습니다.',
            'job.required'               => '직업을 입력해주세요.',
            'height.required'            => '키를 입력해주세요.',
            'height.between'             => '키는 100~250cm 사이로 입력해주세요.',
            'mbti.required'              => 'MBTI를 입력해주세요.',
            'mbti.size'                  => 'MBTI는 4글자로 입력해주세요.',
            'region.required'            => '지역/거주지를 입력해주세요.',
            'is_smoker.required'         => '흡연 여부를 선택해주세요.',
            'rotation_table_id.required' => '앉아 계신 테이블을 선택해주세요.',
            'rotation_table_id.exists'   => '선택한 테이블이 올바르지 않습니다.',
            'ideal_type.required'        => '이상형을 입력해주세요.',
            'merit.required'             => '나의 장점을 입력해주세요.',
            'hobby.required'             => '취미/요즘 빠진 것을 입력해주세요.',
            'tmi.required'               => 'TMI를 입력해주세요.',
        ]);

        $member = Member::create([
            'event_id'   => $validated['event_id'],
            'phone'      => $validated['phone'],
            'name'       => $validated['name'],
            'password'   => $validated['password'],
            'gender'     => $validated['gender'],
            'birth_year' => $validated['birth_year'],
            'job'        => $validated['job'],
            'height'     => $validated['height'],
            'mbti'       => strtoupper($validated['mbti']),
            'region'     => $validated['region'],
            'is_smoker'  => $validated['is_smoker'],
            // 추가 프로필 (공통)
            'ideal_type' => $validated['ideal_type'],
            'merit'      => $validated['merit'],
            'hobby'      => $validated['hobby'],
            'tmi'        => $validated['tmi'],
        ]);

        // 참가자가 고른 기준(홈) 좌석을 생성한다.
        // 새 좌석 모델은 current_round 공식으로 턴 자리를 계산하므로,
        // 로테이션이 이미 시작된(S/V) 뒤 중간 입장이어도 이 좌석이 현재 턴부터 자동 반영된다.
        RotationSeat::create([
            'event_id'          => $validated['event_id'],
            'member_id'         => $member->id,
            'rotation_table_id' => $validated['rotation_table_id'],
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
            'rounds' => $this->roundOptions([EventRound::STATUS_STARTED, EventRound::STATUS_PENDING, EventRound::STATUS_VOTING], true),
        ]);
    }

    /**
     * 입장 처리. 회차 + 핸드폰 번호 + 비밀번호로 인증한다.
     * (핸드폰 번호는 회차별로 중복될 수 있으므로 회차가 인증 조건에 포함된다.)
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
            'phone.required'    => '핸드폰 번호를 입력해주세요.',
            'phone.regex'       => '핸드폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.',
            'password.required' => '비밀번호를 입력해주세요.',
        ]);

        // 비밀번호를 평문 저장하므로 Hash 기반 attempt() 대신 직접 비교한다.
        $member = Member::query()
            ->where('event_id', $validated['event_id'])
            ->where('phone', $validated['phone'])
            ->first();

        if ($member === null || ! hash_equals($member->password, $validated['password'])) {
            throw ValidationException::withMessages([
                'phone' => '회차, 핸드폰 번호 또는 비밀번호가 일치하지 않습니다.',
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
        // web(회원) guard만 로그아웃한다.
        // session()->invalidate()는 세션 전체를 날려 admin guard 로그인까지 풀리므로 사용하지 않는다.
        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->route('landing');
    }
}
