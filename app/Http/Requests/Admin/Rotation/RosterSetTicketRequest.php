<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Services\Rotation\RosterService;

/**
 * 번호표 변경(필수) + 앉을 테이블 변경(선택).
 */
class RosterSetTicketRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ticket_no'         => 'required|integer|min:1|max:' . RosterService::TICKET_MAX,
            'rotation_table_id' => 'nullable|integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ticket_no.required' => '번호표를 입력해 주세요.',
            'ticket_no.integer'  => '번호표는 숫자로 입력해 주세요.',
            'ticket_no.min'      => '번호표는 1 이상이어야 합니다.',
            'ticket_no.max'      => '번호표는 ' . RosterService::TICKET_MAX . ' 이하여야 합니다.',
        ];
    }
}
