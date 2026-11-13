<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\Member;
use Illuminate\Validation\Rule;

/**
 * 체크인 명단 1건 수정. 보낸 항목만 바꾼다.
 */
class RosterUpdateRequest extends ApiFormRequest
{
    /**
     * 년생·키는 "00", "05" 처럼 앞에 0이 붙은 문자열로 올 수 있다.
     * (integer 규칙은 선행 0 문자열을 거부하므로) 검증 전에 숫자로 바꿔 둔다.
     */
    protected function prepareForValidation(): void
    {
        $numbers = [];
        foreach (['height', 'birth_year'] as $key) {
            $value = $this->input($key);
            if (is_string($value) && preg_match('/^\s*\d+\s*$/', $value) === 1) {
                $numbers[$key] = (int) $value;
            }
        }
        $this->merge($numbers);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'       => 'nullable|string|max:50',
            'platform'   => ['nullable', Rule::in([Member::PLATFORM_MUNTO, Member::PLATFORM_FRIP])],
            'gender'     => ['nullable', Rule::in([Member::GENDER_MALE, Member::GENDER_FEMALE])],
            'height'     => 'sometimes|required|integer|min:100|max:230',
            'birth_year' => 'sometimes|required|integer|min:0|max:2100',
            'job'        => 'sometimes|required|string|max:50',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'height.required'     => '키를 입력해주세요.',
            'height.integer'      => '키는 숫자로 입력해주세요.',
            'height.min'          => '키는 100~230cm 사이로 입력해주세요.',
            'height.max'          => '키는 100~230cm 사이로 입력해주세요.',
            'birth_year.required' => '년생을 입력해주세요. (예: 97)',
            'birth_year.integer'  => '년생은 숫자로 입력해주세요. (예: 97)',
            'job.required'        => '직업을 입력해주세요.',
        ];
    }
}
