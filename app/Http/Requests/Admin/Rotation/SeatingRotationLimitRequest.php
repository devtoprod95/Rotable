<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 로테이션 횟수(총 턴 수) 지정. 비우면 테이블 수만큼.
 */
class SeatingRotationLimitRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_id'       => 'required|integer',
            'rotation_limit' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rotation_limit.*' => '로테이션 횟수는 1 이상의 숫자로 입력해 주세요.',
        ];
    }
}
