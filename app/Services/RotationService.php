<?php

namespace App\Services;

use App\Dtos\RotationTableListRequestDto;
use App\Events\EventClosed;
use App\Jobs\SendCloseVotingReportJob;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationSeat;
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

            DB::transaction(function () use ($ids, $events) {
                // members.event_id FK 는 restrict 이므로, 행사를 지우기 전에
                // 해당 행사의 회원을 먼저 물리 삭제한다. (Member 는 SoftDeletes 라 forceDelete 필요)
                // 회원을 참조하는 자식들(event_votes·messages·admin_requests·rotation_seats)은
                // member_id FK 가 cascade 라 함께 정리된다.
                Member::whereIn('event_id', $ids)->forceDelete();

                // 행사 삭제. rotation_tables(및 rotation_seats 의 event_id) 는 cascade 로 정리된다.
                foreach ($events as $event) {
                    $event->delete();
                }
            });

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 행사 상세 (테이블 목록 + 참여자 목록 포함).
     * 각 참여자에는 "그 전화번호의 몇 번째 방문인지"(visit_ordinal)를 함께 주입한다.
     */
    public function eventDetail(int $eventId): ?EventRound
    {
        $event = EventRound::with([
            'rotationTables' => fn ($q) => $q->orderBy('table_no'),
            'members'        => fn ($q) => $q
                ->withCount([
                    'sentMessages',
                    // 받은 투표수는 해당 행사(event_id) 범위로 제한.
                    'receivedVotes as received_votes_count' => fn ($vq) => $vq->where('event_id', $eventId),
                    // 보낸 투표수(내가 선택한 상대 수)도 해당 행사 범위로 제한.
                    'castVotes as cast_votes_count' => fn ($vq) => $vq->where('event_id', $eventId),
                    // 보낸 요청수도 해당 행사 범위로 제한.
                    'adminRequests as admin_requests_count' => fn ($rq) => $rq->where('event_id', $eventId),
                ])
                ->orderBy('name'),
        ])->find($eventId);

        if ($event !== null) {
            // 참여자 전화번호 기준 "행사 유형별 누적 방문 횟수"를 각 회원에 주입한다.
            // (예: visit_counts = ['wine' => 3, 'one' => 2])
            $countsByPhone = app(VisitStatService::class)
                ->visitCountsByType($event->members->pluck('phone')->all());

            foreach ($event->members as $member) {
                $member->visit_counts = $countsByPhone[$member->phone] ?? [];
            }
        }

        return $event;
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
     * @param array{searchText?:string, read?:string, senderId?:int, convA?:int, convB?:int} $filters
     * @return LengthAwarePaginator
     */
    public function eventMessageList(int $eventId, int $page, int $size, array $sort, array $filters = []): LengthAwarePaginator
    {
        $query = \App\Models\Message::query()
            ->with(['sender:id,name,gender', 'receiver:id,name,gender'])
            ->where('event_id', $eventId);

        // 두 회원 "사이"의 대화(양방향)만 조회할 때. (convA ↔ convB)
        if (!empty($filters['convA']) && !empty($filters['convB'])) {
            $a = (int) $filters['convA'];
            $b = (int) $filters['convB'];
            $query->where(function ($q) use ($a, $b) {
                $q->where(fn ($qq) => $qq->where('sender_id', $a)->where('receiver_id', $b))
                    ->orWhere(fn ($qq) => $qq->where('sender_id', $b)->where('receiver_id', $a));
            });
        }

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
        [$votes, $votedMap] = $this->loadVotesWithMutualMap($eventId);

        // 같은 선택한 사람(voter)끼리 묶는다.
        $grouped = [];
        foreach ($votes as $vote) {
            $voterId = (int) $vote->voter_id;

            if (!isset($grouped[$voterId])) {
                $grouped[$voterId] = [
                    'voter'      => $this->personPayload($vote->voter, $voterId),
                    'targets'    => [],
                    'has_mutual' => false,
                    'voted_at'   => null,
                ];
            }

            $isMutual = isset($votedMap[$vote->target_id][$vote->voter_id]);

            $grouped[$voterId]['targets'][] = $this->personPayload($vote->target, (int) $vote->target_id, $isMutual);

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
                    'voter'      => $this->personPayload($vote->voter, $voterId),
                    'targets'    => [],
                    'has_mutual' => true,
                    'voted_at'   => null,
                ];
            }

            $grouped[$voterId]['targets'][] = $this->personPayload($vote->target, (int) $vote->target_id, true);

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
            ->with(['voter:id,name,gender,phone,birth_year,job,height,region,is_smoker,mbti', 'target:id,name,gender,phone,birth_year,job,height,region,is_smoker,mbti'])
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
     * 매칭/투표 응답용 회원 상세 payload 를 만든다.
     * $isMutual 이 전달되면 target 용(상호 매칭 플래그 포함) payload 로 간주한다.
     *
     * @return array<string, mixed>
     */
    private function personPayload(?Member $member, int $id, ?bool $isMutual = null): array
    {
        $payload = [
            'id'         => $id,
            'name'       => $member?->name,
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
            ->with(['target:id,name,gender,phone,birth_year,job,height'])
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

        $rows = $votes->map(function (Vote $vote) use ($votersOfMe, $currentYear) {
            $target = $vote->target;
            $birthYear = $target?->birth_year;

            return [
                'id'         => (int) $vote->target_id,
                'name'       => $target?->name,
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
        $event       = EventRound::find($eventId);
        $eventStatus = $event?->status ?? EventRound::STATUS_PENDING;

        // 현재 로테이션 턴 (0 = 시작 전 = 기준자리 편집 뷰).
        $currentRound = (int) ($event?->current_round ?? 0);

        // roundNo < 0 이면 "현재 턴"을 본다. (URL 직접 진입 시)
        if ($roundNo < 0) {
            $roundNo = $currentRound;
        }

        // 테이블 목록 (오름차순). 남자 이동 공식의 기준.
        $tables   = RotationTable::where('event_id', $eventId)
            ->orderBy('table_no')
            ->get();
        $tableCount = $tables->count();
        $tableNos   = $tables->pluck('table_no')->map(fn ($n) => (int) $n)->all();

        // 이 행사의 기준(홈) 좌석 전부 로드.
        $seats = RotationSeat::with('member')
            ->where('event_id', $eventId)
            ->get();

        // 각 기준 좌석을, 보고 있는 턴(roundNo)의 실제 테이블 번호로 매핑해 테이블별로 묶는다.
        // roundNo <= 0 (기준자리 편집 뷰)이면 홈 테이블 그대로 보여준다.
        $viewRound = $roundNo <= 0 ? 1 : $roundNo; // 1턴 = 기준자리 그대로
        $seatingsByTableNo = $seats->groupBy(function (RotationSeat $seat) use ($viewRound, $tableNos) {
            $homeNo = (int) ($seat->member && $seat->table ? $seat->table->table_no : 0);
            if ($homeNo === 0) {
                return 0;
            }

            return EventRound::seatedTableNoFor(
                round: $viewRound,
                isFemale: $seat->member?->gender === Member::GENDER_FEMALE,
                homeTableNo: $homeNo,
                tableNos: $tableNos,
            );
        });

        $tableData = $tables->map(function (RotationTable $table) use ($seatingsByTableNo) {
                $seatsHere = $seatingsByTableNo->get($table->table_no, collect());

                return [
                    'id'       => $table->id,
                    'table_no' => $table->table_no,
                    'members'  => $seatsHere->map(fn (RotationSeat $s) => [
                        'seating_id' => $s->id,
                        'member_id'  => $s->member_id,
                        'name'       => $s->member?->name,
                        'gender'     => $s->member?->gender,
                        'job'        => $s->member?->job,
                        'birth_year' => $s->member?->birth_year,
                    ])->values(),
                ];
            });

        return [
            'tables' => $tableData,
            'meta'   => [
                'event_status'  => $eventStatus,
                'table_count'   => $tableCount,
                'current_round' => $currentRound,
                'view_round'    => $roundNo,
                // 테이블 N개면 N턴에서 남자가 제자리 복귀 → 마지막 턴.
                'is_last'       => $tableCount > 0 && $currentRound >= $tableCount,
                'is_voting'     => $eventStatus === EventRound::STATUS_VOTING,
                'is_closed'     => $eventStatus === EventRound::STATUS_CLOSED,
                // 1:1 로테이션 여부. 1:1은 매칭 선택을 '홈'에서 받으므로
                // 마지막 턴에서 '매칭 시작(V)' 없이 바로 '매칭 종료(X)'한다.
                'is_one_to_one' => $event !== null && $event->isOneToOne(),
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
     * 기준(홈) 좌석 추가/이동. 회원당 1행이며, 이미 있으면 테이블만 옮긴다.
     * 여자는 이 테이블에 고정, 남자는 1턴 출발 테이블이 된다.
     *
     * @param object{event_id:int, rotation_table_id:int, member_id:int} $data
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

            // 회원의 기준(홈) 자리를 upsert. 이미 있으면 테이블만 갱신(이동).
            $seat = RotationSeat::firstOrNew([
                'event_id'  => $eventId,
                'member_id' => (int) $data->member_id,
            ]);
            $seat->rotation_table_id = (int) $data->rotation_table_id;
            $seat->save();

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
            $seat = RotationSeat::find($id);
            if (!$seat) {
                throw new Exception('좌석을 찾을 수 없습니다.');
            }

            $seat->delete();

            $returnMsg = helpersSuccessMessage();
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 로테이션 진행: 현재 턴(event_rounds.current_round)을 +1 한다.
     *
     * 턴별 좌석은 저장하지 않고 기준자리 + current_round 로 계산하므로,
     * 여기서는 좌석을 복제하지 않고 턴 번호만 올리면 된다.
     *
     * 규칙(계산은 EventRound::seatedTableNoFor):
     * - 1턴은 기준자리 그대로. 2턴부터 남자만 +1 이동(마지막→첫번째 순환), 여자 고정.
     * - 테이블이 N개면 N턴이 마지막(남자가 제자리 복귀).
     *
     * 최초 진행(current_round 0→1) 시 행사 상태를 '진행(S)'으로 전환한다.
     *
     * @return array{isSuccess:bool, message?:string, data?:array{round_no:int, is_last:bool, max_round:int}}
     */
    public function rotateProgress(int $eventId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $tableCount = RotationTable::where('event_id', $eventId)->count();
            if ($tableCount === 0) {
                throw new Exception('생성된 테이블이 없습니다. 먼저 테이블을 생성해주세요.');
            }

            // 최초 진행(0→1턴)이라면 기준자리가 하나라도 있어야 한다.
            $hasSeats = RotationSeat::where('event_id', $eventId)->exists();
            if (! $hasSeats) {
                throw new Exception('배정된 좌석이 없습니다. 먼저 회원을 테이블에 배정해주세요.');
            }

            $nextRoundNo = DB::transaction(function () use ($eventId, $tableCount) {
                /** @var EventRound $event */
                $event = EventRound::lockForUpdate()->find($eventId);
                if ($event === null) {
                    throw new Exception('행사를 찾을 수 없습니다.');
                }

                $next = (int) $event->current_round + 1;

                // 마지막 턴(N)을 넘겨서 진행하지 않도록 상한을 둔다.
                if ($next > $tableCount) {
                    throw new Exception('이미 마지막 턴입니다. 더 이상 진행할 수 없습니다.');
                }

                $event->current_round = $next;
                $event->status        = EventRound::STATUS_STARTED;

                // 1:1 소개팅은 별도 "투표 시작(V)" 단계 없이 진행(S)부터 매칭 선택이 열린다.
                // 로테이션이 처음 시작되는 시점에 voting_opened_at 을 기록한다(이미 있으면 유지).
                if ($event->isOneToOne() && $event->voting_opened_at === null) {
                    $event->voting_opened_at = now();
                }

                $event->save();

                return $next;
            });

            // 테이블이 N개면 N턴이 마지막(남자가 제자리 복귀).
            $isLast = $nextRoundNo >= $tableCount;

            $returnMsg = helpersSuccessMessage("{$nextRoundNo}턴으로 로테이션을 진행했습니다.");
            $returnMsg['data'] = [
                'round_no'  => $nextRoundNo,
                'is_last'   => $isLast,
                'max_round' => $tableCount,
            ];

            // 참가자 홈을 실시간으로 갱신(새 턴 반영). 와인파티·1:1 공통.
            broadcast(new \App\Events\RotationUpdated($eventId));
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 로테이션 되돌리기: 현재 턴(event_rounds.current_round)을 1 줄인다.
     *
     * 실수로 "로테이션 진행"을 한 번 더 눌렀을 때 직전 턴으로 복구한다.
     * 턴별 좌석을 저장하지 않으므로 숫자만 되돌리면 자리는 공식으로 다시 계산된다.
     *
     * - current_round 가 0 이면(아직 시작 전) 되돌릴 턴이 없다.
     * - 매칭중(V)/종료(X) 상태에서는 되돌리지 않는다. (매칭 단계로 넘어간 뒤이므로)
     * - 1→0 으로 되돌리면 로테이션 시작 전으로 간주해 상태를 대기(P)로 되돌린다.
     *
     * @return array{isSuccess:bool, message?:string, data?:array{round_no:int, is_last:bool, max_round:int}}
     */
    public function rotateBack(int $eventId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $tableCount = RotationTable::where('event_id', $eventId)->count();

            $result = DB::transaction(function () use ($eventId) {
                /** @var EventRound $event */
                $event = EventRound::lockForUpdate()->find($eventId);
                if ($event === null) {
                    throw new Exception('행사를 찾을 수 없습니다.');
                }

                if (in_array($event->status, [EventRound::STATUS_VOTING, EventRound::STATUS_CLOSED], true)) {
                    throw new Exception('매칭이 시작/종료된 뒤에는 로테이션을 되돌릴 수 없습니다.');
                }

                $current = (int) $event->current_round;
                if ($current < 1) {
                    throw new Exception('되돌릴 턴이 없습니다. (아직 로테이션 시작 전)');
                }

                $prev = $current - 1;
                $event->current_round = $prev;

                // 1 → 0 이면 로테이션 시작 전으로 되돌아가므로 상태를 대기(P)로.
                // 1:1 은 진행 시 기록한 voting_opened_at 도 되돌린다.
                if ($prev === 0) {
                    $event->status = EventRound::STATUS_PENDING;
                    if ($event->isOneToOne()) {
                        $event->voting_opened_at = null;
                    }
                }

                $event->save();

                return $prev;
            });

            $isLast = $tableCount > 0 && $result >= $tableCount;

            $returnMsg = helpersSuccessMessage(
                $result === 0
                    ? '로테이션을 시작 전(0턴)으로 되돌렸습니다.'
                    : "{$result}턴으로 되돌렸습니다.",
            );
            $returnMsg['data'] = [
                'round_no'  => $result,
                'is_last'   => $isLast,
                'max_round' => $tableCount,
            ];

            broadcast(new \App\Events\RotationUpdated($eventId));
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

            // 참가자 화면을 실시간으로 갱신하여 '매칭하기' 탭을 바로 노출. 와인파티·1:1 공통.
            broadcast(new \App\Events\EventVotingOpened($event->id));

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
    public function closeVoting(int $eventId, string $trigger = 'manual'): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $event = EventRound::find($eventId);
            if (!$event) {
                throw new Exception('행사를 찾을 수 없습니다.');
            }

            $event->status = EventRound::STATUS_CLOSED;
            $event->save();

            // 참가자 화면을 실시간으로 갱신하여 매칭 결과를 바로 노출. 와인파티·1:1 공통.
            broadcast(new EventClosed($event->id));

            // 운영진 텔레그램 요약 리포트는 비동기(Job)로 전송한다. (마감 응답을 지연시키지 않음)
            SendCloseVotingReportJob::dispatch($event->id, $trigger);

            $returnMsg = helpersSuccessMessage('매칭을 마감했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 투표(매칭) 종료 시 운영진 텔레그램으로 요약 리포트를 보낸다.
     * SendCloseVotingReportJob(비동기 큐)에서 호출된다.
     * 전송 자체가 실패하더라도 예외를 삼켜 Job 이 과도하게 재시도하지 않도록 한다.
     *
     * @param  int     $eventId  종료된 행사 id
     * @param  string  $trigger  마감 트리거: 'manual'(수동) | 'auto'(자동 9시 마감)
     */
    public function sendCloseVotingReport(int $eventId, string $trigger): void
    {
        try {
            $notifier = TelegramNotifier::fromConfig();
            if (! $notifier->isConfigured()) {
                return; // 미설정이면 조립 비용 없이 건너뛴다.
            }

            $event = EventRound::find($eventId);
            if ($event === null) {
                return; // 큐 처리 시점에 행사가 사라졌으면 조용히 종료.
            }

            $notifier->send($this->buildCloseVotingReport($event, $trigger));
        } catch (\Throwable $th) {
            channelLog(message: $th, channel: 'telegram', level: 'error');
        }
    }

    /**
     * 종료 요약 리포트 본문(텔레그램 HTML)을 조립한다.
     *
     * 포함 정보: 행사명/타입/일시, 마감 방식(수동/자동), 참가 수(남/여/합계),
     * 투표 참여자 수, 상호 매칭 쌍 수, 메시지/요청 건수.
     *
     * @param  string  $trigger  'manual' | 'auto'
     */
    private function buildCloseVotingReport(EventRound $event, string $trigger): string
    {
        $maleCount   = $event->members()->where('gender', Member::GENDER_MALE)->count();
        $femaleCount = $event->members()->where('gender', Member::GENDER_FEMALE)->count();
        $totalCount  = $maleCount + $femaleCount;

        // 투표를 1표라도 한 고유 참가자 수(성별별). voter_id 를 members 와 조인해 집계한다.
        $voterCount = Vote::query()
            ->where('event_id', $event->id)
            ->distinct('voter_id')
            ->count('voter_id');

        $maleVoterCount = Vote::query()
            ->where('event_votes.event_id', $event->id)
            ->join('members', 'members.id', '=', 'event_votes.voter_id')
            ->where('members.gender', Member::GENDER_MALE)
            ->distinct('event_votes.voter_id')
            ->count('event_votes.voter_id');

        $femaleVoterCount = Vote::query()
            ->where('event_votes.event_id', $event->id)
            ->join('members', 'members.id', '=', 'event_votes.voter_id')
            ->where('members.gender', Member::GENDER_FEMALE)
            ->distinct('event_votes.voter_id')
            ->count('event_votes.voter_id');

        // 상호 매칭 쌍 수 (남자 기준 1회 노출된 쌍 목록의 길이).
        $pairCount = count($this->matchedPairList($event->id));

        $messageCount = $event->messages()->count();
        $requestCount = $event->adminRequests()->count();

        $typeLabel    = EventRound::typeOptions()[$event->type] ?? $event->type;
        $triggerLabel = $trigger === 'auto' ? '자동 마감 (오전 9시)' : '수동 마감';
        $timeLabel    = $event->event_time
            ? $event->event_time->format('Y-m-d H:i')
            : '(미지정)';

        $title = e($event->title);

        return implode("\n", [
            '🏁 <b>매칭 종료 알림</b>',
            '',
            "🍷 <b>행사명</b> : {$title}",
            "📋 <b>행사종류</b> : {$typeLabel}",
            "🗓 <b>행사일시</b> : {$timeLabel}",
            "⏱ <b>마감종류</b> : {$triggerLabel}",
            '',
            '👥 <b>참가</b> : 총 ' . number_format($totalCount) . '명 (남 ' . number_format($maleCount) . ' / 여 ' . number_format($femaleCount) . ')',
            '🗳 <b>투표 참여</b> : ' . number_format($voterCount) . '명 (남 ' . number_format($maleVoterCount) . ' / 여 ' . number_format($femaleVoterCount) . ')',
            '❤️ <b>상호 매칭</b> : ' . number_format($pairCount) . '쌍',
            '',
            '💬 메시지 ' . number_format($messageCount) . '건 · 📨 요청 ' . number_format($requestCount) . '건',
        ]);
    }



    /** 상세페이지 HTML 저장 경로 (유형별, storage/app/private/rotation/detail_{type}.html) */
    private const DETAIL_HTML_PATH = 'rotation/detail_%s.html';

    /** 유형 값 정규화 ('wine' | 'solo') */
    private function detailType(?string $type): string
    {
        return $type === 'solo' ? 'solo' : 'wine';
    }

    /** 유형별 저장 경로 반환 */
    private function detailHtmlPath(?string $type): string
    {
        return sprintf(self::DETAIL_HTML_PATH, $this->detailType($type));
    }

    /**
     * 저장된 상세페이지 HTML 을 유형별로 반환한다.
     * 저장된 파일이 없으면 해당 유형의 기본 샘플(2열) 을 렌더링해서 돌려준다.
     *
     * @param string $type 상세페이지 유형 ('wine' 와인파티 | 'solo' 1:1 소개팅)
     */
    public function detailHtmlLoad(string $type = 'wine'): string
    {
        $path = $this->detailHtmlPath($type);

        if (Storage::exists($path)) {
            return (string) Storage::get($path);
        }

        return $this->detailSampleHtml(2, $this->detailType($type));
    }

    /**
     * 기본 샘플 HTML 을 렌더링한다.
     * 실제 행사 데이터와 무관하게 더미 참여자(남/여 각 5명) 로 구성한다.
     *
     * @param int    $columns 행사 배치 열 수 (1 또는 2)
     * @param string $type    상세페이지 유형 ('wine' 와인파티 | 'solo' 1:1 소개팅)
     */
    public function detailSampleHtml(int $columns = 2, string $type = 'wine'): string
    {
        return trim(View::make('admin.rotation._detailSample', [
            'columns' => $columns === 1 ? 1 : 2,
            'type'    => $type === 'solo' ? 'solo' : 'wine',
        ])->render());
    }

    /**
     * 상세페이지 HTML 을 유형별 storage 파일에 저장한다.
     *
     * @param string $html 저장할 HTML
     * @param string $type 상세페이지 유형 ('wine' 와인파티 | 'solo' 1:1 소개팅)
     * @return array{isSuccess:bool, message?:string}
     */
    public function detailHtmlSave(string $html, string $type = 'wine'): array
    {
        $returnMsg = $this->returnMsg;

        try {
            Storage::put($this->detailHtmlPath($type), $html);
            $returnMsg = helpersSuccessMessage('상세페이지를 저장했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /* ======================================================================
     | 만남 이력 조회 — "만남"은 같은 행사에 함께 참여한 것을 의미한다.
     | 사람은 전화번호(phone)로 식별한다. (정확히 일치)
     * ==================================================================== */

    /**
     * 특정 전화번호의 사람이 "함께 참여한(= 같은 행사에 있었던)" 모든 상대 목록.
     *
     * - 입력 전화번호로 참여한 모든 회원 레코드(여러 행사 가능)를 찾고,
     *   그 각 행사에 함께 참여한 "이성" 회원들을 상대로 모은다. (동성은 제외)
     * - 상대는 (전화번호 + 행사) 단위로 1건씩 나열한다. (같은 상대와 여러 행사에서 만났으면 여러 건)
     * - 상대 프로필은 그 전화번호의 "가장 최근 입력 정보"로 통일한다.
     * - 각 건에는 행사 정보, 상대 프로필, 그 행사에서의 상호 매칭 여부를 담는다.
     *
     * @return array{
     *     found: bool,
     *     me: array<string, mixed>|null,
     *     events_count: int,
     *     partners: array<int, array<string, mixed>>,
     *     events: array<int, array<string, mixed>>
     * }
     */
    public function meetingPartnersByPhone(string $phone): array
    {
        $phone = trim($phone);

        // 이 전화번호로 참여한 내 회원 레코드들(행사별). id 역순 → first() 가 가장 최근 입력 정보.
        $myMembers = Member::query()
            ->where('phone', $phone)
            ->with('eventRound')
            ->orderByDesc('id')
            ->get();

        if ($myMembers->isEmpty()) {
            return ['found' => false, 'me' => null, 'events_count' => 0, 'events_count_by_type' => [], 'partners' => [], 'events' => []];
        }

        // 검색한 사람의 성별(최근 레코드 기준). 상대는 "이성"만 보여준다.
        $myGender = $myMembers->first()->gender;
        $oppositeGender = $myGender === Member::GENDER_MALE
            ? Member::GENDER_FEMALE
            : ($myGender === Member::GENDER_FEMALE ? Member::GENDER_MALE : null);

        $eventIds = $myMembers->pluck('event_id')->unique()->values()->all();

        // 내가 참여한 행사들에 함께 있던 회원 전부. (나 자신 제외)
        // 성별 필터는 프런트에서 토글(기본 이성)하므로 여기서는 전부 가져오고 is_opposite 로 표시한다.
        $myMemberIds = $myMembers->pluck('id')->all();
        $others = Member::query()
            ->whereIn('event_id', $eventIds)
            ->whereNotIn('id', $myMemberIds)
            ->with('eventRound')
            ->orderByDesc('id') // id 역순 → 전화번호별 첫 등장이 "가장 최근 입력 정보".
            ->get();

        // 전화번호별 최신 프로필(가장 최근 입력 정보) 맵.
        $latestByPhone = [];
        foreach ($others as $o) {
            if (! isset($latestByPhone[$o->phone])) {
                $latestByPhone[$o->phone] = $o; // 이미 id 역순이라 첫 등장이 최신.
            }
        }

        // 상호 매칭 판정용: 내가 참여한 행사들의 모든 투표를 (event, voter, target) 맵으로.
        $votedMap = $this->buildVotedMap($eventIds);

        // 상대별 "나(검색자) ↔ 그 상대" 사이에 오간 메시지 수 집계. (양방향, 쿼리 1개)
        // 2-2 상세 페이지의 "A↔B 메시지 합계"와 동일한 기준으로 맞춘다.
        // key = 상대(other) 회원 id, value = 나와 그 상대가 주고받은 총 메시지 수.
        $otherIds = $others->pluck('id')->all();
        $betweenCountByOther = [];
        if (! empty($otherIds) && ! empty($myMemberIds)) {
            $msgRows = \App\Models\Message::query()
                ->where(function ($q) use ($myMemberIds, $otherIds) {
                    $q->where(function ($qq) use ($myMemberIds, $otherIds) {
                        $qq->whereIn('sender_id', $myMemberIds)->whereIn('receiver_id', $otherIds);
                    })->orWhere(function ($qq) use ($myMemberIds, $otherIds) {
                        $qq->whereIn('sender_id', $otherIds)->whereIn('receiver_id', $myMemberIds);
                    });
                })
                ->get(['sender_id', 'receiver_id']);

            $myIdSet = array_flip($myMemberIds);
            foreach ($msgRows as $row) {
                // 나/상대 중 "상대 쪽" id 를 키로 집계한다.
                $otherSide = isset($myIdSet[$row->sender_id]) ? $row->receiver_id : $row->sender_id;
                $betweenCountByOther[$otherSide] = ($betweenCountByOther[$otherSide] ?? 0) + 1;
            }
        }

        // 상대의 "행사 유형별 누적 방문 횟수" 맵. (phone => ['wine'=>N, 'one'=>M])
        // 행사 상세 참여자 리스트와 동일한 표기(VisitStatService 재사용).
        $otherPhones = $others->pluck('phone')->unique()->values()->all();
        $visitCountsByPhone = app(VisitStatService::class)->visitCountsByType($otherPhones);

        // 행사별 "내 회원 레코드" 매핑 (행사 안에서 상대와 매칭 비교할 때 사용).
        $myByEvent = $myMembers->keyBy('event_id');

        // 행사별 "본인이 관리자에게 보낸 요청 수" 집계. (쿼리 1개) — 선택 행사별로 버튼 노출.
        $myRequestByEvent = [];
        if (! empty($myMemberIds)) {
            $myRequestByEvent = \App\Models\AdminRequest::query()
                ->whereIn('member_id', $myMemberIds)
                ->selectRaw('event_id, member_id, COUNT(*) as cnt')
                ->groupBy('event_id', 'member_id')
                ->get()
                ->mapWithKeys(fn ($row) => [(int) $row->event_id => ['member_id' => (int) $row->member_id, 'cnt' => (int) $row->cnt]])
                ->all();
        }

        // 행사별 selectbox 용 행사 목록(내가 참여한 행사만, 최근순).
        $eventsForFilter = [];

        $partners = [];
        foreach ($others as $other) {
            $eventId  = $other->event_id;
            $meInEvt  = $myByEvent->get($eventId);
            if ($meInEvt === null) {
                continue;
            }

            $iVotedThem = isset($votedMap[$eventId][$meInEvt->id][$other->id]);
            $theyVotedMe = isset($votedMap[$eventId][$other->id][$meInEvt->id]);

            // 프로필은 그 전화번호의 "가장 최근 입력 정보"로 통일.
            $profileSource = $latestByPhone[$other->phone] ?? $other;

            $partners[] = [
                'event'             => $this->eventPayload($other->eventRound),
                'event_id'          => $eventId,
                // 그 행사에서의 상대 회원 레코드 id (메시지 모달 필터용).
                'partner_member_id' => $other->id,
                // 그 행사에서의 내(검색자) 회원 레코드 id (대화 메시지 모달 필터용).
                'my_member_id'      => $meInEvt->id,
                'partner'           => $this->meetingPersonPayload($profileSource),
                // 상대가 이성인지(검색자 기준). 프런트에서 이성/동성 토글에 사용.
                'is_opposite'       => $oppositeGender !== null && $other->gender === $oppositeGender,
                // 나와 그 상대가 이 행사에서 주고받은 메시지 수 (양방향 합계, 0이면 버튼 숨김).
                'message_count'     => (int) ($betweenCountByOther[$other->id] ?? 0),
                // 상대의 행사 유형별 누적 방문 횟수. (예: ['wine'=>3,'one'=>2])
                'visit_counts'      => $visitCountsByPhone[$other->phone] ?? [],
                'i_voted'           => $iVotedThem,
                'they_voted'        => $theyVotedMe,
                'is_mutual'         => $iVotedThem && $theyVotedMe,
            ];

            if (! isset($eventsForFilter[$eventId])) {
                $eventsForFilter[$eventId] = $this->eventPayload($other->eventRound);
            }
        }

        // 정렬: ① 상호 매칭 먼저 → ② 한쪽이라도 투표(나 선택/상대 선택) → ③ 이름 가나다순.
        usort($partners, function ($a, $b) {
            $rank = function ($p) {
                if ($p['is_mutual']) {
                    return 0;
                }
                if ($p['i_voted'] || $p['they_voted']) {
                    return 1;
                }
                return 2;
            };
            $ra = $rank($a);
            $rb = $rank($b);
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            return strcmp((string) ($a['partner']['name'] ?? ''), (string) ($b['partner']['name'] ?? ''));
        });

        // 행사 필터 목록도 일시 역순. 각 행사에 "내가 이 행사에서 보낸 요청" 정보를 붙인다.
        $events = array_values($eventsForFilter);
        foreach ($events as &$evt) {
            $eid = $evt['id'];
            $myReq = $myRequestByEvent[$eid] ?? null;
            // 그 행사에서의 내 회원 레코드 id (요청 모달 필터용).
            $evt['my_member_id']    = optional($myByEvent->get($eid))->id;
            $evt['my_request_count'] = $myReq['cnt'] ?? 0;
        }
        unset($evt);
        usort($events, function ($a, $b) {
            return ($b['event_time'] ?? '') <=> ($a['event_time'] ?? '');
        });

        return [
            'found'        => true,
            'me'           => $this->meetingPersonPayload($myMembers->first()),
            'my_gender'    => $myGender,
            'events_count' => count($eventIds),
            // 본인(검색한 전화번호)의 행사 유형별 참여 횟수. (예: ['wine'=>3,'one'=>2])
            'events_count_by_type' => app(VisitStatService::class)
                ->visitCountsByType([$myMembers->first()->phone])[$myMembers->first()->phone] ?? [],
            'partners'     => $partners,
            'events'       => $events,
        ];
    }

    /**
     * 두 전화번호(A, B)가 "함께 참여한" 모든 행사의 만남 이력 상세.
     *
     * - 두 번호가 같은 행사에 모두 참여한 행사만 모은다.
     * - 각 행사마다: 행사 정보, 양쪽 프로필, 서로의 투표/매칭 관계, 주고받은 메시지 건수.
     *
     * @return array{
     *     found: bool,
     *     a: array<string, mixed>|null,
     *     b: array<string, mixed>|null,
     *     meetings: array<int, array<string, mixed>>
     * }
     */
    public function meetingHistoryBetween(string $phoneA, string $phoneB): array
    {
        $phoneA = trim($phoneA);
        $phoneB = trim($phoneB);

        // 각 전화번호의 참여 레코드(행사별). id 역순으로 정렬해 first() 가 "가장 최근 입력 정보"가 되게 한다.
        $aAll = Member::where('phone', $phoneA)->with('eventRound')->orderByDesc('id')->get();
        $bAll = Member::where('phone', $phoneB)->with('eventRound')->orderByDesc('id')->get();

        // 최신 프로필(별도 쿼리 없이 이미 로드한 컬렉션에서 추출).
        $aLatest = $aAll->first();
        $bLatest = $bAll->first();

        $aMembers = $aAll->keyBy('event_id');
        $bMembers = $bAll->keyBy('event_id');

        if ($aMembers->isEmpty() || $bMembers->isEmpty()) {
            return [
                'found' => false,
                'a'     => $aLatest ? $this->meetingPersonPayload($aLatest) : null,
                'b'     => $bLatest ? $this->meetingPersonPayload($bLatest) : null,
                'meetings' => [],
            ];
        }

        // 두 사람이 모두 참여한 행사 id.
        $sharedEventIds = $aMembers->keys()->intersect($bMembers->keys())->values()->all();

        if (empty($sharedEventIds)) {
            return [
                'found'    => true,
                'a'        => $this->meetingPersonPayload($aLatest),
                'b'        => $this->meetingPersonPayload($bLatest),
                'meetings' => [],
            ];
        }

        // 공유 행사들의 투표/메시지를 한 번에 로드.
        $votedMap = $this->buildVotedMap($sharedEventIds);
        $messages = \App\Models\Message::whereIn('event_id', $sharedEventIds)->get();

        // 프로필 payload(최신 정보) — 모든 행사 카드에서 공통으로 쓴다.
        $aProfile = $this->meetingPersonPayload($aLatest);
        $bProfile = $this->meetingPersonPayload($bLatest);

        $meetings = [];
        foreach ($sharedEventIds as $eventId) {
            $a = $aMembers->get($eventId);
            $b = $bMembers->get($eventId);

            $aVotedB = isset($votedMap[$eventId][$a->id][$b->id]);
            $bVotedA = isset($votedMap[$eventId][$b->id][$a->id]);

            // 두 사람 사이에 오간 메시지만 추린다.
            $between = $messages->filter(function ($m) use ($eventId, $a, $b) {
                return $m->event_id === $eventId
                    && (($m->sender_id === $a->id && $m->receiver_id === $b->id)
                        || ($m->sender_id === $b->id && $m->receiver_id === $a->id));
            });

            $aToB = $between->where('sender_id', $a->id)->count();
            $bToA = $between->where('sender_id', $b->id)->count();

            $messageList = $between->sortBy('id')->map(function ($m) use ($a) {
                return [
                    'from'       => $m->sender_id === $a->id ? 'a' : 'b',
                    'body'       => $m->body,
                    'read'       => $m->read_at !== null,
                    'created_at' => $m->created_at?->toIso8601String(),
                ];
            })->values()->all();

            $meetings[] = [
                'event'        => $this->eventPayload($a->eventRound),
                'event_id'     => $eventId,
                'a'            => $aProfile,
                'b'            => $bProfile,
                // 그 행사에서의 각자 member 레코드 id (매칭 조회 모달 필터용).
                'a_member_id'  => $a->id,
                'b_member_id'  => $b->id,
                'a_voted_b'    => $aVotedB,
                'b_voted_a'    => $bVotedA,
                'is_mutual'    => $aVotedB && $bVotedA,
                'messages_a_to_b' => $aToB,
                'messages_b_to_a' => $bToA,
                'messages'     => $messageList,
            ];
        }

        // 행사 일시 역순.
        usort($meetings, function ($x, $y) {
            return ($y['event']['event_time'] ?? '') <=> ($x['event']['event_time'] ?? '');
        });

        return [
            'found'    => true,
            'a'        => $aProfile,
            'b'        => $bProfile,
            'meetings' => $meetings,
        ];
    }

    /**
     * 주어진 행사들의 투표를 (event_id => [voter_id => [target_id => true]]) 맵으로 만든다.
     * 상호 매칭/투표 방향 판정에 공통으로 쓴다.
     *
     * @param array<int, int> $eventIds
     * @return array<int, array<int, array<int, true>>>
     */
    private function buildVotedMap(array $eventIds): array
    {
        $map = [];
        if (empty($eventIds)) {
            return $map;
        }

        foreach (Vote::query()->whereIn('event_id', $eventIds)->get(['event_id', 'voter_id', 'target_id']) as $v) {
            $map[$v->event_id][$v->voter_id][$v->target_id] = true;
        }

        return $map;
    }

    /**
     * 만남 이력 응답용 회원 프로필 payload.
     *
     * @return array<string, mixed>
     */
    private function meetingPersonPayload(?Member $member): array
    {
        return [
            'id'         => $member?->id,
            'name'       => $member?->name,
            'phone'      => $member?->phone,
            'gender'     => $member?->gender,
            'birth_year' => $member?->birth_year,
            'age'        => $member?->birth_year ? ((int) now()->year - (int) $member->birth_year + 1) : null,
            'job'        => $member?->job,
            'height'     => $member?->height,
            'mbti'       => $member?->mbti,
            'region'     => $member?->region,
            'is_smoker'  => $member?->is_smoker,
        ];
    }

    /**
     * 만남 이력 응답용 행사 payload.
     *
     * @return array<string, mixed>
     */
    private function eventPayload(?EventRound $event): array
    {
        return [
            'id'         => $event?->id,
            'title'      => $event?->title,
            'type'       => $event?->type,
            'type_label' => $event ? (EventRound::typeOptions()[$event->type] ?? $event->type) : null,
            'status'     => $event?->status,
            'status_label' => $event ? (EventRound::statusOptions()[$event->status] ?? $event->status) : null,
            'event_time' => $event?->event_time?->toIso8601String(),
        ];
    }
}
