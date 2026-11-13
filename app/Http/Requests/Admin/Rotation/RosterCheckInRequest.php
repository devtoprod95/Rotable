<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Services\Rotation\RosterService;

/**
 * 체크인(참석) 토글. 번호표를 비우면 자동 배정, 테이블은 선택 지정.
 */
class RosterCheckInRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ticket_no'         => 'nullable|integer|min:1|max:' . RosterService::TICKET_MAX,
            'rotation_table_id' => 'nullable|integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ticket_no.integer' => '번호표는 숫자로 입력해 주세요.',
            'ticket_no.min'     => '번호표는 1 이상이어야 합니다.',
            'ticket_no.max'     => '번호표는 ' . RosterService::TICKET_MAX . ' 이하여야 합니다.',
        ];
    }
}
