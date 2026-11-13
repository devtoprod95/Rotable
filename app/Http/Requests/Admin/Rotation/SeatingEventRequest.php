<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 로테이션 진행 단계 전환(진행/되돌리기/매칭 시작·종료·취소/종료 되돌리기) — 대상 행사.
 */
class SeatingEventRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_id' => 'required|integer',
        ];
    }
}
