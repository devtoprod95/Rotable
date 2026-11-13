<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 삭제 등 복수 선택 요청 — ids 배열(정수).
 */
class IdsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ];
    }
}
