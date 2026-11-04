<?php

namespace App\Services\Rotation;

use App\Models\EventRoster;
use App\Models\EventRound;
use Illuminate\Support\Collection;

/**
 * 행사 운영 화면(행사 정보 · 체크인 · 로테이션 진행)이 공유하는 "현재 행사" 컨텍스트.
 *
 * - 사이드바의 체크인/로테이션 진행, 화면 상단 탭바가 모두 같은 규칙으로 "지금 볼 행사"를 고른다.
 * - 탭바에 보여줄 요약(참석 현황, 로테이션 턴)도 여기서 만든다.
 */
class EventContextService
{
    /**
     * 지금 운영할 행사 id.
     *
     *  1) 진행(S)·매칭중(V) 행사가 있으면 그중 가장 최근 행사 — 지금 진행 중인 행사가 최우선.
     *  2) 없으면 대기(P) 행사 중 일정이 가장 가까운 것. (행사 당일 새벽에 이미 지난 시각이어도 잡히도록 12시간 여유)
     *  3) 그래도 없으면 가장 최근 행사.
     */
    public function currentEventId(): ?int
    {
        $live = EventRound::query()
            ->whereIn('status', [EventRound::STATUS_STARTED, EventRound::STATUS_VOTING])
            ->orderByDesc('event_time')
            ->orderByDesc('id')
            ->value('id');
        if ($live !== null) {
            return (int) $live;
        }

        $upcoming = EventRound::query()
            ->where('status', EventRound::STATUS_PENDING)
            ->where('event_time', '>=', now()->subHours(12))
            ->orderBy('event_time')
            ->orderBy('id')
            ->value('id');
        if ($upcoming !== null) {
            return (int) $upcoming;
        }

        $latest = EventRound::query()->orderByDesc('event_time')->orderByDesc('id')->value('id');

        return $latest !== null ? (int) $latest : null;
    }

    /**
     * 탭바의 행사 선택 목록. 최근 행사가 위로 오도록 정렬한다.
     *
     * @return Collection<int, EventRound>
     */
    public function selectableEvents(): Collection
    {
        return EventRound::query()
            ->orderByDesc('event_time')
            ->orderByDesc('id')
            ->get(['id', 'title', 'type', 'status', 'event_time', 'current_round']);
    }

    /**
     * 탭바에 보여줄 한 행사의 요약.
     *
     * @return array{checkin_total:int, checkin_in:int}
     */
    public function summary(int $eventId): array
    {
        $total = EventRoster::where('event_id', $eventId)->count();
        $in    = EventRoster::where('event_id', $eventId)->whereNotNull('checked_in_at')->count();

        return [
            'checkin_total' => $total,
            'checkin_in'    => $in,
        ];
    }
}
