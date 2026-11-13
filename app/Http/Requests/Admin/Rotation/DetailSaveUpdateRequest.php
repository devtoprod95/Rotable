<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 상세페이지 저장본 HTML 덮어쓰기.
 */
class DetailSaveUpdateRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'html' => 'nullable|string',
        ];
    }
}
