<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 입금 완료 / 완료 취소.
 */
class PaybackTransferRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'done' => ['required', 'boolean'],
        ];
    }
}
