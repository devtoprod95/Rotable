<?php

namespace App\Http\Controllers;

use App\Models\EventRound;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * 홍보(신청 안내) 공개 페이지 — 비인증.
 * 유형(와인파티 / 1:1)별 소개·예정 일정·핵심 기능과 문토·프립 신청 링크를 보여준다.
 */
class PublicPromoController extends Controller
{
    /** 유형별로 노출할 예정 일정 최대 개수. */
    private const SCHEDULE_LIMIT = 6;

    /**
     * @param  string|null  $type  'wine' | 'one'. 그 외 값이면 1:1 로 시작.
     */
    public function index(?string $type = null): View
    {
        $type = in_array($type, [EventRound::TYPE_WINE, EventRound::TYPE_ONE], true)
            ? $type
            : EventRound::TYPE_ONE;

        return view('public.promo', [
            'type'      => $type,
            'types'     => EventRound::typeOptions(),
            'schedules' => $this->upcomingSchedules($type),
            'platforms' => $this->platformsFor($type),
        ]);
    }

    /**
     * 오늘 이후 예정된(종료 전) 행사 일정. 가까운 순.
     *
     * @return Collection<int, EventRound>
     */
    private function upcomingSchedules(string $type): Collection
    {
        return EventRound::query()
            ->where('type', $type)
            ->where('status', '!=', EventRound::STATUS_CLOSED)
            ->where('event_time', '>=', now()->startOfDay())
            ->orderBy('event_time')
            ->limit(self::SCHEDULE_LIMIT)
            ->get(['id', 'title', 'type', 'status', 'event_time']);
    }

    /**
     * 해당 유형의 신청 플랫폼 목록.
     *
     * @return list<array{key:string, name:string, color:string, url:?string}>
     */
    private function platformsFor(string $type): array
    {
        return collect(config('promo.platforms', []))
            ->map(fn (array $platform, string $key) => [
                'key'   => $key,
                'name'  => $platform['name'],
                'color' => $platform['color'],
                'url'   => $platform['urls'][$type] ?? null,
            ])
            ->values()
            ->all();
    }
}
