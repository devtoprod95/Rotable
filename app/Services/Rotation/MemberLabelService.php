<?php

namespace App\Services\Rotation;

use App\Models\EventRoster;
use App\Models\Member;

/**
 * 관리자 화면용 회원 표기 라벨 — `닉네임(플랫폼)_N호`.
 *
 * - 회원의 번호표(members.name)와 체크인 명단(event_rosters)의 번호표를 (행사, 성별, 번호)로 연결해
 *   체크인 때 적어 둔 닉네임·플랫폼을 붙여 보여준다.
 * - 체크인 명단과 연결되지 않으면 `N호`만 표기한다.
 * - 참가자 화면에는 쓰지 않는다. (상대 닉네임이 보이면 익명성이 깨진다.)
 */
class MemberLabelService
{
    /**
     * 행사의 번호표 → 닉네임/플랫폼 맵. 키는 "성별|번호".
     *
     * @return array<string, array{nickname:?string, platform:?string}>
     */
    public function labelMap(int $eventId): array
    {
        $map = [];

        EventRoster::query()
            ->where('event_id', $eventId)
            ->whereNotNull('ticket_no')
            ->get(['gender', 'ticket_no', 'name', 'platform'])
            ->each(function (EventRoster $r) use (&$map): void {
                $map[$r->gender . '|' . (int) $r->ticket_no] = [
                    'nickname' => $r->name,
                    'platform' => $r->platform,
                ];
            });

        return $map;
    }

    /**
     * 한 회원의 라벨을 만든다.
     *
     * @param array<string, array{nickname:?string, platform:?string}> $map            labelMap() 결과 (키 "성별|번호")
     * @param string|null                                               $memberPlatform 체크인 명단에 플랫폼이 없을 때 쓸 회원의 신청 플랫폼
     */
    public function label(array $map, ?string $gender, ?string $name, ?string $memberPlatform = null): string
    {
        return $this->build($map[(string) $gender . '|' . (int) $name] ?? null, $name, $memberPlatform);
    }

    /**
     * @param array{nickname:?string, platform:?string}|null $hit
     */
    private function build(?array $hit, ?string $name, ?string $memberPlatform): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '-';
        }

        $ticket = $name . '호';
        $nick   = trim((string) ($hit['nickname'] ?? ''));
        if ($hit === null || $nick === '' || ! ctype_digit($name)) {
            return $ticket;
        }

        $platform = $this->platformLabel($hit['platform'] ?? $memberPlatform);

        return $nick . ($platform !== '' ? "({$platform})" : '') . '_' . $ticket;
    }

    private function platformLabel(?string $platform): string
    {
        return match ($platform) {
            Member::PLATFORM_MUNTO => '문토',
            Member::PLATFORM_FRIP  => '프립',
            default                => '',
        };
    }
}
