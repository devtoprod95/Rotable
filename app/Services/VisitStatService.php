<?php

namespace App\Services;

use App\Models\EventRoster;
use App\Models\EventRound;
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
     * 아울러 노쇼(신청했으나 미참석, event_rosters.checked_in_at is null)를 성별·플랫폼으로 함께 집계한다.
     *
     * 스냅샷 재생성 멱등성을 위해 삭제(soft delete)된 회원도 포함해 집계한다.
     * (집계 기준은 오직 created_at 컷오프이며, 같은 기준 시각이면 몇 번을 돌려도 같은 값이 나온다.)
     *
     * @param CarbonInterface|null $until 이 시각 "이전(미만)"에 생성된 회원만 집계. null 이면 전체.
     * @return array<string, int|array<string, int>> 집계 결과. platform_counts 만 [플랫폼=>수] 중첩 배열이고 나머지는 int.
     */
    public function aggregate(?CarbonInterface $until = null): array
    {
        // 스냅샷은 "그 시점의 누적 사실"을 박제하는 것이므로, 몇 번을 다시 돌려도(= 과거 날짜 재생성)
        // 같은 값이 나와야 한다(멱등). Member 는 SoftDeletes 라 기본 쿼리는 삭제분을 빼는데,
        // 그러면 "그 당시엔 있었지만 지금 삭제된 회원"이 재집계 때 빠져 값이 달라진다.
        // 따라서 created_at 컷오프 기준으로만 거르고, 삭제 여부는 보지 않도록 withTrashed() 로 집계한다.
        $base = Member::query()->withTrashed();
        if ($until !== null) {
            $base->where('members.created_at', '<', $until);
        }

        // 총 방문 수 / 성별 방문 수 (방문 = 회원 레코드 1건).
        $total       = (clone $base)->count();
        $totalMale   = (clone $base)->where('gender', Member::GENDER_MALE)->count();
        $totalFemale = (clone $base)->where('gender', Member::GENDER_FEMALE)->count();

        // 신청 플랫폼별 방문 수 (방문 = 회원 레코드 1건).
        // 플랫폼은 늘어날 수 있으므로 하드코딩하지 않고, 실제 데이터의 platform 값을 그대로 집계한다.
        // (Member::platformOptions() 의 모든 플랫폼은 0 으로라도 키가 존재하도록 미리 채운다.)
        $platformCounts = [];
        foreach (Member::platformOptions() as $opt) {
            $platformCounts[$opt['id']] = 0;
        }
        $platformRows = (clone $base)
            ->selectRaw('members.platform as platform, COUNT(*) as cnt')
            ->groupBy('members.platform')
            ->get();
        foreach ($platformRows as $row) {
            $key = $row->platform ?? Member::PLATFORM_MUNTO; // null 은 기본(문토)로 귀속
            $platformCounts[$key] = ($platformCounts[$key] ?? 0) + (int) $row->cnt;
        }

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
            'total_visits'    => $total,
            'total_male'      => $totalMale,
            'total_female'    => $totalFemale,
            'platform_counts' => $platformCounts,
        ], $buckets, $this->aggregateNoShow($until));
    }

    /**
     * 노쇼(신청했으나 미참석) 인원을 집계한다.
     *
     * 기준: **종료(X)된 행사**의 체크인 명단(event_rosters) 중 checked_in_at 이 null 인 행 = 미참석(노쇼).
     * (진행 전/중 행사는 아직 체크인이 끝나지 않아 노쇼가 확정되지 않으므로 제외한다.)
     * 방문 통계와 같은 축(성별·플랫폼)으로 분류한다.
     *  - 성별: roster.gender 기준으로 남/여를 각각 명시적으로 집계한다. (M/F 외 값은 어느 쪽에도 넣지 않음)
     *  - 플랫폼: Member::platformOptions() 의 모든 플랫폼 키를 0 으로 채운 뒤 실제 값으로 집계.
     *
     * @param CarbonInterface|null $until 이 시각 "이전(미만)"에 생성된 명단만 집계. null 이면 전체.
     * @return array{no_show_total:int, no_show_male:int, no_show_female:int, no_show_platform_counts:array<string,int>}
     */
    private function aggregateNoShow(?CarbonInterface $until = null): array
    {
        // 노쇼는 "행사가 끝나야" 확정된다. 진행 전/중(P·S·V)인 행사의 명단에서 checked_in_at 이
        // null 인 사람은 아직 체크인 안 한 것일 뿐 노쇼가 아니므로, 종료(X)된 행사만 대상으로 한다.
        $base = EventRoster::query()
            ->whereNull('checked_in_at')
            ->whereHas('eventRound', fn ($q) => $q->where('status', EventRound::STATUS_CLOSED));
        if ($until !== null) {
            $base->where('event_rosters.created_at', '<', $until);
        }

        $total  = (clone $base)->count();
        // 남/여를 각각 명시적으로 센다. (roster.gender 는 체크인 추가 시 M/F 로 정규화되지만,
        // '여를 뺀 나머지=남' 방식은 혹시 모를 null/기타 값을 남으로 과대 집계하므로 쓰지 않는다.)
        $male   = (clone $base)->where('gender', Member::GENDER_MALE)->count();
        $female = (clone $base)->where('gender', Member::GENDER_FEMALE)->count();

        // 플랫폼별 노쇼 수. 방문 플랫폼 집계와 동일하게 모든 플랫폼 키를 0 으로 선채운다.
        $platformCounts = [];
        foreach (Member::platformOptions() as $opt) {
            $platformCounts[$opt['id']] = 0;
        }
        $platformRows = (clone $base)
            ->selectRaw('event_rosters.platform as platform, COUNT(*) as cnt')
            ->groupBy('event_rosters.platform')
            ->get();
        foreach ($platformRows as $row) {
            $key = $row->platform ?? Member::PLATFORM_MUNTO; // null 은 기본(문토)로 귀속
            $platformCounts[$key] = ($platformCounts[$key] ?? 0) + (int) $row->cnt;
        }

        return [
            'no_show_total'           => $total,
            'no_show_male'            => $male,
            'no_show_female'          => $female,
            'no_show_platform_counts' => $platformCounts,
        ];
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

    /**
     * 전화번호별 "행사 유형별 누적 방문 횟수"를 집계한다.
     *
     * - 한 전화번호가 와인파티/1:1 각각 몇 번 참여했는지(회원 레코드 수)를 센다.
     * - 반환 맵의 키는 전화번호, 값은 [행사타입 => 횟수] 배열. (예: ['wine' => 3, 'one' => 2])
     *   타입이 null 인(행사 미연결) 레코드는 집계에서 제외한다.
     *
     * @param array<int, string> $phones 대상 전화번호들
     * @return array<string, array<string, int>> phone => [type => count]
     */
    public function visitCountsByType(array $phones): array
    {
        $phones = array_values(array_unique(array_filter($phones)));
        if (empty($phones)) {
            return [];
        }

        $rows = Member::query()
            ->leftJoin('event_rounds', 'event_rounds.id', '=', 'members.event_id')
            ->whereIn('members.phone', $phones)
            ->whereNotNull('event_rounds.type')
            ->selectRaw('members.phone as phone, event_rounds.type as event_type, COUNT(*) as cnt')
            ->groupBy('members.phone', 'event_rounds.type')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->phone][$row->event_type] = (int) $row->cnt;
        }

        return $map;
    }
}
