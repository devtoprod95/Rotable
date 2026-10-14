<?php

namespace App\Services;

use App\Models\Member;
use Carbon\CarbonInterface;

/**
 * 방문 통계 집계 서비스.
 *
 * "방문"의 정의:
 * - 회원(members) 레코드 1건 = 1회 방문. (한 행사에 입장/등록하면 1회 방문으로 센다.)
 * - 같은 사람은 전화번호(phone)로 식별한다. 같은 전화번호가 여러 행사에 참여하면
 *   그 전화번호의 누적 방문 횟수가 올라간다.
 */
class VisitStatService
{
    /**
     * 특정 시점(기준 시각)까지의 방문 통계를 집계한다.
     *
     * 방문 횟수 구간(1~5번, 6번 이상)별로 사람(전화번호) 수를 전체/남/여로 나눠 집계한다.
     * 전화번호의 성별은 그 전화번호의 "가장 최근 방문 레코드" 기준으로 1개로 확정한다.
     *
     * @param CarbonInterface|null $until 이 시각 "이전(미만)"에 생성된 회원만 집계. null 이면 전체.
     * @return array<string, int>
     */
    public function aggregate(?CarbonInterface $until = null): array
    {
        $base = Member::query();
        if ($until !== null) {
            $base->where('members.created_at', '<', $until);
        }

        // 총 방문 수 / 성별 방문 수 (방문 = 회원 레코드 1건).
        $total       = (clone $base)->count();
        $totalMale   = (clone $base)->where('gender', Member::GENDER_MALE)->count();
        $totalFemale = (clone $base)->where('gender', Member::GENDER_FEMALE)->count();

        // 전화번호별 방문 횟수 + 그 전화번호의 대표 성별(가장 최근 방문 레코드)을 구한다.
        $rows = (clone $base)
            ->orderBy('members.phone')
            ->orderBy('members.created_at')
            ->orderBy('members.id')
            ->get(['members.phone', 'members.gender']);

        // phone => ['count' => int, 'gender' => string] (gender 는 가장 최근 레코드로 덮어쓴다)
        $byPhone = [];
        foreach ($rows as $row) {
            $phone = $row->phone;
            if (! isset($byPhone[$phone])) {
                $byPhone[$phone] = ['count' => 0, 'gender' => $row->gender];
            }
            $byPhone[$phone]['count']++;
            $byPhone[$phone]['gender'] = $row->gender; // 정렬상 마지막(=최근) 성별로 확정
        }

        // 구간별 전체/남/여 버킷 초기화.
        $keys = ['visit_1', 'visit_2', 'visit_3', 'visit_4', 'visit_5', 'visit_6_plus'];
        $buckets = [];
        foreach ($keys as $key) {
            $buckets[$key]             = 0;
            $buckets[$key . '_male']   = 0;
            $buckets[$key . '_female'] = 0;
        }

        foreach ($byPhone as $info) {
            $count = (int) $info['count'];
            if ($count < 1) {
                continue;
            }
            $key = $count >= 6 ? 'visit_6_plus' : 'visit_' . $count;

            $buckets[$key]++;
            if ($info['gender'] === Member::GENDER_MALE) {
                $buckets[$key . '_male']++;
            } elseif ($info['gender'] === Member::GENDER_FEMALE) {
                $buckets[$key . '_female']++;
            }
        }

        return array_merge([
            'total_visits' => $total,
            'total_male'   => $totalMale,
            'total_female' => $totalFemale,
        ], $buckets);
    }

    /**
     * 특정 행사의 참여자들에 대해 "이 회원이 그 전화번호의 몇 번째 방문인지" 맵을 만든다.
     *
     * - 전화번호 전체 방문 이력을 행사 일시(event_time) → 회원 생성시각(created_at) 순으로 정렬해
     *   순번(1부터)을 매긴다.
     * - 반환 맵의 키는 members.id, 값은 그 회원의 방문 순번(N번째).
     *
     * @param array<int, string> $phones 대상 전화번호들
     * @return array<int, int> member_id => N (몇 번째 방문)
     */
    public function visitOrdinalMap(array $phones): array
    {
        $phones = array_values(array_unique(array_filter($phones)));
        if (empty($phones)) {
            return [];
        }

        // 대상 전화번호들의 모든 방문(회원 레코드)을 행사 일시/생성시각 순으로 가져온다.
        $rows = Member::query()
            ->leftJoin('event_rounds', 'event_rounds.id', '=', 'members.event_id')
            ->whereIn('members.phone', $phones)
            ->orderByRaw('event_rounds.event_time is null') // 일시 없는 건 뒤로
            ->orderBy('event_rounds.event_time')
            ->orderBy('members.created_at')
            ->orderBy('members.id')
            ->get(['members.id', 'members.phone']);

        $ordinalByPhone = [];
        $map            = [];
        foreach ($rows as $row) {
            $phone                  = $row->phone;
            $ordinalByPhone[$phone] = ($ordinalByPhone[$phone] ?? 0) + 1;
            $map[(int) $row->id]    = $ordinalByPhone[$phone];
        }

        return $map;
    }
}
