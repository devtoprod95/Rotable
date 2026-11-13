<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 상세페이지 저장 목록 추가 — 이름(라벨)을 붙여 저장.
 */
class DetailSaveStoreRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'html'  => 'nullable|string',
            'type'  => 'nullable|in:wine,solo',
        ];
    }
}
