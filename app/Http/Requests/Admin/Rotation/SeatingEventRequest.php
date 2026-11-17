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
            'event_id'       => 'required|integer',
            // 로테이션 진행 시 관리자 화면이 보고 있던 현재 턴. 서버의 현재 턴과 다르면 진행하지 않는다. (선택)
            'expected_round' => 'nullable|integer|min:0',
        ];
    }
}
