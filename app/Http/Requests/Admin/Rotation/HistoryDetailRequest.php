<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 만남 이력 상세 조회 — 전화번호 2개.
 */
class HistoryDetailRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phoneA' => 'required|string|max:20',
            'phoneB' => 'required|string|max:20',
        ];
    }
}
