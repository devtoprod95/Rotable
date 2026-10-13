<?php

namespace App\Services;

use App\Dtos\RotationTableListRequestDto;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationRound;
use App\Models\RotationSeating;
use App\Models\RotationTable;
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
            ->withCount([
                'rotationTables',
                'messages',
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

        return $query->paginate($size, ['*'], 'page', $page);
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
            'members'        => fn ($q) => $q->withCount('sentMessages')->orderBy('name'),
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
     * @param array{searchText?:string, read?:string} $filters
     * @return LengthAwarePaginator
     */
    public function eventMessageList(int $eventId, int $page, int $size, array $sort, array $filters = []): LengthAwarePaginator
    {
        $query = \App\Models\Message::query()
            ->with(['sender:id,name,gender', 'receiver:id,name,gender'])
            ->where('event_id', $eventId);

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
     | 테이블
     * ==================================================================== */

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
                'id'     => $m->id,
                'name'   => $m->name,
                'gender' => $m->gender,
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
            $round = RotationRound::firstOrCreate(
                ['event_id' => (int) $data->event_id, 'round_no' => (int) $data->round_no]
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
     * 로테이션 종료: 행사 상태를 '종료(X)'로 전환한다.
     *
     * @return array{isSuccess:bool, message?:string}
     */
    public function rotateFinish(int $eventId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $event = EventRound::find($eventId);
            if (!$event) {
                throw new Exception('행사를 찾을 수 없습니다.');
            }

            $event->status = EventRound::STATUS_CLOSED;
            $event->save();

            $returnMsg = helpersSuccessMessage('로테이션을 종료했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }
}
