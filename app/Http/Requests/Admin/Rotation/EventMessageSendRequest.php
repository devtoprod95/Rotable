<?php

namespace App\Http\Requests\Admin\Rotation;

use Illuminate\Validation\Rule;

/**
 * 관리자 → 참가자 메시지 발송. (참가자에게는 익명으로 표시)
 */
class EventMessageSendRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body'         => ['required', 'string', 'max:1000'],
            'target'       => ['required', Rule::in(['all', 'M', 'F', 'members'])],
            'member_ids'   => ['required_if:target,members', 'array'],
            'member_ids.*' => ['integer'],
        ];
    }
}
