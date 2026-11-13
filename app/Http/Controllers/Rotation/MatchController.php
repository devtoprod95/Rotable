<?php

namespace App\Http\Controllers\Rotation;

use App\Services\Rotation\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매칭 — 투표/상호 매칭 쌍 조회. 응답은 Tabulator 가 기대하는 { data, total } 형태.
 */
class MatchController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected MatchService $matchService,
    ) {
        parent::__construct($request);
    }

    /**
     * 행사의 투표/매칭 목록.
     * - matched=1 이면 "상호 매칭된 쌍"만 남자 기준으로 묶어 반환하고, total 은 쌍 수.
     * - 그 외에는 전체 투표를 선택한 사람 기준으로 묶어 반환한다.
     */
    public function eventMatchList(int $id): JsonResponse
    {
        $matchedOnly = $this->request->boolean('matched');

        $matches = $matchedOnly
            ? $this->matchService->matchedPairList($id)
            : $this->matchService->matchList($id);

        // 한 사람이 여러 명과 매칭될 수 있어 행 수 != 쌍 수 이므로 targets 합으로 센다.
        $total = $matchedOnly
            ? array_sum(array_map(static fn (array $row): int => count($row['targets'] ?? []), $matches))
            : count($matches);

        return response()->json(['data' => $matches, 'total' => $total]);
    }

    /**
     * 특정 회원을 "선택(지목)한 사람들" 목록.
     */
    public function eventMemberVoterList(int $id, int $memberId): JsonResponse
    {
        $voters = $this->matchService->memberVoterList($id, $memberId);

        return response()->json(['data' => $voters, 'total' => count($voters)]);
    }

    /**
     * 특정 회원이 "선택(투표)한 상대들" 목록 (매칭 여부 포함).
     */
    public function eventMemberVotedList(int $id, int $memberId): JsonResponse
    {
        $targets = $this->matchService->memberVotedTargetList($id, $memberId);

        return response()->json(['data' => $targets, 'total' => count($targets)]);
    }
}
