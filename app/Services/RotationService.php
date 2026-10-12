<?php

namespace App\Services;

use App\Dtos\RotationTableBulkStoreRequestDto;
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

    /**
     * 행사에 테이블을 개수만큼 증분 생성한다.
     * 기존 최대 table_no 다음 번호부터 count개를 추가한다. (요구사항: a안 증분)
     */
    public function tableBulkStore(RotationTableBulkStoreRequestDto $dto): array
    {
        $returnMsg = $this->returnMsg;

        try {
            DB::transaction(function () use ($dto) {
                $maxNo = (int) RotationTable::where('event_id', $dto->eventId)->max('table_no');

                $rows = [];
                for ($i = 1; $i <= $dto->count; $i++) {
                    $rows[] = [
                        'event_id'   => $dto->eventId,
                        'table_no'   => $maxNo + $i,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                RotationTable::insert($rows);

                // 최초 턴(0턴)을 보장 생성하고, 행사 상태를 '대기(P)'로 둔다.
                RotationRound::firstOrCreate([
                    'event_id' => $dto->eventId,
                    'round_no' => 0,
                ]);

                EventRound::where('id', $dto->eventId)
                    ->update(['status' => EventRound::STATUS_PENDING]);
            });

            $returnMsg = helpersSuccessMessage("테이블 {$dto->count}개를 생성했습니다.");
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
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

            $result = DB::transaction(function () use ($eventId, $currentRound, $nextRoundNo, $nextTableId) {
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

                    // 여자: 같은 테이블 고정 / 남자: 다음 테이블로 +1 이동(마지막→첫번째).
                    $tableId = $gender === Member::GENDER_FEMALE
                        ? $seating->rotation_table_id
                        : ($nextTableId[$seating->rotation_table_id] ?? $seating->rotation_table_id);

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
