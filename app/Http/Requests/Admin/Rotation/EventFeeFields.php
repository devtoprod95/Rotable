<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventRound;
use App\Models\Member;

/**
 * 행사 참가비·수수료 입력 필드 (선택 입력) — 행사 생성/수정 요청에서 함께 쓴다.
 *
 * - fee_prices[플랫폼][성별] = 정가(원)
 * - early_bird_rates[플랫폼] = 얼리버드 할인율(%), platform_fee_rates[플랫폼] = 플랫폼 수수료율(%)
 * - 쉼표·공백·"원"·"%" 만 지운다. 마이너스·소수점 등이 섞이면 거부한다. 정가는 비우면 미설정, 얼리버드 할인율은 비우면 0%,
 *   플랫폼 수수료율은 비우면 기본값(EventRound::DEFAULT_PLATFORM_FEE_RATE).
 * - 요청에 참가비 필드가 하나도 없으면 아무것도 하지 않는다. (수정 시 기존 값 유지)
 */
trait EventFeeFields
{
    /**
     * 참가비 필드가 요청에 들어 있는지.
     */
    public function hasFeeFields(): bool
    {
        return $this->has('fee_prices') || $this->has('early_bird_rates') || $this->has('platform_fee_rates');
    }

    /**
     * 검증 전에 참가비 입력값을 숫자로 정리한다. (prepareForValidation 에서 호출)
     */
    protected function prepareFeeFields(): void
    {
        if (! $this->hasFeeFields()) {
            return;
        }

        $prices   = [];
        $rates    = [];
        $feeRates = [];
        foreach ($this->feePlatformIds() as $platform) {
            foreach ([Member::GENDER_MALE, Member::GENDER_FEMALE] as $gender) {
                $prices[$platform][$gender] = $this->cleanNumber($this->input("fee_prices.$platform.$gender"));
            }
            $rates[$platform]    = $this->cleanNumber($this->input("early_bird_rates.$platform"), 0);
            $feeRates[$platform] = $this->cleanNumber($this->input("platform_fee_rates.$platform"), EventRound::DEFAULT_PLATFORM_FEE_RATE);
        }

        $this->merge([
            'fee_prices'         => $prices,
            'early_bird_rates'   => $rates,
            'platform_fee_rates' => $feeRates,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function feeRules(): array
    {
        $rules = [
            'fee_prices'         => ['sometimes', 'array'],
            'early_bird_rates'   => ['sometimes', 'array'],
            'platform_fee_rates' => ['sometimes', 'array'],
        ];
        foreach ($this->feePlatformIds() as $platform) {
            $rules["fee_prices.$platform"]         = ['sometimes', 'array'];
            $rules["fee_prices.$platform.*"]       = ['nullable', 'integer', 'min:0', 'max:10000000'];
            $rules["early_bird_rates.$platform"]   = ['sometimes', 'integer', 'min:0', 'max:100'];
            $rules["platform_fee_rates.$platform"] = ['sometimes', 'integer', 'min:0', 'max:100'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function feeMessages(): array
    {
        return [
            'fee_prices.*.*.integer'       => '참가비는 0 이상의 정수(원)로 입력해 주세요.',
            'early_bird_rates.*.integer'   => '얼리버드 할인율은 0~100 사이의 정수로 입력해 주세요.',
            'platform_fee_rates.*.integer' => '플랫폼 수수료율은 0~100 사이의 정수로 입력해 주세요.',
            'fee_prices.*.*.max'           => '참가비가 너무 큽니다.',
            'early_bird_rates.*.min'       => '얼리버드 할인율은 0~100% 사이로 입력해 주세요.',
            'early_bird_rates.*.max'       => '얼리버드 할인율은 0~100% 사이로 입력해 주세요.',
            'platform_fee_rates.*.min'     => '플랫폼 수수료율은 0~100% 사이로 입력해 주세요.',
            'platform_fee_rates.*.max'     => '플랫폼 수수료율은 0~100% 사이로 입력해 주세요.',
        ];
    }

    /**
     * 저장할 정가 표. 값이 없는 칸은 뺀다.
     *
     * @return array<string, array<string, int>>
     */
    public function prices(): array
    {
        $out = [];
        foreach ((array) $this->validated('fee_prices', []) as $platform => $byGender) {
            foreach ((array) $byGender as $gender => $price) {
                if ($price !== null) {
                    $out[$platform][$gender] = (int) $price;
                }
            }
        }

        return $out;
    }

    /**
     * 저장할 플랫폼별 얼리버드 할인율(%).
     *
     * @return array<string, int>
     */
    public function earlyBirdRates(): array
    {
        return array_map('intval', (array) $this->validated('early_bird_rates', []));
    }

    /**
     * 저장할 플랫폼별 수수료율(%).
     *
     * @return array<string, int>
     */
    public function platformFeeRates(): array
    {
        return array_map('intval', (array) $this->validated('platform_fee_rates', []));
    }

    /**
     * 서비스에 넘길 참가비·수수료 값. 요청에 참가비 필드가 없으면 빈 배열(= 바꾸지 않음).
     *
     * @return array{fee_prices?: array<string, array<string, int>>, early_bird_rates?: array<string, int>, platform_fee_rates?: array<string, int>}
     */
    public function feeData(): array
    {
        if (! $this->hasFeeFields()) {
            return [];
        }

        return [
            'fee_prices'         => $this->prices(),
            'early_bird_rates'   => $this->earlyBirdRates(),
            'platform_fee_rates' => $this->platformFeeRates(),
        ];
    }

    /**
     * @return list<string>
     */
    private function feePlatformIds(): array
    {
        return array_column(Member::platformOptions(), 'id');
    }
}
