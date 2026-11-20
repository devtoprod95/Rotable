<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventRoster;
use Illuminate\Validation\Rule;

/**
 * 신청자 결제 유형 변경 (정가 / 얼리버드 / 무료).
 */
class RosterPayTypeRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pay_type' => ['required', Rule::in(array_keys(EventRoster::payTypeOptions()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pay_type.required' => '결제 유형을 선택해 주세요.',
            'pay_type.in'       => '결제 유형이 올바르지 않습니다.',
        ];
    }
}
