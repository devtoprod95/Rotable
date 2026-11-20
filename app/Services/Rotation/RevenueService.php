<?php

namespace App\Services\Rotation;

use App\Models\EventCost;
use App\Models\EventPayback;
use App\Models\EventRoster;
use App\Models\EventRound;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

/**
 * 행사 매출 계산.
 *
 * 체크인 명단(event_rosters) 전원을 결제한 사람으로 본다. (노쇼도 결제는 했으므로 매출에 넣는다)
 *  - 결제 금액: 무료(F)=0, 페이백 대상은 페이백에 기록된 결제 금액, 그 외는 플랫폼·성별 정가(얼리버드면 할인)
 *  - 플랫폼 수수료 = 결제 금액 × 그 플랫폼 수수료율(%)
 *  - 페이백 = 돌려줄(또는 돌려준) 금액. 노쇼 페이백은 돌려주지 않으므로 빼지 않는다.
 *  - 실수입 = 결제 합계 - 수수료 - 페이백, 순이익 = 실수입 - 비용 합계
 */
class RevenueService
{
    /**
     * 행사 매출 데이터.
     *
     * @return array<string, mixed>
     */
    public function eventRevenue(EventRound $event): array
    {
        $started = in_array($event->status, [EventRound::STATUS_STARTED, EventRound::STATUS_VOTING, EventRound::STATUS_CLOSED], true);

        $rosters = EventRoster::query()
            ->with('payback')
            ->where('event_id', $event->id)
            ->get();

        $people     = [];
        $byPlatform = [];
        $totals     = [
            'people'        => 0,
            'paid_people'   => 0,
            'free_people'   => 0,
            'early_people'  => 0,
            'no_show'       => 0,
            'missing_price' => 0,
            'gross'         => 0,
            'platform_fee'  => 0,
            'payback'       => 0,
        ];

        foreach ($rosters as $r) {
            $platform = (string) $r->platform;
            $gender   = $r->gender === Member::GENDER_FEMALE ? Member::GENDER_FEMALE : Member::GENDER_MALE;
            $payback  = $r->payback;
            $noShow   = $started && $r->checked_in_at === null;

            // 결제 금액: 무료 → 0, 페이백 대상 → 기록된 결제 금액, 그 외 → 참가비 설정값
            $missing = false;
            if ($r->isFree()) {
                $gross = 0;
            } elseif ($payback !== null) {
                $gross = (int) $payback->paid_amount;
            } else {
                $price   = $event->paidPriceFor($platform, $gender, $r->isEarlyBird());
                $missing = $price === null;
                $gross   = (int) ($price ?? 0);
            }

            $fee = intdiv($gross * $event->platformFeeRateFor($platform), 100);

            // 노쇼 페이백은 돌려주지 않는다. (입금 완료된 건은 노쇼여도 이미 나간 돈)
            $paybackAmount = 0;
            if ($payback !== null && (! $noShow || $payback->isTransferred())) {
                $paybackAmount = (int) $payback->payback_amount;
            }

            $people[] = [
                'id'             => $r->id,
                'name'           => $r->name,
                'gender'         => $gender,
                'platform'       => $platform,
                'ticket_no'      => $r->ticket_no,
                'pay_type'       => $r->pay_type ?: EventRoster::PAY_NORMAL,
                'checked_in'     => $r->checked_in_at !== null,
                'is_no_show'     => $noShow,
                'missing_price'  => $missing,
                'gross'          => $gross,
                'platform_fee'   => $fee,
                'payback'        => $paybackAmount,
                'payback_rate'   => $payback?->payback_rate,
                'payback_done'   => $payback?->isTransferred() ?? false,
                'net'            => $gross - $fee - $paybackAmount,
            ];

            $bucket = &$byPlatform[$platform][$gender];
            $bucket ??= ['people' => 0, 'free' => 0, 'early' => 0, 'gross' => 0, 'platform_fee' => 0, 'payback' => 0];
            $bucket['people']++;
            $bucket['free']         += $r->isFree() ? 1 : 0;
            $bucket['early']        += $r->isEarlyBird() ? 1 : 0;
            $bucket['gross']        += $gross;
            $bucket['platform_fee'] += $fee;
            $bucket['payback']      += $paybackAmount;
            unset($bucket);

            $totals['people']++;
            $totals['free_people']   += $r->isFree() ? 1 : 0;
            $totals['paid_people']   += $r->isFree() ? 0 : 1;
            $totals['early_people']  += $r->isEarlyBird() ? 1 : 0;
            $totals['no_show']       += $noShow ? 1 : 0;
            $totals['missing_price'] += $missing ? 1 : 0;
            $totals['gross']         += $gross;
            $totals['platform_fee']  += $fee;
            $totals['payback']       += $paybackAmount;
        }

        $costs     = $event->costs()->get(['id', 'name', 'amount']);
        $costTotal = (int) $costs->sum('amount');

        $totals['income']     = $totals['gross'] - $totals['platform_fee'] - $totals['payback'];
        $totals['cost']       = $costTotal;
        $totals['net_profit'] = $totals['income'] - $costTotal;

        usort($people, fn (array $a, array $b): int => [
            $a['platform'], $a['gender'] === Member::GENDER_FEMALE ? 0 : 1, $a['ticket_no'] ?? PHP_INT_MAX, (string) $a['name'],
        ] <=> [
            $b['platform'], $b['gender'] === Member::GENDER_FEMALE ? 0 : 1, $b['ticket_no'] ?? PHP_INT_MAX, (string) $b['name'],
        ]);

        return [
            'totals'      => $totals,
            'by_platform' => $byPlatform,
            'people'      => $people,
            'costs'       => $costs->map(fn (EventCost $c): array => ['id' => $c->id, 'name' => $c->name, 'amount' => $c->amount])->values()->all(),
            'fees'        => [
                'prices'             => $event->fee_prices ?? [],
                'early_bird_rates'   => $event->early_bird_rates ?? [],
                'platform_fee_rates' => $event->platform_fee_rates ?? [],
            ],
        ];
    }

    /**
     * 플랫폼별 수수료율(%) 저장.
     *
     * @param array<string, int> $rates
     */
    public function savePlatformFeeRates(int $eventId, array $rates): array
    {
        try {
            $event                     = EventRound::findOrFail($eventId);
            $event->platform_fee_rates = $rates !== [] ? $rates : null;
            $event->save();

            return helpersSuccessMessage('플랫폼 수수료율을 저장했습니다.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 비용 항목을 입력 목록으로 통째로 맞춘다. (입력 순서 = sort_order)
     *
     * @param list<array{name:string, amount:int}> $items
     */
    public function syncCosts(int $eventId, array $items): array
    {
        try {
            EventRound::findOrFail($eventId);

            DB::transaction(function () use ($eventId, $items): void {
                EventCost::where('event_id', $eventId)->delete();
                $now  = now();
                $rows = [];
                foreach (array_values($items) as $order => $item) {
                    $rows[] = [
                        'event_id'   => $eventId,
                        'name'       => trim($item['name']),
                        'amount'     => (int) $item['amount'],
                        'sort_order' => $order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows !== []) {
                    EventCost::insert($rows);
                }
            });

            return helpersSuccessMessage('비용을 저장했습니다.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }

    /**
     * 신청자 결제 유형 변경 (정가 / 얼리버드 / 무료).
     *
     * - 페이백 대상은 무료로 바꿀 수 없다. (페이백에서 먼저 빼야 한다)
     * - 입금 완료된 페이백 대상은 결제 유형을 바꿀 수 없다. (이미 보낸 금액이 바뀌므로)
     * - 페이백 대상의 얼리버드 여부를 바꾸면 페이백도 맞춘다. 결제 금액이 자동 금액 그대로였으면 새 자동 금액으로 다시 계산한다.
     */
    public function setPayType(int $eventId, int $rosterId, string $payType): array
    {
        try {
            $roster = EventRoster::with('payback')->where('event_id', $eventId)->findOrFail($rosterId);
            $event  = EventRound::findOrFail($eventId);

            if ($roster->pay_type === $payType) {
                return helpersSuccessMessage('결제 유형을 저장했습니다.');
            }

            $payback = $roster->payback;
            if ($payback !== null) {
                if ($payType === EventRoster::PAY_FREE) {
                    return helpersFailMessage('페이백 대상은 무료로 바꿀 수 없습니다. 페이백 화면에서 먼저 빼 주세요.');
                }
                if ($payback->isTransferred()) {
                    return helpersFailMessage('페이백 입금이 끝난 사람은 결제 유형을 바꿀 수 없습니다.');
                }
            }

            DB::transaction(function () use ($roster, $payback, $event, $payType): void {
                $roster->pay_type = $payType;
                $roster->save();

                if ($payback !== null) {
                    $earlyBird = $payType === EventRoster::PAY_EARLY_BIRD;
                    $before    = $event->paidPriceFor($roster->platform, $roster->gender, (bool) $payback->is_early_bird);
                    $after     = $event->paidPriceFor($roster->platform, $roster->gender, $earlyBird);

                    $payback->is_early_bird = $earlyBird;
                    if ($before !== null && $after !== null && (int) $payback->paid_amount === $before) {
                        $payback->paid_amount    = $after;
                        $payback->payback_amount = EventPayback::calcAmount($after, (int) $payback->payback_rate);
                    }
                    $payback->save();
                }
            });

            return helpersSuccessMessage('결제 유형을 저장했습니다.');
        } catch (\Throwable $th) {
            return helpersFailMessage($th->getMessage());
        }
    }
}
