<?php

namespace App\Services\Rotation;

use App\Models\Member;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

/**
 * 매칭(투표 기반 상호 매칭 조회) 도메인 서비스.
 *
 * 누가 누구를 선택했는지(matchList), 상호 매칭된 쌍(matchedPairList),
 * 특정 회원의 지목/피지목 목록을 조회한다. (RotationService 에서 분리)
 */
class MatchService
{
    /* ======================================================================
     | 매칭 (투표 기반 상호 매칭 조회)
     * ==================================================================== */

    /**
     * 특정 행사의 투표 목록. (쌍방 여부와 무관하게 "누가 누구를 선택했는지" 모두 표기)
     * 같은 "선택한 사람(voter)" 기준으로 한 줄에 묶어, 그 사람이 고른 상대들을 함께 반환한다.
     * 상대도 나를 선택한(상호 매칭) 상대는 target.is_mutual=true 로 표시한다.
     * 투표 시각(target.voted_at)은 선택한 상대별 투표 생성 시각(event_votes.created_at)이다.
     * 선택한 사람(행)과 각 사람의 선택 상대는 모두 번호표 오름차순(같은 번호면 남 → 여)으로 정렬한다.
     *
     * @return array<int, array{
     *     voter: array{id:int, name:?string, gender:?string, phone:?string, birth_year:?int, job:?string, height:?int, region:?string, is_smoker:?bool, mbti:?string},
     *     targets: array<int, array{id:int, name:?string, gender:?string, phone:?string, birth_year:?int, job:?string, height:?int, region:?string, is_smoker:?bool, mbti:?string, is_mutual:bool, voted_at:?string}>,
     *     has_mutual: bool
     * }>
     */
    public function matchList(int $eventId): array
    {
        [$votes, $votedMap] = $this->loadVotesWithMutualMap($eventId);
        $labelMap = $this->labelMap($eventId);

        // 같은 선택한 사람(voter)끼리 묶는다.
        $grouped = [];
        foreach ($votes as $vote) {
            $voterId = (int) $vote->voter_id;

            if (!isset($grouped[$voterId])) {
                $grouped[$voterId] = [
                    'voter'      => $this->personPayload($vote->voter, $voterId, null, $labelMap),
                    'targets'    => [],
                    'has_mutual' => false,
                ];
            }

            $isMutual = isset($votedMap[$vote->target_id][$vote->voter_id]);

            $target = $this->personPayload($vote->target, (int) $vote->target_id, $isMutual, $labelMap);
            // 선택한 상대별 투표 시각 (투표 생성 시각)
            $target['voted_at'] = $vote->created_at?->toIso8601String();
            $grouped[$voterId]['targets'][] = $target;

            if ($isMutual) {
                $grouped[$voterId]['has_mutual'] = true;
            }
        }

        $rows = array_values($grouped);

        // 선택한 상대 목록: 번호표 오름차순.
        foreach ($rows as &$row) {
            usort($row['targets'], fn ($a, $b) => $this->compareByTicket($a, $b));
        }
        unset($row);

        // 선택한 사람: 번호표 오름차순. (페이지네이션 없이 전부 반환)
        usort($rows, fn ($a, $b) => $this->compareByTicket($a['voter'], $b['voter']));

        return $rows;
    }

    /**
     * 번호표(members.name) 오름차순 비교. 숫자 번호표는 숫자로 비교하고(2 < 10),
     * 같은 번호면 남 → 여, 번호표가 숫자가 아니면 맨 뒤로 보낸다.
     *
     * @param array{name:?string, gender:?string} $a
     * @param array{name:?string, gender:?string} $b
     */
    private function compareByTicket(array $a, array $b): int
    {
        $key = fn (array $p): array => [
            ctype_digit((string) ($p['name'] ?? '')) ? 0 : 1,
            ctype_digit((string) ($p['name'] ?? '')) ? (int) $p['name'] : 0,
            ($p['gender'] ?? null) === Member::GENDER_MALE ? 0 : 1,
            (string) ($p['name'] ?? ''),
        ];

        return $key($a) <=> $key($b);
    }

    /**
     * 특정 행사의 전체 상호 매칭 수(쌍의 개수).
     *
     * 한 사람이 여러 명과 서로 선택할 수 있으므로 "매칭된 사람 수"가 아니라 "성사된 쌍" 자체를 센다.
     * (A→B 와 B→A 가 모두 있으면 self-join 에서 2행이 나오므로 2로 나눈다.)
     */
    public function mutualPairCount(int $eventId): int
    {
        $rows = DB::table('event_votes as v1')
            ->join('event_votes as v2', function ($join) {
                $join->on('v1.event_id', '=', 'v2.event_id')
                    ->on('v1.voter_id', '=', 'v2.target_id')
                    ->on('v1.target_id', '=', 'v2.voter_id');
            })
            ->where('v1.event_id', $eventId)
            ->count();

        return intdiv($rows, 2);
    }

    /**
     * 특정 행사의 "상호 매칭된 쌍"만, 남자(voter) 기준으로 묶어 반환한다.
     * - 쌍방이 서로 선택한 경우만 포함(단방향 투표 제외).
     * - A↔B 쌍은 남자 쪽을 voter 로 하여 한 번만 노출한다. (성별이 모호하면 중복 방지를 위해 id 작은 쪽을 기준으로)
     * - 투표 시각(target.voted_at)은 해당 남자가 그 상대를 선택한 투표의 생성 시각이다.
     * - 남자(행)와 각 남자의 매칭 상대는 번호표 오름차순으로 정렬한다.
     *
     * @return array<int, array{
     *     voter: array{id:int, name:?string, gender:?string, phone:?string, birth_year:?int, job:?string, height:?int, region:?string, is_smoker:?bool, mbti:?string},
     *     targets: array<int, array{id:int, name:?string, gender:?string, phone:?string, birth_year:?int, job:?string, height:?int, region:?string, is_smoker:?bool, mbti:?string, is_mutual:bool, voted_at:?string}>,
     *     has_mutual: bool
     * }>
     */
    public function matchedPairList(int $eventId): array
    {
        [$votes, $votedMap] = $this->loadVotesWithMutualMap($eventId);
        $labelMap = $this->labelMap($eventId);

        $grouped = [];
        foreach ($votes as $vote) {
            $isMutual = isset($votedMap[$vote->target_id][$vote->voter_id]);
            if (!$isMutual) {
                continue; // 매칭(쌍방)된 건만.
            }

            // 쌍을 한 번만 노출하기 위한 기준: 남자를 voter 로 삼는다.
            // 성별 정보가 없거나 양쪽이 같으면 id 작은 쪽을 voter 로 하여 중복을 방지한다.
            $voterIsMale = $vote->voter?->gender === Member::GENDER_MALE;
            $targetIsMale = $vote->target?->gender === Member::GENDER_MALE;

            if ($voterIsMale && !$targetIsMale) {
                $keep = true;
            } elseif (!$voterIsMale && $targetIsMale) {
                $keep = false;
            } else {
                // 성별 기준으로 가릴 수 없으면 id 작은 쪽만 유지.
                $keep = (int) $vote->voter_id < (int) $vote->target_id;
            }

            if (!$keep) {
                continue;
            }

            $voterId = (int) $vote->voter_id;

            if (!isset($grouped[$voterId])) {
                $grouped[$voterId] = [
                    'voter'      => $this->personPayload($vote->voter, $voterId, null, $labelMap),
                    'targets'    => [],
                    'has_mutual' => true,
                ];
            }

            $target = $this->personPayload($vote->target, (int) $vote->target_id, true, $labelMap);
            // 선택한 상대별 투표 시각 (투표 생성 시각)
            $target['voted_at'] = $vote->created_at?->toIso8601String();
            $grouped[$voterId]['targets'][] = $target;
        }

        $rows = array_values($grouped);

        // 매칭 상대: 번호표 오름차순.
        foreach ($rows as &$row) {
            usort($row['targets'], fn ($a, $b) => $this->compareByTicket($a, $b));
        }
        unset($row);

        // 남자(행): 번호표 오름차순.
        usort($rows, fn ($a, $b) => $this->compareByTicket($a['voter'], $b['voter']));

        return $rows;
    }

    /**
     * 특정 행사의 투표 전체를 (voter/target 상세 포함) 로드하고,
     * 상호 지목 판정용 맵(voter_id => [target_id => true])을 함께 만들어 반환한다.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Collection<int, Vote>, 1: array<int, array<int, true>>}
     */
    private function loadVotesWithMutualMap(int $eventId): array
    {
        $votes = Vote::query()
            ->with(['voter:id,name,gender,platform,phone,birth_year,job,height,region,is_smoker,mbti,drink_capacity,drink_frequency,has_pet,friend_meet_frequency', 'target:id,name,gender,platform,phone,birth_year,job,height,region,is_smoker,mbti,drink_capacity,drink_frequency,has_pet,friend_meet_frequency'])
            ->where('event_id', $eventId)
            ->orderByDesc('id')
            ->get();

        $votedMap = [];
        foreach ($votes as $vote) {
            $votedMap[$vote->voter_id][$vote->target_id] = true;
        }

        return [$votes, $votedMap];
    }

    /**
     * 행사의 번호표 → 닉네임/플랫폼 맵. (관리자 표기 라벨용)
     *
     * @return array<string, array{nickname:?string, platform:?string}>
     */
    private function labelMap(int $eventId): array
    {
        return app(MemberLabelService::class)->labelMap($eventId);
    }

    /**
     * 매칭/투표 응답용 회원 상세 payload 를 만든다.
     * $isMutual 이 전달되면 target 용(상호 매칭 플래그 포함) payload 로 간주한다.
     * label 은 관리자 표기 `닉네임(플랫폼)_N호`.
     *
     * @param array<string, array{nickname:?string, platform:?string}> $labelMap
     * @return array<string, mixed>
     */
    private function personPayload(?Member $member, int $id, ?bool $isMutual = null, array $labelMap = []): array
    {
        $payload = [
            'id'         => $id,
            'name'       => $member?->name,
            'label'      => app(MemberLabelService::class)->label($labelMap, $member?->gender, $member?->name, $member?->platform),
            'gender'     => $member?->gender,
            'phone'      => $member?->phone,
            'birth_year' => $member?->birth_year,
            'job'        => $member?->job,
            'height'     => $member?->height,
            'region'     => $member?->region,
            'is_smoker'  => $member?->is_smoker,
            'mbti'       => $member?->mbti,
            // 생활 습관
            'drink_capacity'        => $member?->drink_capacity,
            'drink_frequency'       => $member?->drink_frequency,
            'has_pet'               => $member?->has_pet,
            'friend_meet_frequency' => $member?->friend_meet_frequency,
        ];

        if ($isMutual !== null) {
            $payload['is_mutual'] = $isMutual;
        }

        return $payload;
    }

    /**
     * 특정 행사에서 "특정 회원을 선택(지목)한 사람들" 목록.
     * - target(선택받은 회원)을 나를 지목한 voter 들을 상세 정보와 함께 반환한다.
     * - 상대도 선택받은 회원을 지목했는지(상호 매칭)는 is_mutual 로 표시한다.
     * - 각 voter 정보: 이름/직업/전화번호/나이(출생연도)/키.
     *
     * @return array<int, array{
     *     id:int, name:?string, gender:?string, job:?string, phone:?string,
     *     birth_year:?int, age:?int, height:?int, is_mutual:bool, voted_at:?string
     * }>
     */
    public function memberVoterList(int $eventId, int $memberId): array
    {
        // 이 회원(memberId)을 지목한 투표들.
        $votes = Vote::query()
            ->with(['voter:id,name,gender,platform,phone,birth_year,job,height'])
            ->where('event_id', $eventId)
            ->where('target_id', $memberId)
            ->orderByDesc('id')
            ->get();

        // 이 회원이 역으로 지목한 상대 집합(상호 매칭 판정용).
        $myTargets = Vote::query()
            ->where('event_id', $eventId)
            ->where('voter_id', $memberId)
            ->pluck('target_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $currentYear = (int) now()->year;
        $labelMap    = $this->labelMap($eventId);
        $labeler     = app(MemberLabelService::class);

        $rows = $votes->map(function (Vote $vote) use ($myTargets, $currentYear, $labelMap, $labeler) {
            $voter = $vote->voter;
            $birthYear = $voter?->birth_year;

            return [
                'id'         => (int) $vote->voter_id,
                'name'       => $voter?->name,
                'label'      => $labeler->label($labelMap, $voter?->gender, $voter?->name, $voter?->platform),
                'gender'     => $voter?->gender,
                'job'        => $voter?->job,
                'phone'      => $voter?->phone,
                'birth_year' => $birthYear,
                'age'        => $birthYear ? ($currentYear - (int) $birthYear + 1) : null,
                'height'     => $voter?->height,
                'is_mutual'  => in_array((int) $vote->voter_id, $myTargets, true),
                'voted_at'   => $vote->created_at?->toIso8601String(),
            ];
        })->all();

        return $rows;
    }

    /**
     * 특정 행사에서 "특정 회원이 선택(투표)한 상대들" 목록.
     * - 그 회원(memberId)이 voter 로서 지목한 target 들을 상세 정보와 함께 반환한다.
     * - 상대도 그 회원을 지목했는지(상호 매칭)는 is_mutual 로 표시한다.
     * - 매칭된 상대가 먼저 보이도록 정렬한다.
     *
     * @return array<int, array{
     *     id:int, name:?string, gender:?string, job:?string, phone:?string,
     *     birth_year:?int, age:?int, height:?int, is_mutual:bool, voted_at:?string
     * }>
     */
    public function memberVotedTargetList(int $eventId, int $memberId): array
    {
        // 이 회원(memberId)이 지목한 투표들.
        $votes = Vote::query()
            ->with(['target:id,name,gender,platform,phone,birth_year,job,height'])
            ->where('event_id', $eventId)
            ->where('voter_id', $memberId)
            ->orderByDesc('id')
            ->get();

        // 역으로 이 회원을 지목한 상대 집합(상호 매칭 판정용).
        $votersOfMe = Vote::query()
            ->where('event_id', $eventId)
            ->where('target_id', $memberId)
            ->pluck('voter_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $currentYear = (int) now()->year;
        $labelMap    = $this->labelMap($eventId);
        $labeler     = app(MemberLabelService::class);

        $rows = $votes->map(function (Vote $vote) use ($votersOfMe, $currentYear, $labelMap, $labeler) {
            $target = $vote->target;
            $birthYear = $target?->birth_year;

            return [
                'id'         => (int) $vote->target_id,
                'name'       => $target?->name,
                'label'      => $labeler->label($labelMap, $target?->gender, $target?->name, $target?->platform),
                'gender'     => $target?->gender,
                'job'        => $target?->job,
                'phone'      => $target?->phone,
                'birth_year' => $birthYear,
                'age'        => $birthYear ? ($currentYear - (int) $birthYear + 1) : null,
                'height'     => $target?->height,
                'is_mutual'  => in_array((int) $vote->target_id, $votersOfMe, true),
                'voted_at'   => $vote->created_at?->toIso8601String(),
            ];
        })->all();

        // 매칭된 상대 먼저.
        usort($rows, fn ($a, $b) => ($b['is_mutual'] ? 1 : 0) <=> ($a['is_mutual'] ? 1 : 0));

        return $rows;
    }
}
