<?php

namespace App\Services\Rotation;

use App\Dtos\RotationTableListRequestDto;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationTable;
use App\Services\VisitStatService;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
                'members as munto_count'  => fn ($q) => $q->where('platform', Member::PLATFORM_MUNTO),
                'members as frip_count'   => fn ($q) => $q->where('platform', Member::PLATFORM_FRIP),
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


}
