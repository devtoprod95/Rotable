<?php

namespace App\Services;

use App\Dtos\RotationTableListRequestDto;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationRound;
use App\Models\RotationSeating;
use App\Models\RotationTable;
use App\Models\Vote;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class RotationService
{
    protected array $returnMsg = [];

    public function __construct()
    {
        $this->returnMsg = helpersDefaultMessage();
    }

    /* ======================================================================
     | 행사(회차) 관리
     * ==================================================================== */

    /**
     * 행사 목록 (페이지네이션). 테이블 개수를 함께 집계한다.
     */
    /**
     * 행사 목록 (페이지네이션 + 검색). 테이블 개수와 성별 참여자 수를 함께 집계한다.
     * searchText는 행사명, 또는 참여 회원의 이름/전화번호로 검색한다.
     *
     * @param array{searchText?:string, type?:string, status?:string} $filters
     */
    public function eventList(int $page, int $size, array $sort, array $filters = []): LengthAwarePaginator
    {
        $query = EventRound::query()
            ->with(['creator:id,name'])
            ->withCount([
                'rotationTables',
                'messages',
                'adminRequests',
                'members as male_count'   => fn ($q) => $q->where('gender', Member::GENDER_MALE),
                'members as female_count' => fn ($q) => $q->where('gender', Member::GENDER_FEMALE),
            ]);

        if (!empty($filters['searchText'])) {
            $keyword = $filters['searchText'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                    ->orWhereHas('members', function ($mq) use ($keyword) {
                        $mq->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('phone', 'like', '%' . $keyword . '%');
                    });
            });
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($sort['field'])) {
            $query->orderBy($sort['field'], $sort['dir'] ?? 'desc');
        }

        $paginator = $query->paginate($size, ['*'], 'page', $page);

        // 현재 페이지 행사들의 "상호 매칭 쌍 수"를 self-join 으로 한 번에 집계해 주입한다.
        // (A→B 와 B→A 가 모두 있으면 2번 세지므로 2로 나눈다.)
        $eventIds = $paginator->getCollection()->pluck('id')->all();
        $matchCounts = [];
        if (!empty($eventIds)) {
            $rows = DB::table('event_votes as v1')
                ->join('event_votes as v2', function ($join) {
                    $join->on('v1.event_id', '=', 'v2.event_id')
                        ->on('v1.voter_id', '=', 'v2.target_id')
                        ->on('v1.target_id', '=', 'v2.voter_id');
                })
                ->whereIn('v1.event_id', $eventIds)
                ->groupBy('v1.event_id')
                ->selectRaw('v1.event_id as event_id, COUNT(*) as cnt')
                ->get();

            foreach ($rows as $row) {
                $matchCounts[(int) $row->event_id] = (int) ($row->cnt / 2);
            }
        }

        $paginator->getCollection()->transform(function (EventRound $event) use ($matchCounts) {
            $event->match_count = $matchCounts[$event->id] ?? 0;

            return $event;
        });

        return $paginator;
    }

    /**
     * 행사 생성. 상태는 무조건 대기(P)로 고정한다.
     * 테이블 개수(table_count)가 있으면 테이블과 0턴까지 함께 생성한다.
     *
     * @param object{title:string, type:string, event_time:string, table_count?:int} $data
     */
    public function eventStore(object $data): array
    {
        $returnMsg = $this->returnMsg;

        try {
            DB::transaction(function () use ($data) {
                $event = EventRound::create([
                    'title'      => $data->title,
                    'type'       => $data->type,
                    'event_time' => $data->event_time,
                    'status'     => EventRound::STATUS_PENDING, // 생성 시 상태는 무조건 대기(P)
                    'created_by' => $data->created_by ?? null,
                ]);

                $count = (int) ($data->table_count ?? 0);
                if ($count > 0) {
                    $rows = [];
                    for ($i = 1; $i <= $count; $i++) {
                        $rows[] = [
                            'event_id'   => $event->id,
                            'table_no'   => $i,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    RotationTable::insert($rows);

                    // 최초 턴(0턴) 보장 생성.
                    RotationRound::firstOrCreate(['event_id' => $event->id, 'round_no' => 0]);
                }
            });

            $returnMsg = helpersSuccessMessage('행사를 생성했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 행사 정보 수정 (제목/타입/일시). 상태는 로테이션 흐름에서만 변경되므로 건드리지 않는다.
     *
     * @param object{title:string, type:string, event_time:string} $data
     */
    public function eventUpdate(int $eventId, object $data): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $event = EventRound::find($eventId);
            if (!$event) {
                throw new Exception('행사를 찾을 수 없습니다.');
            }

            $event->title      = $data->title;
            $event->type       = $data->type;
            $event->event_time = $data->event_time;
            $event->save();

            $returnMsg = helpersSuccessMessage('행사 정보를 수정했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    public function eventDelete(array $ids): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $events = EventRound::whereIn('id', $ids)->get();
            if ($events->isEmpty()) {
                throw new Exception('행사를 찾을 수 없습니다.');
            }

            foreach ($events as $event) {
                $event->delete();
            }

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 행사 상세 (테이블 목록 + 참여자 목록 포함).
     */
    public function eventDetail(int $eventId): ?EventRound
    {
        return EventRound::with([
            'rotationTables' => fn ($q) => $q->orderBy('table_no'),
            'members'        => fn ($q) => $q
                ->withCount([
                    'sentMessages',
                    // 받은 투표수는 해당 행사(event_id) 범위로 제한.
                    'receivedVotes as received_votes_count' => fn ($vq) => $vq->where('event_id', $eventId),
                    // 보낸 요청수도 해당 행사 범위로 제한.
                    'adminRequests as admin_requests_count' => fn ($rq) => $rq->where('event_id', $eventId),
                ])
                ->orderBy('name'),
        ])->find($eventId);
    }

    /**
     * 특정 행사에 테이블 1개 추가 (다음 번호).
     */
    public function tableAdd(int $eventId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            DB::transaction(function () use ($eventId) {
                $maxNo = (int) RotationTable::where('event_id', $eventId)->max('table_no');
                RotationTable::create(['event_id' => $eventId, 'table_no' => $maxNo + 1]);

                // 0턴 보장 생성.
                RotationRound::firstOrCreate(['event_id' => $eventId, 'round_no' => 0]);
            });

            $returnMsg = helpersSuccessMessage('테이블을 추가했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /* ======================================================================
     | 메시지 (누가 누구에게 보냈는지)
     * ==================================================================== */

    /**
     * 특정 행사의 메시지 목록 (페이지네이션).
     * 보낸사람/받는사람 정보를 함께 로드하며, 읽음 필터와 검색을 지원한다.
     *
     * @param array{searchText?:string, read?:string, senderId?:int} $filters
     * @return LengthAwarePaginator
     */
    public function eventMessageList(int $eventId, int $page, int $size, array $sort, array $filters = []): LengthAwarePaginator
    {
        $query = \App\Models\Message::query()
            ->with(['sender:id,name,gender', 'receiver:id,name,gender'])
            ->where('event_id', $eventId);

        // 특정 회원이 "보낸" 메시지만 조회할 때.
        if (!empty($filters['senderId'])) {
            $query->where('sender_id', (int) $filters['senderId']);
        }

        if (!empty($filters['searchText'])) {
            $keyword = $filters['searchText'];
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('sender', fn ($sq) => $sq->where('name', 'like', '%' . $keyword . '%'))
                    ->orWhereHas('receiver', fn ($rq) => $rq->where('name', 'like', '%' . $keyword . '%'))
                    ->orWhere('body', 'like', '%' . $keyword . '%');
            });
        }

        if (isset($filters['read']) && $filters['read'] !== '') {
            if ($filters['read'] === 'Y') {
                $query->whereNotNull('read_at');
            } elseif ($filters['read'] === 'N') {
                $query->whereNull('read_at');
            }
        }

        if (!empty($sort['field'])) {
            $query->orderBy($sort['field'], $sort['dir'] ?? 'desc');
        } else {
            $query->orderByDesc('id');
        }

        return $query->paginate($size, ['*'], 'page', $page);
    }

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
        $votes = Vote::query()
            ->with(['voter:id,name,gender,phone,birth_year,job,height,region,is_smoker,mbti', 'target:id,name,gender,phone,birth_year,job,height,region,is_smoker,mbti'])
            ->where('event_id', $eventId)
            ->orderByDesc('id')
            ->get();

        // 상호 지목 판정용 맵 (voter_id => [target_id => true]).
        $votedMap = [];
        foreach ($votes as $vote) {
            $votedMap[$vote->voter_id][$vote->target_id] = true;
        }

        // 같은 선택한 사람(voter)끼리 묶는다.
        $grouped = [];
        foreach ($votes as $vote) {
            $voterId = (int) $vote->voter_id;

            if (!isset($grouped[$voterId])) {
                $grouped[$voterId] = [
                    'voter' => [
                        'id'         => $voterId,
                        'name'       => $vote->voter?->name,
                        'gender'     => $vote->voter?->gender,
                        'phone'      => $vote->voter?->phone,
                        'birth_year' => $vote->voter?->birth_year,
                        'job'        => $vote->voter?->job,
                        'height'     => $vote->voter?->height,
                        'region'     => $vote->voter?->region,
                        'is_smoker'  => $vote->voter?->is_smoker,
                        'mbti'       => $vote->voter?->mbti,
                    ],
                    'targets'    => [],
                    'has_mutual' => false,
                    'voted_at'   => null,
                ];
            }

            $isMutual = isset($votedMap[$vote->target_id][$vote->voter_id]);

            $grouped[$voterId]['targets'][] = [
                'id'         => (int) $vote->target_id,
                'name'       => $vote->target?->name,
                'gender'     => $vote->target?->gender,
                'phone'      => $vote->target?->phone,
                'birth_year' => $vote->target?->birth_year,
                'job'        => $vote->target?->job,
                'height'     => $vote->target?->height,
                'region'     => $vote->target?->region,
                'is_smoker'  => $vote->target?->is_smoker,
                'mbti'       => $vote->target?->mbti,
                'is_mutual'  => $isMutual,
            ];

            if ($isMutual) {
                $grouped[$voterId]['has_mutual'] = true;
            }

            // 가장 최신 투표 시각 1개만 유지한다.
            $votedAt = $vote->created_at?->toIso8601String();
            if ($votedAt !== null && ($grouped[$voterId]['voted_at'] === null || $votedAt > $grouped[$voterId]['voted_at'])) {
                $grouped[$voterId]['voted_at'] = $votedAt;
            }
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
        $votes = Vote::query()
            ->with(['voter:id,name,gender,phone,birth_year,job,height,region,is_smoker,mbti', 'target:id,name,gender,phone,birth_year,job,height,region,is_smoker,mbti'])
            ->where('event_id', $eventId)
            ->orderByDesc('id')
            ->get();

        // 상호 지목 판정용 맵 (voter_id => [target_id => true]).
        $votedMap = [];
        foreach ($votes as $vote) {
            $votedMap[$vote->voter_id][$vote->target_id] = true;
        }

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
                    'voter' => [
                        'id'         => $voterId,
                        'name'       => $vote->voter?->name,
                        'gender'     => $vote->voter?->gender,
                        'phone'      => $vote->voter?->phone,
                        'birth_year' => $vote->voter?->birth_year,
                        'job'        => $vote->voter?->job,
                        'height'     => $vote->voter?->height,
                        'region'     => $vote->voter?->region,
                        'is_smoker'  => $vote->voter?->is_smoker,
                        'mbti'       => $vote->voter?->mbti,
                    ],
                    'targets'    => [],
                    'has_mutual' => true,
                    'voted_at'   => null,
                ];
            }

            $grouped[$voterId]['targets'][] = [
                'id'         => (int) $vote->target_id,
                'name'       => $vote->target?->name,
                'gender'     => $vote->target?->gender,
                'phone'      => $vote->target?->phone,
                'birth_year' => $vote->target?->birth_year,
                'job'        => $vote->target?->job,
                'height'     => $vote->target?->height,
                'region'     => $vote->target?->region,
                'is_smoker'  => $vote->target?->is_smoker,
                'mbti'       => $vote->target?->mbti,
                'is_mutual'  => true,
            ];

            $votedAt = $vote->created_at?->toIso8601String();
            if ($votedAt !== null && ($grouped[$voterId]['voted_at'] === null || $votedAt > $grouped[$voterId]['voted_at'])) {
                $grouped[$voterId]['voted_at'] = $votedAt;
            }
        }

        $rows = array_values($grouped);

        // 최신 투표순 정렬.
        usort($rows, function ($a, $b) {
            return ($b['voted_at'] ?? '') <=> ($a['voted_at'] ?? '');
        });

        return $rows;
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
            ->with(['voter:id,name,gender,phone,birth_year,job,height'])
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

        $rows = $votes->map(function (Vote $vote) use ($myTargets, $currentYear) {
            $voter = $vote->voter;
            $birthYear = $voter?->birth_year;

            return [
                'id'         => (int) $vote->voter_id,
                'name'       => $voter?->name,
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

    public function tableList(RotationTableListRequestDto $dto): LengthAwarePaginator
    {
        $query = RotationTable::query()->with('eventRound');

        if (!empty($dto->eventId)) {
            $query->where('event_id', $dto->eventId);
        }
        if (!empty($dto->searchText)) {
            $query->where('table_no', 'like', '%' . $dto->searchText . '%');
        }
        if (!empty($dto->sort)) {
            $query->orderBy($dto->sort['field'], $dto->sort['dir']);
        }

        return $query->paginate($dto->size, ['*'], 'page', $dto->page);
    }

    public function tableDelete(array $ids): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $objs = RotationTable::whereIn('id', $ids)->get();
            if ($objs->isEmpty()) {
                throw new Exception('테이블을 찾을 수 없습니다.');
            }

            foreach ($objs as $obj) {
                $obj->delete();
            }

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /* ======================================================================
     | 좌석 배정 (단일 화면)
     * ==================================================================== */

    /**
     * 행사·턴 기준 테이블 그리드 데이터 + 로테이션 메타 정보.
     * 턴 레코드가 아직 없으면 빈 좌석으로 표시한다. (좌석 추가 시 자동 생성)
     *
     * @return array{
     *     tables: array<int, array<string, mixed>>,
     *     meta: array{event_status:string, table_count:int, current_round:int, is_last:bool, is_closed:bool}
     * }
     */
    public function seatingBoard(int $eventId, int $roundNo): array
    {
        // 좌석이 존재하는 최대 턴(현재 로테이션 진행 지점). 없으면 0.
        $currentRound = (int) RotationRound::where('event_id', $eventId)
            ->whereHas('seatings')
            ->max('round_no');

        // roundNo가 음수(-1 등)면 "최신 턴"을 자동 선택한다. (URL 직접 진입 시 최신 턴 표시)
        if ($roundNo < 0) {
            $roundNo = $currentRound;
        }

        $round = RotationRound::where('event_id', $eventId)
            ->where('round_no', $roundNo)
            ->first();

        $seatingsByTable = collect();
        if ($round) {
            $seatingsByTable = RotationSeating::with('member')
                ->where('rotation_round_id', $round->id)
                ->get()
                ->groupBy('rotation_table_id');
        }

        $tables = RotationTable::where('event_id', $eventId)
            ->orderBy('table_no')
            ->get();

        $tableCount = $tables->count();

        $tableData = $tables->map(function (RotationTable $table) use ($seatingsByTable) {
                $seatings = $seatingsByTable->get($table->id, collect());

                return [
                    'id'       => $table->id,
                    'table_no' => $table->table_no,
                    'members'  => $seatings->map(fn (RotationSeating $s) => [
                        'seating_id' => $s->id,
                        'member_id'  => $s->member_id,
                        'name'       => $s->member?->name,
                        'gender'     => $s->member?->gender,
                        'job'        => $s->member?->job,
                        'birth_year' => $s->member?->birth_year,
                    ])->values(),
                ];
            });

        $eventStatus = EventRound::whereKey($eventId)->value('status') ?? EventRound::STATUS_PENDING;

        return [
            'tables' => $tableData,
            'meta'   => [
                'event_status' => $eventStatus,
                'table_count'  => $tableCount,
                'current_round' => $currentRound,
                // 테이블 N개면 N턴에서 남자가 제자리 복귀 → 마지막 턴.
                'is_last'      => $tableCount > 0 && $currentRound >= $tableCount,
                'is_voting'    => $eventStatus === EventRound::STATUS_VOTING,
                'is_closed'    => $eventStatus === EventRound::STATUS_CLOSED,
            ],
        ];
    }

    /**
     * 행사의 회원 목록 (셀렉트용).
     *
     * @return array<int, array<string, mixed>>
     */
    public function seatingMembers(int $eventId): array
    {
        return Member::where('event_id', $eventId)
            ->orderBy('name')
            ->get()
            ->map(fn (Member $m) => [
                'id'         => $m->id,
                'name'       => $m->name,
                'gender'     => $m->gender,
                'job'        => $m->job,
                'birth_year' => $m->birth_year,
            ])
            ->toArray();
    }

    /**
     * 좌석 추가. 턴(round)이 없으면 자동 생성(find-or-create).
     * 한 회원이 같은 턴에 이미 앉아있으면 테이블만 옮긴다.
     *
     * @param object{event_id:int, round_no:int, rotation_table_id:int, member_id:int} $data
     */
    public function seatingUpsert(object $data): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $eventId = (int) $data->event_id;

            // 종료(X)된 행사는 좌석(회원) 추가/이동을 막는다.
            $event = EventRound::find($eventId);
            if ($event === null) {
                throw new Exception('행사를 찾을 수 없습니다.');
            }
            if ($event->status === EventRound::STATUS_CLOSED) {
                throw new Exception('종료된 행사에는 회원을 추가할 수 없습니다.');
            }

            $round = RotationRound::firstOrCreate(
                ['event_id' => $eventId, 'round_no' => (int) $data->round_no]
            );

            // 같은 턴에 이미 앉아있으면 테이블만 갱신(이동), 없으면 새로 생성.
            $seating = RotationSeating::firstOrNew([
                'rotation_round_id' => $round->id,
                'member_id'         => (int) $data->member_id,
            ]);
            $seating->rotation_table_id = (int) $data->rotation_table_id;
            $seating->save();

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    public function seatingDelete(int $id): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $seating = RotationSeating::find($id);
            if (!$seating) {
                throw new Exception('좌석을 찾을 수 없습니다.');
            }

            $seating->delete();

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 로테이션 진행: 현재 턴을 기준으로 다음 턴 좌석을 자동 생성한다.
     *
     * 규칙:
     * - 여자: 현재 앉은 테이블에 그대로 고정.
     * - 남자: 진행할 때마다 테이블 번호 순서상 한 칸 이동(마지막→첫번째 순환).
     *
     * 현재 턴(가장 큰 round_no, 0턴 포함)의 좌석을 읽어 다음 턴(round_no + 1)에 배정하고,
     * 행사 상태를 '진행(S)'으로 전환한다.
     *
     * 마지막 턴 판단: 테이블이 N개면 round_no 가 N 이 될 때 남자가 한 바퀴 돌아 제자리로 복귀하므로
     * 그 턴을 마지막 턴으로 본다. (0턴 세팅 → 1..N턴 로테이션)
     *
     * @return array{isSuccess:bool, message?:string, data?:array{round_no:int, is_last:bool, max_round:int}}
     */
    public function rotateProgress(int $eventId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            // 정렬된 테이블 목록 (남자 이동 기준)
            $tables = RotationTable::where('event_id', $eventId)
                ->orderBy('table_no')
                ->get();

            if ($tables->isEmpty()) {
                throw new Exception('생성된 테이블이 없습니다. 먼저 테이블을 생성해주세요.');
            }

            // 테이블 id -> 다음 테이블 id 매핑 (마지막 → 첫번째 순환)
            $nextTableId = [];
            $tableCount = $tables->count();
            foreach ($tables as $i => $table) {
                $nextTableId[$table->id] = $tables[($i + 1) % $tableCount]->id;
            }

            // 현재 턴 = 좌석이 존재하는 가장 큰 round_no (0턴 포함)
            $currentRound = RotationRound::where('event_id', $eventId)
                ->whereHas('seatings')
                ->orderByDesc('round_no')
                ->first();

            if (!$currentRound) {
                throw new Exception('현재 턴에 배정된 좌석이 없습니다. 0턴을 먼저 세팅해주세요.');
            }

            $nextRoundNo = $currentRound->round_no + 1;

            // 테이블이 N개면 N턴이 마지막(남자가 제자리 복귀).
            $isLast = $nextRoundNo >= $tableCount;

            // 0턴 → 1턴은 세팅 그대로 복사(남자도 이동 없음). 1턴부터 남자 +1 이동.
            $shouldMoveMale = $currentRound->round_no >= 1;

            $result = DB::transaction(function () use ($eventId, $currentRound, $nextRoundNo, $nextTableId, $shouldMoveMale) {
                $nextRound = RotationRound::firstOrCreate(
                    ['event_id' => $eventId, 'round_no' => $nextRoundNo]
                );

                // 다음 턴 좌석을 새로 계산하므로 기존 다음 턴 좌석은 비우고 재생성.
                RotationSeating::where('rotation_round_id', $nextRound->id)->delete();

                $currentSeatings = RotationSeating::with('member')
                    ->where('rotation_round_id', $currentRound->id)
                    ->get();

                $rows = [];
                foreach ($currentSeatings as $seating) {
                    $gender = $seating->member?->gender;

                    // 여자: 항상 고정. 남자: 0→1턴은 고정, 1턴부터 다음 테이블로 +1 이동.
                    $moveThis = $shouldMoveMale && $gender !== Member::GENDER_FEMALE;
                    $tableId = $moveThis
                        ? ($nextTableId[$seating->rotation_table_id] ?? $seating->rotation_table_id)
                        : $seating->rotation_table_id;

                    $rows[] = [
                        'rotation_round_id' => $nextRound->id,
                        'rotation_table_id' => $tableId,
                        'member_id'         => $seating->member_id,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }

                if (!empty($rows)) {
                    RotationSeating::insert($rows);
                }

                // 로테이션이 시작되었으므로 행사 상태를 '진행(S)'으로.
                EventRound::where('id', $eventId)
                    ->update(['status' => EventRound::STATUS_STARTED]);

                return $nextRound->round_no;
            });

            $returnMsg = helpersSuccessMessage("{$result}턴으로 로테이션을 진행했습니다.");
            $returnMsg['data'] = [
                'round_no'  => $result,
                'is_last'   => $isLast,
                'max_round' => $tableCount,
            ];
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 투표 시작: 로테이션이 끝난 행사의 상태를 '투표중(V)'으로 전환한다.
     * 투표가 열린 시각(voting_opened_at)을 기록한다. (자동 마감 기준점)
     *
     * @return array{isSuccess:bool, message?:string}
     */
    public function openVoting(int $eventId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $event = EventRound::find($eventId);
            if (!$event) {
                throw new Exception('행사를 찾을 수 없습니다.');
            }

            $event->status           = EventRound::STATUS_VOTING;
            $event->voting_opened_at = now();
            $event->save();

            $returnMsg = helpersSuccessMessage('매칭을 시작했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 투표 마감: 행사 상태를 '종료(X)'로 전환한다.
     * (수동 마감 버튼 및 자동 종료 스케줄러에서 공통으로 사용)
     *
     * @return array{isSuccess:bool, message?:string}
     */
    public function closeVoting(int $eventId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $event = EventRound::find($eventId);
            if (!$event) {
                throw new Exception('행사를 찾을 수 없습니다.');
            }

            $event->status = EventRound::STATUS_CLOSED;
            $event->save();

            $returnMsg = helpersSuccessMessage('매칭을 마감했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /* ======================================================================
     | 상세페이지 디자이너 — 공용 템플릿 HTML 1개를 storage 파일에 저장/로드
     * ==================================================================== */

    /** 상세페이지 HTML 저장 경로 (storage/app/private/rotation/detail.html) */
    private const DETAIL_HTML_PATH = 'rotation/detail.html';

    /**
     * 저장된 상세페이지 HTML 을 반환한다.
     * 저장된 파일이 없으면 기본 샘플(2열) 을 렌더링해서 돌려준다.
     */
    public function detailHtmlLoad(): string
    {
        if (Storage::exists(self::DETAIL_HTML_PATH)) {
            return (string) Storage::get(self::DETAIL_HTML_PATH);
        }

        return $this->detailSampleHtml(2);
    }

    /**
     * 기본 샘플 HTML 을 렌더링한다.
     * 실제 행사 데이터와 무관하게 더미 참여자(남/여 각 5명) 로 구성한다.
     *
     * @param int $columns 행사 배치 열 수 (1 또는 2)
     */
    public function detailSampleHtml(int $columns = 2): string
    {
        return trim(View::make('admin.rotation._detailSample', [
            'columns' => $columns === 1 ? 1 : 2,
        ])->render());
    }

    /**
     * 상세페이지 HTML 을 storage 파일에 저장한다.
     *
     * @return array{isSuccess:bool, message?:string}
     */
    public function detailHtmlSave(string $html): array
    {
        $returnMsg = $this->returnMsg;

        try {
            Storage::put(self::DETAIL_HTML_PATH, $html);
            $returnMsg = helpersSuccessMessage('상세페이지를 저장했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }
}
