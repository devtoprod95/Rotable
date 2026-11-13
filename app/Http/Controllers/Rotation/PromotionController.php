<?php

namespace App\Http\Controllers\Rotation;

use App\Models\EventRound;
use Illuminate\View\View;

/**
 * 홍보 — 초대 멘트. (상세페이지 디자이너·QR 은 각각 DetailPageController·QrController)
 */
class PromotionController extends RotationBaseController
{
    /**
     * 초대 멘트 — 플랫폼(문토/네이버)·행사 유형·성별·혜택에 따라 초대 메시지를 조합해 복사한다.
     * 종료(X)가 아닌 행사만 셀렉트 대상으로 내려준다.
     */
    public function inviteIndex(): View
    {
        $events = EventRound::query()
            ->where('status', '!=', EventRound::STATUS_CLOSED)
            ->orderByDesc('event_time')
            ->orderByDesc('id')
            ->get(['id', 'title', 'type', 'status', 'event_time']);

        return view('admin.rotation.invite', [
            'events'      => $events,
            'typeOptions' => EventRound::typeOptions(),
        ]);
    }
}
