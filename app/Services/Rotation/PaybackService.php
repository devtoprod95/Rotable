<?php

namespace App\Services\Rotation;

use App\Models\EventPayback;
use App\Models\EventRoster;
use App\Models\EventRound;
use App\Models\Member;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * 행사 페이백(100% / 80%) 대상 관리.
 *
 * - 대상자는 그 행사의 체크인 명단(event_rosters)에서 고른다. 1명당 1건.
 * - 결제 금액 = 그 사람의 플랫폼·성별 정가, 얼리버드 적용자는 그 플랫폼의 할인율(%)만큼 할인. 사람마다 직접 고칠 수 있다.
 * - 페이백 금액 = 결제 금액 × 비율. 서버에서만 계산한다.
 * - 노쇼는 체크인 데이터로 판단한다. 행사가 시작(S/V/X)된 뒤에도 체크인하지 않았으면 노쇼이고,
 *   노쇼는 입금 대상 합계에서 빠지며 입금 완료 처리도 막는다.
 * - 얼리버드 여부는 체크인 명단의 결제 유형(pay_type)과 같이 움직인다. 무료(F) 참가자는 페이백 대상이 될 수 없다.
 */
class PaybackService
{
    /**
     * 행사의 페이백 데이터: 대상 목록 · 합계 · 추가 가능한 명단 · 참가비 설정.
     *
     * @return array{items: list<array<string, mixed>>, summary: array<string, int>, candidates: list<array<string, mixed>>, fees: array{prices: array<string, array<string, int>>, early_bird_rates: array<string, int>}}
     */
    public function eventPaybacks(EventRound $event): array
    {
        $started = $this->isStarted($event);

        $paybacks = EventPayback::query()
            ->with(['roster', 'transferrer:id,name'])
            ->where('event_id', $event->id)
            ->get();

        $items = $paybacks
            ->map(fn (EventPayback $p): array => $this->transform($p, $started))
            ->sortBy(fn (array $row): array => [
                // 플랫폼별로 묶고, 입금 대기 → 노쇼 → 입금 완료, 그 안에서 성별 · 번호표 · 닉네임 순
                (string) $row['platform'],
                $row['is_done'] ? 2 : ($row['is_no_show'] ? 1 : 0),
                $row['gender'] === Member::GENDER_FEMALE ? 1 : 0,
                $row['ticket_no'] ?? PHP_INT_MAX,
                (string) $row['name'],
            ])
            ->values()
            ->all();

        $summary = [
            'total_count'   => count($items),
            'waiting_count' => 0,
            'waiting_sum'   => 0,
            'done_count'    => 0,
            'done_sum'      => 0,
            'no_show_count' => 0,
        ];
        foreach ($items as $row) {
            if ($row['is_done']) {
                $summary['done_count']++;
                $summary['done_sum'] += $row['payback_amount'];
            } elseif ($row['is_no_show']) {
                $summary['no_show_count']++;
            } else {
                $summary['waiting_count']++;
                $summary['waiting_sum'] += $row['payback_amount'];
            }
        }

        $candidates = EventRoster::query()
            ->where('event_id', $event->id)
            ->where('pay_type', '!=', EventRoster::PAY_FREE) // 무료 참가자는 돌려줄 돈이 없다
            ->whereDoesntHave('payback')
            ->get()
            ->sortBy(fn (EventRoster $r): array => [
                $r->gender === Member::GENDER_FEMALE ? 1 : 0,
                $r->ticket_no ?? PHP_INT_MAX,
                (string) $r->name,
            ])
            ->map(fn (EventRoster $r): array => [
                'id'         => $r->id,
                'name'       => $r->name,
                'gender'     => $r->gender,
                'platform'   => $r->platform,
                'ticket_no'  => $r->ticket_no,
                'checked_in' => $r->checked_in_at !== null,
                // 명단의 결제 유형이 얼리버드면 추가 창의 얼리버드 체크를 미리 켠다.
                'early_bird' => $r->isEarlyBird(),
            ])
            ->values()
            ->all();

        return [
            'items'      => $items,
            'summary'    => $summary,
            'candidates' => $candidates,
            'fees'       => [
                'prices'           => $event->fee_prices ?? [],
                'early_bird_rates' => $event->early_bird_rates ?? [],
            ],
        ];
    }

    /**
     * 페이백 대상 추가. 결제 금액을 비워 두면 그 사람의 플랫폼·성별 참가비(얼리버드 여부에 따라)로 채운다.
     *
     * @param array{event_roster_id:int, payback_rate:int, is_early_bird:bool, paid_amount:?int, bank_name:?string, account_number:?string, account_holder:?string, memo:?string} $data
     */
    public function add(int $eventId, array $data): array
    {
        try {
            $event = EventRound::findOrFail($eventId);

            // 확인 → 저장을 한 트랜잭션에서, 명단 행을 잠그고 처리한다. (동시에 두 번 눌러도 한 건만)
            return DB::transaction(function () use ($event, $eventId, $data): array {
                $roster = EventRoster::where('event_id', $eventId)->lockForUpdate()->find($data['event_roster_id']);
                if ($roster === null) {
                    return helpersFailMessage('이 행사의 체크인 명단에 없는 사람입니다.');
                }
                if (EventPayback::where('event_roster_id', $roster->id)->exists()) {
                    return helpersFailMessage('이미 페이백 대상으로 등록된 사람입니다.');
                }
                if ($roster->isFree()) {
                    return helpersFailMessage('무료 참가자는 페이백 대상이 될 수 없습니다. 체크인이나 매출 화면에서 결제 유형을 먼저 바꿔 주세요.');
                }

                $paid = $this->resolvePaidAmount($event, $roster, (bool) $data['is_early_bird'], $data['paid_amount'] ?? null);
                if ($paid === null) {
                    return helpersFailMessage('결제 금액을 입력하거나, 행사 정보에서 이 플랫폼·성별의 참가비를 먼저 저장해 주세요.');
                }

                EventPayback::create([
                    'event_id'        => $eventId,
                    'event_roster_id' => $roster->id,
                    'payback_rate'    => (int) $data['payback_rate'],
                    'is_early_bird'   => (bool) $data['is_early_bird'],
                    'paid_amount'     => $paid,
                    'payback_amount'  => EventPayback::calcAmount($paid, (int) $data['payback_rate']),
                    'bank_name'       => $this->clean($data['bank_name'] ?? null),
                    'account_number'  => $this->clean($data['account_number'] ?? null),
                    'account_holder'  => $this->clean($data['account_holder'] ?? null),
                    'memo'            => $this->clean($data['memo'] ?? null),
                    'status'          => EventPayback::STATUS_WAITING,
                ]);
                $this->syncRosterEarlyBird($roster, (bool) $data['is_early_bird']);

                return helpersSuccessMessage('페이백 대상을 추가했습니다.');
            });
        } catch (UniqueConstraintViolationException) {
            return helpersFailMessage('이미 페이백 대상으로 등록된 사람입니다.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 페이백 정보 수정. 입금 완료된 건은 금액·비율을 바꿀 수 없다. (계좌·메모는 수정 가능)
     *
     * @param array{payback_rate:int, is_early_bird:bool, paid_amount:?int, bank_name:?string, account_number:?string, account_holder:?string, memo:?string} $data
     */
    public function update(int $eventId, int $paybackId, array $data): array
    {
        try {
            $event = EventRound::findOrFail($eventId);

            // 입금 완료 여부 확인 → 저장을 한 트랜잭션에서 행을 잠그고 처리한다. (다른 관리자가 동시에 입금 완료한 경우 대비)
            return DB::transaction(function () use ($event, $eventId, $paybackId, $data): array {
                $payback = EventPayback::where('event_id', $eventId)->lockForUpdate()->findOrFail($paybackId);
                $payback->load('roster');
                if ($payback->roster?->isFree()) {
                    return helpersFailMessage('무료 참가자는 페이백 대상이 될 수 없습니다. 결제 유형을 먼저 바꿔 주세요.');
                }

                $rate      = (int) $data['payback_rate'];
                $earlyBird = (bool) $data['is_early_bird'];
                $paid      = $this->resolvePaidAmount($event, $payback->roster, $earlyBird, $data['paid_amount'] ?? null);
                if ($paid === null) {
                    return helpersFailMessage('결제 금액을 입력해 주세요.');
                }

                $amountChanged = $rate !== $payback->payback_rate
                    || $earlyBird !== $payback->is_early_bird
                    || $paid !== $payback->paid_amount;
                if ($payback->isTransferred() && $amountChanged) {
                    return helpersFailMessage('입금 완료된 건은 금액을 바꿀 수 없습니다. 입금 완료를 먼저 취소해 주세요.');
                }

                $payback->payback_rate   = $rate;
                $payback->is_early_bird  = $earlyBird;
                $payback->paid_amount    = $paid;
                $payback->payback_amount = EventPayback::calcAmount($paid, $rate);
                $payback->bank_name      = $this->clean($data['bank_name'] ?? null);
                $payback->account_number = $this->clean($data['account_number'] ?? null);
                $payback->account_holder = $this->clean($data['account_holder'] ?? null);
                $payback->memo           = $this->clean($data['memo'] ?? null);
                $payback->save();
                if ($payback->roster !== null) {
                    $this->syncRosterEarlyBird($payback->roster, $earlyBird);
                }

                return helpersSuccessMessage('페이백 정보를 수정했습니다.');
            });
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 입금 완료 / 완료 취소. 노쇼는 입금 완료로 바꿀 수 없다.
     */
    public function setTransferred(int $eventId, int $paybackId, bool $done, ?int $adminId): array
    {
        try {
            $event = EventRound::findOrFail($eventId);

            return DB::transaction(function () use ($event, $eventId, $paybackId, $done, $adminId): array {
                $payback = EventPayback::where('event_id', $eventId)->lockForUpdate()->findOrFail($paybackId);
                $payback->load('roster');

                // 이미 그 상태면 아무것도 바꾸지 않는다. (처리 시각·처리자 기록을 덮어쓰지 않게)
                if ($payback->isTransferred() === $done) {
                    return helpersSuccessMessage($done ? '이미 입금 완료된 건입니다.' : '이미 입금 대기 상태입니다.');
                }

                if ($done) {
                    if ($this->isNoShow($payback, $this->isStarted($event))) {
                        return helpersFailMessage('노쇼(체크인 안 함)인 사람은 입금 완료로 바꿀 수 없습니다. 실제 참석했다면 체크인 화면에서 먼저 체크인해 주세요.');
                    }
                    $payback->status         = EventPayback::STATUS_DONE;
                    $payback->transferred_at = now();
                    $payback->transferred_by = $adminId;
                } else {
                    $payback->status         = EventPayback::STATUS_WAITING;
                    $payback->transferred_at = null;
                    $payback->transferred_by = null;
                }
                $payback->save();

                return helpersSuccessMessage($done ? '입금 완료로 표시했습니다.' : '입금 완료를 취소했습니다.');
            });
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 페이백 대상에서 뺀다. 입금 완료된 건은 지울 수 없다.
     */
    public function delete(int $eventId, int $paybackId): array
    {
        try {
            return DB::transaction(function () use ($eventId, $paybackId): array {
                $payback = EventPayback::where('event_id', $eventId)->lockForUpdate()->findOrFail($paybackId);
                if ($payback->isTransferred()) {
                    return helpersFailMessage('입금 완료된 건은 삭제할 수 없습니다. 입금 완료를 먼저 취소해 주세요.');
                }
                $payback->delete();

                return helpersSuccessMessage('페이백 대상에서 뺐습니다.');
            });
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 한 사람의 결제 유형 + 페이백을 한 번에 맞춘다. (체크인 · 매출 화면이 함께 쓰는 공통 진입점)
     *
     * 전달된 키만 반영한다.
     *  - pay_type          : N=정가 / E=얼리버드 / F=무료
     *  - payback_rate      : null=페이백 없음(있으면 뺀다) / 80 / 100
     *  - bank_name · account_number · account_holder : 페이백 계좌 (페이백이 있을 때만)
     *
     * 규칙 (모두 여기서만 판정한다)
     *  - 무료 참가자는 페이백을 가질 수 없다. (무료 + 페이백을 같이 보내면 거부)
     *  - 입금 완료된 페이백이 있으면 결제 유형·비율을 바꾸거나 페이백을 뺄 수 없다. (계좌만 수정 가능)
     *  - 결제 금액은 처음 만들 때 플랫폼·성별 참가비로 채우고, 이후 결제 유형이 바뀌면
     *    금액이 "이전 자동 금액" 그대로일 때만 새 자동 금액으로 다시 계산한다. (직접 고친 금액은 유지)
     *
     * 호출하는 쪽에서 트랜잭션을 열어 두었으면 그 안에서 함께 처리된다.
     *
     * @param array{pay_type?:string, payback_rate?:?int, bank_name?:?string, account_number?:?string, account_holder?:?string} $input
     */
    public function applyRosterPayment(int $eventId, int $rosterId, array $input): array
    {
        try {
            $event = EventRound::findOrFail($eventId);

            return DB::transaction(function () use ($event, $eventId, $rosterId, $input): array {
                $roster = EventRoster::where('event_id', $eventId)->lockForUpdate()->find($rosterId);
                if ($roster === null) {
                    return helpersFailMessage('이 행사의 명단에서 찾을 수 없습니다.');
                }
                $payback = EventPayback::where('event_roster_id', $roster->id)->lockForUpdate()->first();

                // 바꿀 값 정리 (안 보낸 키는 현재 값 유지)
                $payType = array_key_exists('pay_type', $input) && $input['pay_type'] !== null && $input['pay_type'] !== ''
                    ? (string) $input['pay_type']
                    : ($roster->pay_type ?: EventRoster::PAY_NORMAL);
                if (! array_key_exists($payType, EventRoster::payTypeOptions())) {
                    return helpersFailMessage('결제 유형이 올바르지 않습니다.');
                }
                $rateGiven = array_key_exists('payback_rate', $input);
                $rate      = $rateGiven
                    ? ($input['payback_rate'] !== null && $input['payback_rate'] !== '' ? (int) $input['payback_rate'] : null)
                    : $payback?->payback_rate;
                if ($rate !== null && ! array_key_exists($rate, EventPayback::rateOptions())) {
                    return helpersFailMessage('페이백 비율은 80% 또는 100%만 선택할 수 있습니다.');
                }

                // 규칙 1: 무료 + 페이백 불가
                if ($payType === EventRoster::PAY_FREE && $rate !== null) {
                    return helpersFailMessage($payback !== null && ! $rateGiven
                        ? '페이백 대상은 무료로 바꿀 수 없습니다. 페이백을 "없음"으로 먼저 바꿔 주세요.'
                        : '무료 참가자는 페이백 대상이 될 수 없습니다.');
                }

                // 규칙 2: 입금 완료면 결제 유형·비율 변경, 페이백 빼기 불가
                if ($payback !== null && $payback->isTransferred()) {
                    if ($rate === null) {
                        return helpersFailMessage('페이백 입금이 끝난 사람은 페이백을 뺄 수 없습니다. 페이백 화면에서 입금 완료를 먼저 취소해 주세요.');
                    }
                    if ($payType !== $roster->pay_type || $rate !== (int) $payback->payback_rate) {
                        return helpersFailMessage('페이백 입금이 끝난 사람은 결제 유형·페이백 비율을 바꿀 수 없습니다.');
                    }
                }

                $earlyBird = $payType === EventRoster::PAY_EARLY_BIRD;

                // 규칙 3: 새 페이백은 참가비로 결제 금액을 채우므로, 참가비가 없으면 거부 (아무것도 저장하기 전에 판정)
                $newPaid = null;
                if ($rate !== null && $payback === null) {
                    $newPaid = $event->paidPriceFor($roster->platform, $roster->gender, $earlyBird);
                    if ($newPaid === null) {
                        return helpersFailMessage('이 플랫폼·성별의 참가비가 없어 페이백 금액을 계산할 수 없습니다. 행사 정보에서 참가비를 먼저 입력하거나, 페이백 화면에서 결제 금액을 직접 넣어 주세요.');
                    }
                }

                // ---- 여기부터 저장 (위에서 모든 판정을 끝냈다) ----
                $oldEarly = $roster->isEarlyBird();
                if ($roster->pay_type !== $payType) {
                    $roster->pay_type = $payType;
                    $roster->save();
                }

                // 페이백 빼기
                if ($rate === null) {
                    $payback?->delete();

                    return helpersSuccessMessage('저장했습니다.');
                }

                $account = [];
                foreach (['bank_name', 'account_number', 'account_holder'] as $key) {
                    if (array_key_exists($key, $input)) {
                        $account[$key] = $this->clean($input[$key]);
                    }
                }

                // 페이백 새로 만들기: 결제 금액은 참가비로 자동
                if ($payback === null) {
                    $paid = (int) $newPaid;
                    EventPayback::create(array_merge([
                        'event_id'        => $eventId,
                        'event_roster_id' => $roster->id,
                        'payback_rate'    => $rate,
                        'is_early_bird'   => $earlyBird,
                        'paid_amount'     => $paid,
                        'payback_amount'  => EventPayback::calcAmount($paid, $rate),
                        'status'          => EventPayback::STATUS_WAITING,
                    ], $account));

                    return helpersSuccessMessage('저장했습니다.');
                }

                // 페이백 고치기: 얼리버드가 바뀌었고 금액이 이전 자동 금액 그대로면 새 자동 금액으로
                if ($oldEarly !== $earlyBird || (bool) $payback->is_early_bird !== $earlyBird) {
                    $before = $event->paidPriceFor($roster->platform, $roster->gender, (bool) $payback->is_early_bird);
                    $after  = $event->paidPriceFor($roster->platform, $roster->gender, $earlyBird);
                    if ($before !== null && $after !== null && (int) $payback->paid_amount === $before) {
                        $payback->paid_amount = $after;
                    }
                    $payback->is_early_bird = $earlyBird;
                }
                $payback->payback_rate   = $rate;
                $payback->payback_amount = EventPayback::calcAmount((int) $payback->paid_amount, $rate);
                foreach ($account as $key => $value) {
                    $payback->{$key} = $value;
                }
                $payback->save();

                return helpersSuccessMessage('저장했습니다.');
            });
        } catch (UniqueConstraintViolationException) {
            return helpersFailMessage('이미 페이백 대상으로 등록된 사람입니다. 새로고침 후 다시 시도해 주세요.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 명단의 플랫폼·성별이 바뀐 뒤 페이백 결제 금액을 맞춘다. (참가비가 플랫폼·성별마다 다르므로)
     *
     * - 입금 완료된 페이백이 있으면 플랫폼·성별을 바꿀 수 없다. (실패를 돌려주면 호출한 쪽이 롤백한다)
     * - 결제 금액이 이전 자동 금액 그대로였으면 새 자동 금액으로 다시 계산한다. (직접 고친 금액은 유지)
     */
    public function repriceForProfileChange(int $eventId, int $rosterId, ?string $oldPlatform, ?string $oldGender): array
    {
        $event   = EventRound::findOrFail($eventId);
        $roster  = EventRoster::where('event_id', $eventId)->findOrFail($rosterId);
        $payback = EventPayback::where('event_roster_id', $roster->id)->lockForUpdate()->first();
        if ($payback === null || ($oldPlatform === $roster->platform && $oldGender === $roster->gender)) {
            return helpersSuccessMessage();
        }
        if ($payback->isTransferred()) {
            return helpersFailMessage('페이백 입금이 끝난 사람은 플랫폼·성별을 바꿀 수 없습니다.');
        }

        $before = $event->paidPriceFor($oldPlatform, $oldGender, (bool) $payback->is_early_bird);
        $after  = $event->paidPriceFor($roster->platform, $roster->gender, (bool) $payback->is_early_bird);
        if ($before !== null && $after !== null && (int) $payback->paid_amount === $before) {
            $payback->paid_amount    = $after;
            $payback->payback_amount = EventPayback::calcAmount($after, (int) $payback->payback_rate);
            $payback->save();
        }

        return helpersSuccessMessage();
    }

    /**
     * 체크인 명단의 결제 유형을 페이백의 얼리버드 여부에 맞춘다. (무료는 건드리지 않는다)
     */
    private function syncRosterEarlyBird(EventRoster $roster, bool $earlyBird): void
    {
        if ($roster->isFree()) {
            return;
        }
        $payType = $earlyBird ? EventRoster::PAY_EARLY_BIRD : EventRoster::PAY_NORMAL;
        if ($roster->pay_type !== $payType) {
            $roster->pay_type = $payType;
            $roster->save();
        }
    }

    /**
     * 화면용 1행.
     *
     * @return array<string, mixed>
     */
    private function transform(EventPayback $p, bool $started): array
    {
        $roster = $p->roster;

        return [
            'id'             => $p->id,
            'roster_id'      => $p->event_roster_id,
            'name'           => $roster?->name,
            'gender'         => $roster?->gender,
            'platform'       => $roster?->platform,
            'ticket_no'      => $roster?->ticket_no,
            'checked_in'     => $roster?->checked_in_at !== null,
            'is_no_show'     => $this->isNoShow($p, $started),
            'payback_rate'   => $p->payback_rate,
            'is_early_bird'  => $p->is_early_bird,
            'paid_amount'    => $p->paid_amount,
            'payback_amount' => $p->payback_amount,
            'bank_name'      => $p->bank_name,
            'account_number' => $p->account_number,
            'account_holder' => $p->account_holder,
            'memo'           => $p->memo,
            'is_done'        => $p->isTransferred(),
            'transferred_at' => $p->transferred_at?->format('Y-m-d H:i'),
            'transferrer'    => $p->transferrer?->name,
        ];
    }

    /**
     * 행사가 시작(진행·매칭중·종료)됐는지. 시작 전에는 체크인 안 한 사람도 노쇼로 보지 않는다.
     */
    private function isStarted(EventRound $event): bool
    {
        return in_array($event->status, [EventRound::STATUS_STARTED, EventRound::STATUS_VOTING, EventRound::STATUS_CLOSED], true);
    }

    /**
     * 노쇼 여부: 행사가 시작됐는데 체크인하지 않았으면 노쇼. (입금 완료된 건은 노쇼로 보지 않는다)
     */
    private function isNoShow(EventPayback $p, bool $started): bool
    {
        return $started && ! $p->isTransferred() && $p->roster?->checked_in_at === null;
    }

    /**
     * 결제 금액: 입력값이 있으면 그 값, 없으면 그 사람의 플랫폼·성별 참가비(얼리버드 여부에 따라). 둘 다 없으면 null.
     */
    private function resolvePaidAmount(EventRound $event, ?EventRoster $roster, bool $earlyBird, ?int $input): ?int
    {
        if ($input !== null) {
            return $input;
        }

        return $event->paidPriceFor($roster?->platform, $roster?->gender, $earlyBird);
    }

    /**
     * 앞뒤 공백 제거, 빈 값은 null.
     */
    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
