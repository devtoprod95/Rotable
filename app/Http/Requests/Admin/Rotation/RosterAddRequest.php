<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\Member;
use Illuminate\Validation\Rule;

/**
 * 체크인 명단 1명 추가 (닉네임/플랫폼/성별).
 */
class RosterAddRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:50',
            'platform'   => ['required', Rule::in([Member::PLATFORM_MUNTO, Member::PLATFORM_FRIP])],
            'gender'     => ['required', Rule::in([Member::GENDER_MALE, Member::GENDER_FEMALE])],
            'height'     => 'required|integer|min:100|max:230',
            'birth_year' => 'required|integer|min:0|max:2100',
            'job'        => 'required|string|max:50',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'     => '닉네임을 입력해주세요.',
            'platform.required' => '플랫폼을 선택해주세요.',
            'platform.in'       => '플랫폼 선택이 올바르지 않습니다.',
            'gender.required'   => '성별을 선택해주세요.',
            'gender.in'         => '성별 선택이 올바르지 않습니다.',
            'height.required'   => '키를 입력해주세요.',
            'height.integer'    => '키는 숫자로 입력해주세요.',
            'height.min'        => '키는 100~230cm 사이로 입력해주세요.',
            'height.max'        => '키는 100~230cm 사이로 입력해주세요.',
            'birth_year.required' => '년생을 입력해주세요. (예: 97)',
            'job.required'      => '직업을 입력해주세요.',
            'birth_year.integer' => '년생은 숫자로 입력해주세요. (예: 97)',
        ];
    }
}
