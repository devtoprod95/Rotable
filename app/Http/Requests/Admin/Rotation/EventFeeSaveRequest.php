<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\Member;

/**
 * 행사 참가비 저장: 플랫폼 × 성별 정가 + 플랫폼별 얼리버드 할인율(%).
 *
 * 입력 예) fee_prices[munto][M]=39000, fee_prices[frip][F]=32000, early_bird_rates[munto]=20, early_bird_rates[frip]=10
 */
class EventFeeSaveRequest extends ApiFormRequest
{
    /**
     * 금액에 섞인 쉼표·"원"·공백, 할인율의 "%"를 지운다.
     */
    protected function prepareForValidation(): void
    {
        $prices = [];
        $rates  = [];
        foreach ($this->platformIds() as $platform) {
            foreach ([Member::GENDER_MALE, Member::GENDER_FEMALE] as $gender) {
                $digits                     = preg_replace('/[^\d]/', '', (string) $this->input("fee_prices.$platform.$gender", ''));
                $prices[$platform][$gender] = $digits === '' ? null : (int) $digits;
            }
            $rate             = preg_replace('/[^\d]/', '', (string) $this->input("early_bird_rates.$platform", ''));
            $rates[$platform] = $rate === '' ? 0 : (int) $rate;
        }

        $this->merge([
            'fee_prices'       => $prices,
            'early_bird_rates' => $rates,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'fee_prices'       => ['array'],
            'early_bird_rates' => ['array'],
        ];
        foreach ($this->platformIds() as $platform) {
            $rules["fee_prices.$platform"]       = ['array'];
            $rules["fee_prices.$platform.*"]     = ['nullable', 'integer', 'min:0', 'max:10000000'];
            $rules["early_bird_rates.$platform"] = ['required', 'integer', 'min:0', 'max:100'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fee_prices.*.*.integer' => '참가비는 숫자로 입력해 주세요.',
            'fee_prices.*.*.max'     => '참가비가 너무 큽니다.',
            'early_bird_rates.*.min' => '얼리버드 할인율은 0~100% 사이로 입력해 주세요.',
            'early_bird_rates.*.max' => '얼리버드 할인율은 0~100% 사이로 입력해 주세요.',
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
     * @return list<string>
     */
    private function platformIds(): array
    {
        return array_column(Member::platformOptions(), 'id');
    }
}
