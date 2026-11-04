<?php

namespace App\Services\Rotation;

use App\Models\Member;
use App\Models\Vote;

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
     * 상대도 나를 선택한(상호 매칭) 상대는 target.is_mutual=true 로 표시하며,
     * 투표 시각(voted_at)은 그 voter 의 투표 중 가장 최신 1개만 노출한다.
     *
     * @return array<int, array{
     *     voter: array{id:int, name:?string, gender:?string, phone:?string, birth_year:?int, job:?string, height:?int, region:?string, is_smoker:?bool, mbti:?string},
     *     targets: array<int, array{id:int, name:?string, gender:?string, phone:?string, birth_year:?int, job:?string, height:?int, region:?string, is_smoker:?bool, mbti:?string, is_mutual:bool}>,
     *     has_mutual: bool,
     *     voted_at: ?string
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
                    'voted_at'   => null,
                ];
            }

            $isMutual = isset($votedMap[$vote->target_id][$vote->voter_id]);

            $grouped[$voterId]['targets'][] = $this->personPayload($vote->target, (int) $vote->target_id, $isMutual, $labelMap);

            if ($isMutual) {
                $grouped[$voterId]['has_mutual'] = true;
            }

            // 가장 최신 투표 시각 1개만 유지한다.
            $this->keepLatestVotedAt($grouped[$voterId], $vote);
        }

        $rows = array_values($grouped);

        // 각 사람의 선택 상대 목록도 매칭(상호)된 상대가 먼저 보이도록 정렬한다.
        foreach ($rows as &$row) {
            usort($row['targets'], function ($a, $b) {
                return ($b['is_mutual'] ? 1 : 0) <=> ($a['is_mutual'] ? 1 : 0);
            });
        }
        unset($row);

        // 상호 매칭이 있는 사람을 먼저, 그다음 최신 투표순으로 정렬한다. (페이지네이션 없이 전부 반환)
        usort($rows, function ($a, $b) {
            if ($a['has_mutual'] !== $b['has_mutual']) {
                return $b['has_mutual'] <=> $a['has_mutual']; // 상호 매칭 있는 사람 먼저
            }
            return ($b['voted_at'] ?? '') <=> ($a['voted_at'] ?? ''); // 최신순
        });

        return $rows;
    }

    /**
     * 특정 행사의 "상호 매칭된 쌍"만, 남자(voter) 기준으로 묶어 반환한다.
     * - 쌍방이 서로 선택한 경우만 포함(단방향 투표 제외).
     * - A↔B 쌍은 남자 쪽을 voter 로 하여 한 번만 노출한다. (성별이 모호하면 중복 방지를 위해 id 작은 쪽을 기준으로)
     * - voted_at 은 해당 남자의 매칭 투표 중 최신 1개만 표기한다.
     *
     * @return array<int, array{
     *     voter: array{id:int, name:?string, gender:?string, phone:?string, birth_year:?int, job:?string, height:?int, region:?string, is_smoker:?bool, mbti:?string},
     *     targets: array<int, array{id:int, name:?string, gender:?string, phone:?string, birth_year:?int, job:?string, height:?int, region:?string, is_smoker:?bool, mbti:?string, is_mutual:bool}>,
     *     has_mutual: bool,
     *     voted_at: ?string
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
                    'voted_at'   => null,
                ];
            }

            $grouped[$voterId]['targets'][] = $this->personPayload($vote->target, (int) $vote->target_id, true, $labelMap);

            $this->keepLatestVotedAt($grouped[$voterId], $vote);
        }

        $rows = array_values($grouped);

        // 최신 투표순 정렬.
        usort($rows, function ($a, $b) {
            return ($b['voted_at'] ?? '') <=> ($a['voted_at'] ?? '');
        });

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
            ->with(['voter:id,name,gender,platform,phone,birth_year,job,height,region,is_smoker,mbti', 'target:id,name,gender,platform,phone,birth_year,job,height,region,is_smoker,mbti'])
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
        ];

        if ($isMutual !== null) {
            $payload['is_mutual'] = $isMutual;
        }

        return $payload;
    }

    /**
     * 그룹 행에 들어있는 voted_at 을, 전달된 투표의 생성 시각과 비교해 더 최신값으로 갱신한다.
     *
     * @param array<string, mixed> $row
     */
    private function keepLatestVotedAt(array &$row, Vote $vote): void
    {
        $votedAt = $vote->created_at?->toIso8601String();
        if ($votedAt !== null && ($row['voted_at'] === null || $votedAt > $row['voted_at'])) {
            $row['voted_at'] = $votedAt;
        }
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
