<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 만남 이력 기본 조회 — 전화번호 1개.
 */
class HistorySearchRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => 'required|string|max:20',
        ];
    }
}
