<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 음료 전달 완료 체크/해제. (1:1 소개팅)
 */
class EventMemberDrinkServedRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'served' => 'required|boolean',
        ];
    }
}
