<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventPayback;
use Illuminate\Validation\Rule;

/**
 * 신청자 페이백 비율 변경 (없음 / 80% / 100%). 비우면 "없음".
 */
class RosterPaybackRateRequest extends ApiFormRequest
{
    /**
     * 빈 값은 null(페이백 없음)로 본다.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['payback_rate' => $this->cleanNumber($this->input('payback_rate'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payback_rate' => ['nullable', 'integer', Rule::in(array_keys(EventPayback::rateOptions()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payback_rate.integer' => '페이백 비율이 올바르지 않습니다.',
            'payback_rate.in'      => '페이백 비율은 80% 또는 100%만 선택할 수 있습니다.',
        ];
    }
}
