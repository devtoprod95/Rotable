<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventPayback;
use Illuminate\Validation\Rule;

/**
 * 페이백 대상 추가/수정. (추가일 때만 event_roster_id 필수)
 *
 * - 결제 금액은 비워 두면 행사 참가비(얼리버드 여부에 따라)로 채운다.
 * - 페이백 금액은 받지 않는다. 서버가 결제 금액 × 비율로 계산한다.
 */
class PaybackSaveRequest extends ApiFormRequest
{
    /**
     * 금액에 섞인 쉼표·"원"·공백을 지우고, 계좌번호는 숫자와 하이픈만 남긴다.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        $paid = $this->input('paid_amount');
        if (is_string($paid)) {
            $digits               = preg_replace('/[^\d]/', '', $paid);
            $merge['paid_amount'] = $digits === '' ? null : (int) $digits;
        }

        $account = $this->input('account_number');
        if (is_string($account)) {
            $merge['account_number'] = preg_replace('/[^\d-]/', '', $account);
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'event_roster_id' => [$isCreate ? 'required' : 'prohibited', 'integer'],
            'payback_rate'    => ['required', 'integer', Rule::in([EventPayback::RATE_FULL, EventPayback::RATE_PARTIAL])],
            'is_early_bird'   => ['required', 'boolean'],
            'paid_amount'     => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'bank_name'       => ['nullable', 'string', Rule::in(EventPayback::bankOptions())],
            'account_number'  => ['nullable', 'string', 'max:30', 'regex:/^[\d-]*$/'],
            'account_holder'  => ['nullable', 'string', 'max:30'],
            'memo'            => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_roster_id.required' => '페이백 대상자를 선택해 주세요.',
            'payback_rate.required'    => '페이백 비율을 선택해 주세요.',
            'payback_rate.in'          => '페이백 비율은 100% 또는 80%만 선택할 수 있습니다.',
            'paid_amount.integer'      => '결제 금액은 숫자로 입력해 주세요.',
            'paid_amount.max'          => '결제 금액이 너무 큽니다.',
            'account_number.regex'     => '계좌번호는 숫자와 하이픈(-)만 입력해 주세요.',
            'account_number.max'       => '계좌번호가 너무 깁니다.',
            'bank_name.in'             => '은행을 목록에서 선택해 주세요.',
            'account_holder.max'       => '예금주는 30자 이내로 입력해 주세요.',
            'memo.max'                 => '메모는 255자 이내로 입력해 주세요.',
        ];
    }
}
