<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\Member;
use App\Services\Rotation\RosterService;
use Illuminate\Validation\Rule;

/**
 * 관리자의 입장 회원 정보 수정. 번호표·성별은 받지만 테이블·플랫폼은 받지 않는다. (체크인과 연결된 값)
 * 입력이 비어 있으면(null) 그 항목을 비운다. 과거 회원은 일부 값이 비어 있을 수 있어 필수로 두지 않는다.
 */
class EventMemberUpdateRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        // 하이픈/공백 제거 (예: 010-1234-5678 → 01012345678) — 입장등록과 같은 방식
        if ($this->has('phone')) {
            $this->merge(['phone' => preg_replace('/\D+/', '', (string) $this->input('phone'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $eventId  = (int) $this->route('id');
        $memberId = (int) $this->route('memberId');

        return [
            // 핸드폰은 행사 안에서 유일해야 한다. (본인 제외, 삭제된 회원 행도 DB 유일 제약에 걸리므로 함께 확인)
            'phone'    => [
                'required', 'string', 'regex:/^010\d{8}$/',
                Rule::unique('members', 'phone')->where(fn ($q) => $q->where('event_id', $eventId))->ignore($memberId),
            ],
            'password' => 'nullable|string|size:4',

            // 번호표·성별: 체크인 명단과 연결된 값. 사용 중인지/기록이 있는지는 서비스에서 검증한다.
            // 번호표는 숫자 번호표 회원만 보낸다. (숫자가 아닌 예외 회원 이름은 폼에서 비활성)
            'name'   => 'sometimes|required|integer|between:1,' . RosterService::TICKET_MAX,
            'gender' => ['sometimes', 'required', Rule::in([Member::GENDER_MALE, Member::GENDER_FEMALE])],

            'birth_year' => 'nullable|integer|between:1940,' . now()->year,
            'height'     => 'nullable|integer|between:100,230',
            'job'        => 'nullable|string|max:50',
            'mbti'       => ['nullable', 'string', 'size:4', 'regex:/^[A-Za-z]{4}$/'],
            'region'     => 'nullable|string|max:50',
            'is_smoker'  => 'nullable|boolean',

            'drink_capacity'        => ['nullable', Rule::in(Member::drinkCapacityOptions())],
            'drink_frequency'       => ['nullable', Rule::in(Member::drinkFrequencyOptions())],
            'has_pet'               => 'nullable|boolean',
            'friend_meet_frequency' => ['nullable', Rule::in(Member::friendMeetFrequencyOptions())],
            'religion'              => ['nullable', Rule::in(Member::religionOptions())],
            'outer_preference'      => ['nullable', 'array', Member::exclusiveAnyRule()],
            'outer_preference.*'    => ['distinct', Rule::in(Member::outerPreferenceOptions())],
            'inner_preference'      => ['nullable', 'array', Member::exclusiveAnyRule()],
            'inner_preference.*'    => ['distinct', Rule::in(Member::innerPreferenceOptions())],

            'ideal_type' => 'nullable|string|max:100',
            'merit'      => 'nullable|string|max:100',
            'hobby'      => 'nullable|string|max:100',
            'tmi'        => 'nullable|string|max:100',

            // 음료: 이 행사에 등록된 음료만 (1:1 에서만 쓰인다)
            'event_drink_id' => [
                'nullable', 'integer',
                Rule::exists('event_drinks', 'id')->where(fn ($q) => $q->where('event_id', $eventId)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required'          => '핸드폰 번호를 입력해주세요.',
            'phone.regex'             => '핸드폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.',
            'phone.unique'            => '이 행사에 이미 등록된 핸드폰 번호입니다.',
            'password.size'           => '비밀번호는 4자리로 입력해주세요.',
            'name.required'           => '번호표를 입력해주세요.',
            'name.integer'            => '번호표는 숫자로 입력해주세요.',
            'name.between'            => '번호표는 1~' . RosterService::TICKET_MAX . ' 사이 숫자로 입력해 주세요.',
            'gender.in'               => '성별 선택이 올바르지 않습니다.',
            'birth_year.between'      => '출생연도는 1940년 이후 4자리로 입력해주세요.',
            'birth_year.integer'      => '출생연도는 숫자로 입력해주세요.',
            'height.between'          => '키는 100~230cm 사이로 입력해주세요.',
            'height.integer'          => '키는 숫자로 입력해주세요.',
            'mbti.size'               => 'MBTI는 4글자로 입력해주세요.',
            'mbti.regex'              => 'MBTI는 영문 4글자로 입력해주세요.',
            'drink_capacity.in'       => '주량 선택이 올바르지 않습니다.',
            'drink_frequency.in'      => '평균 음주 횟수 선택이 올바르지 않습니다.',
            'friend_meet_frequency.in' => '친구 약속 빈도 선택이 올바르지 않습니다.',
            'religion.in'             => '종교 선택이 올바르지 않습니다.',
            'outer_preference.*.in'   => '외적 조건 선택이 올바르지 않습니다.',
            'inner_preference.*.in'   => '내적 조건 선택이 올바르지 않습니다.',
            'event_drink_id.exists'   => '선택한 음료가 올바르지 않습니다.',
        ];
    }
}
