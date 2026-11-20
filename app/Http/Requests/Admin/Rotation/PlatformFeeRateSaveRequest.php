<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\Member;

/**
 * 플랫폼별 수수료율(%) 저장. 입력 예) platform_fee_rates[munto]=10, platform_fee_rates[frip]=15
 */
class PlatformFeeRateSaveRequest extends ApiFormRequest
{
    /**
     * "%"·공백을 지우고, 비우면 0 으로 본다.
     */
    protected function prepareForValidation(): void
    {
        $rates = [];
        foreach ($this->platformIds() as $platform) {
            $digits           = preg_replace('/[^\d]/', '', (string) $this->input("platform_fee_rates.$platform", ''));
            $rates[$platform] = $digits === '' ? 0 : (int) $digits;
        }
        $this->merge(['platform_fee_rates' => $rates]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['platform_fee_rates' => ['array']];
        foreach ($this->platformIds() as $platform) {
            $rules["platform_fee_rates.$platform"] = ['required', 'integer', 'min:0', 'max:100'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform_fee_rates.*.min' => '수수료율은 0~100% 사이로 입력해 주세요.',
            'platform_fee_rates.*.max' => '수수료율은 0~100% 사이로 입력해 주세요.',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function rates(): array
    {
        return array_map('intval', (array) $this->validated('platform_fee_rates', []));
    }

    /**
     * @return list<string>
     */
    private function platformIds(): array
    {
        return array_column(Member::platformOptions(), 'id');
    }
}
