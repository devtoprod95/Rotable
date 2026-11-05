<?php

namespace App\Services\Rotation;

use App\Events\EventClosed;
use App\Jobs\SendCloseVotingReportJob;
use App\Models\EventRound;
use App\Models\Member;
use App\Models\RotationSeat;
use App\Models\RotationTable;
use App\Models\Vote;
use App\Services\TelegramNotifier;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * 좌석 배정 / 로테이션 진행 도메인 서비스.
 *
 * 좌석 보드/배치(seating*), 턴 진행·되돌리기(rotate*), 투표 시작/마감(openVoting/closeVoting),
 * 마감 리포트 전송을 담당한다. (RotationService 에서 분리)
 */
class SeatingService
{
    /** @var array<string, mixed> 기본 응답 메시지 템플릿 */
    protected array $returnMsg = [];

    public function __construct(protected MatchService $matchService)
    {
        $this->returnMsg = helpersDefaultMessage();
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
            /** @var object $seat */
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

        $labeler  = app(MemberLabelService::class);
        $labelMap = $labeler->labelMap($eventId);

        $tableData = $tables->map(function (RotationTable $table) use ($seatingsByTableNo, $labeler, $labelMap) {
                $seatsHere = $seatingsByTableNo->get($table->table_no, collect());

                return [
                    'id'       => $table->id,
                    'table_no' => $table->table_no,
                    'members'  => $seatsHere->map(fn (RotationSeat $s) => [
                        'seating_id' => $s->id,
                        'member_id'  => $s->member_id,
                        'name'       => $s->member?->name,
                        'label'      => $labeler->label($labelMap, $s->member?->gender, $s->member?->name, $s->member?->platform),
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
        $labeler  = app(MemberLabelService::class);
        $labelMap = $labeler->labelMap($eventId);

        return Member::where('event_id', $eventId)
            ->orderBy('name')
            ->get()
            ->map(fn (Member $m) => [
                'id'         => $m->id,
                'name'       => $m->name,
                'label'      => $labeler->label($labelMap, $m->gender, $m->name, $m->platform),
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
                'status'    => EventRound::STATUS_STARTED,
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
                // 1→0 되돌림이면 대기(P), 그 외엔 진행(S) 유지.
                'status'    => $result === 0 ? EventRound::STATUS_PENDING : EventRound::STATUS_STARTED,
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
            $returnMsg['data'] = ['status' => EventRound::STATUS_VOTING];
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 매칭 시작 취소 — 매칭중(V)인 행사를 진행(S)으로 되돌린다. (openVoting 의 역방향)
     *
     * 실수로 "매칭 시작"을 눌렀을 때 로테이션 진행 단계로 복구한다.
     * 그동안 쌓인 투표(event_votes)는 **그대로 유지**하고, voting_opened_at(자동 마감 기준점)만 비운다.
     * (다시 매칭 시작을 누르면 이어서 선택/수정할 수 있다.)
     *
     * 와인파티(wine) 전용이다. 1:1(one)은 V 단계 없이 진행(S)에서 바로 선택을 받으므로
     * 이 되돌리기 대상이 아니다. (진행 중 매칭 취소가 필요하면 로테이션 되돌리기를 쓴다)
     *
     * @return array{isSuccess:bool, msg?:string, data?:array{status:string}}
     */
    public function cancelVoting(int $eventId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            DB::transaction(function () use ($eventId): void {
                /** @var EventRound $event */
                $event = EventRound::lockForUpdate()->find($eventId);
                if ($event === null) {
                    throw new Exception('행사를 찾을 수 없습니다.');
                }

                if ($event->status !== EventRound::STATUS_VOTING) {
                    throw new Exception('매칭중인 행사만 진행 상태로 되돌릴 수 있습니다.');
                }

                // 투표(매칭 데이터)는 지우지 않고 유지한다. 상태만 진행(S)으로 되돌린다.
                $event->status           = EventRound::STATUS_STARTED;
                $event->voting_opened_at = null;
                $event->save();
            });

            // 참가자 화면 복구: '매칭하기' 탭을 닫고 홈(로테이션 진행 화면)으로 되돌린다.
            broadcast(new \App\Events\RotationUpdated($eventId));

            $returnMsg = helpersSuccessMessage('매칭 시작을 취소하고 진행 상태로 되돌렸습니다. (기존 투표는 유지)');
            $returnMsg['data'] = ['status' => EventRound::STATUS_STARTED];
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
            $returnMsg['data'] = ['status' => EventRound::STATUS_CLOSED];
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 종료(X) 되돌리기 — 종료된 행사를 "종료 직전 상태"로 강제 복구한다.
     *
     * 실수로 매칭을 종료했을 때, DB의 status 를 직전 운영 상태로 되돌려 다시 진행/매칭을 이어가게 한다.
     * 행사 타입별로 종료 직전 상태가 다르므로 그에 맞춰 복구한다:
     *  - 와인파티(wine): 매칭중(V) 에서 종료되므로 X → V 로 되돌린다. (매칭 선택 재개)
     *  - 1:1(one):      진행(S) 에서 바로 종료되므로 X → S 로 되돌린다. (별도 V 단계 없음)
     *
     * current_round(턴)·좌석은 종료로 바뀌지 않으므로 건드리지 않는다.
     * voting_opened_at 도 유지한다. (자동 9시 마감 기준점. 되돌린 뒤 와인파티는 다시 그 기준이 적용됨)
     *
     * @return array{isSuccess:bool, msg?:string, data?:array{status:string}}
     */
    public function reopenFromClosed(int $eventId): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $result = DB::transaction(function () use ($eventId): string {
                /** @var EventRound $event */
                $event = EventRound::lockForUpdate()->find($eventId);
                if ($event === null) {
                    throw new Exception('행사를 찾을 수 없습니다.');
                }

                if ($event->status !== EventRound::STATUS_CLOSED) {
                    throw new Exception('이미 종료되지 않은 행사입니다. 되돌릴 수 없습니다.');
                }

                // 타입별로 "종료 직전" 상태로 복구한다.
                //  - 1:1(one): 진행(S) 에서 바로 종료되므로 S 로.
                //  - 와인파티(wine): 매칭중(V) 에서 종료되므로 V 로.
                $target = $event->isOneToOne()
                    ? EventRound::STATUS_STARTED
                    : EventRound::STATUS_VOTING;

                $event->status = $target;

                // 되돌리는 시점에 매칭 선택 기준점이 비어 있으면 지금으로 채운다.
                // (정상 흐름이라면 진행/매칭 시작 때 이미 기록돼 있어 유지된다.)
                if ($event->voting_opened_at === null) {
                    $event->voting_opened_at = now();
                }

                $event->save();

                return $target;
            });

            // 참가자 화면 실시간 복구:
            //  - V(와인파티): '매칭하기' 탭을 다시 열어야 하므로 EventVotingOpened.
            //  - S(1:1):      홈/매칭 선택을 다시 노출해야 하므로 RotationUpdated.
            if ($result === EventRound::STATUS_VOTING) {
                broadcast(new \App\Events\EventVotingOpened($eventId));
            } else {
                broadcast(new \App\Events\RotationUpdated($eventId));
            }

            $label     = EventRound::statusOptions()[$result] ?? $result;
            $returnMsg = helpersSuccessMessage("종료를 되돌렸습니다. (현재 상태: {$label})");
            $returnMsg['data'] = ['status' => $result];
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
     * 노쇼 수(남/여/합계), 투표 참여자 수, 상호 매칭 쌍 수, 메시지/요청 건수.
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
        $pairCount = count($this->matchService->matchedPairList($event->id));

        // 노쇼: 체크인 명단(event_rosters)에 있었으나 당일 참석(체크인)하지 않은 신청자 수.
        // checked_in_at 이 null 이면 미참석(노쇼)으로 본다. 성별이 F 가 아니면 남(M)으로 귀속한다.
        $noShowBase = $event->rosters()->whereNull('checked_in_at');
        $noShowTotal  = (clone $noShowBase)->count();
        $noShowFemale = (clone $noShowBase)->where('gender', Member::GENDER_FEMALE)->count();
        $noShowMale   = $noShowTotal - $noShowFemale;

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
            '🚫 <b>노쇼</b> : 총 ' . number_format($noShowTotal) . '명 (남 ' . number_format($noShowMale) . ' / 여 ' . number_format($noShowFemale) . ')',
            '🗳 <b>투표 참여</b> : ' . number_format($voterCount) . '명 (남 ' . number_format($maleVoterCount) . ' / 여 ' . number_format($femaleVoterCount) . ')',
            '❤️ <b>상호 매칭</b> : ' . number_format($pairCount) . '쌍',
            '',
            '💬 메시지 ' . number_format($messageCount) . '건 · 📨 요청 ' . number_format($requestCount) . '건',
        ]);
    }

}
