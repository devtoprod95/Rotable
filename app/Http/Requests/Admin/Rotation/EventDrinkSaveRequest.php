<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventDrink;

/**
 * 행사 음료 선택지 저장. (1:1 소개팅) 비우면 전체 삭제.
 */
class EventDrinkSaveRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'drinks'   => 'nullable|array|max:' . EventDrink::MAX_PER_EVENT,
            'drinks.*' => 'required|string|max:50',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'drinks.max'      => '음료는 최대 ' . EventDrink::MAX_PER_EVENT . '개까지 등록할 수 있습니다.',
            'drinks.*.max'    => '음료 이름은 50자 이내로 입력해 주세요.',
            'drinks.*.string' => '음료 이름을 확인해 주세요.',
        ];
    }
}
