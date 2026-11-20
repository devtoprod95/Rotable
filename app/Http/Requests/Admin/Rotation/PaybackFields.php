<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventPayback;
use Illuminate\Validation\Rule;

/**
 * 결제 유형과 함께 보내는 페이백 입력 (선택) — 체크인 명단 추가/수정 · 매출 화면에서 함께 쓴다.
 *
 * - payback_rate   : 비우면 "페이백 없음", 80 또는 100
 * - bank_name      : 은행 목록(EventPayback::bankOptions) 안에서만
 * - account_number : 숫자와 하이픈만 (공백은 지운다)
 * - account_holder : 예금주
 *
 * 페이백 규칙(무료 불가, 입금 완료 잠금 등)은 PaybackService::applyRosterPayment 에서 판정한다.
 */
trait PaybackFields
{
    /**
     * 페이백 비율 키가 요청에 있는지. (없으면 페이백은 건드리지 않는다)
     */
    public function hasPaybackRate(): bool
    {
        return $this->has('payback_rate');
    }

    /**
     * 검증 전에 정리: 비율 빈 값은 null, 계좌번호 공백 제거.
     */
    protected function preparePaybackFields(): void
    {
        $merge = [];
        if ($this->has('payback_rate')) {
            $merge['payback_rate'] = $this->cleanNumber($this->input('payback_rate'));
        }
        $account = $this->input('account_number');
        if (is_string($account)) {
            $merge['account_number'] = preg_replace('/\s/u', '', $account);
        }
        $this->merge($merge);
    }

    /**
     * @return array<string, mixed>
     */
    protected function paybackRules(): array
    {
        return [
            'payback_rate'   => ['nullable', 'integer', Rule::in(array_keys(EventPayback::rateOptions()))],
            'bank_name'      => ['nullable', 'string', Rule::in(EventPayback::bankOptions())],
            'account_number' => ['nullable', 'string', 'max:30', 'regex:/^[\d-]*$/'],
            'account_holder' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function paybackMessages(): array
    {
        return [
            'payback_rate.integer' => '페이백 비율이 올바르지 않습니다.',
            'payback_rate.in'      => '페이백 비율은 80% 또는 100%만 선택할 수 있습니다.',
            'bank_name.in'         => '은행을 목록에서 선택해 주세요.',
            'account_number.regex' => '계좌번호는 숫자와 하이픈(-)만 입력해 주세요.',
            'account_number.max'   => '계좌번호가 너무 깁니다.',
            'account_holder.max'   => '예금주는 30자 이내로 입력해 주세요.',
        ];
    }

    /**
     * PaybackService::applyRosterPayment 에 넘길 값. 요청에 있는 키만 담는다.
     *
     * @return array{pay_type?:string, payback_rate?:?int, bank_name?:?string, account_number?:?string, account_holder?:?string}
     */
    public function paymentInput(): array
    {
        $validated = $this->validated();
        $out       = [];
        foreach (['pay_type', 'payback_rate', 'bank_name', 'account_number', 'account_holder'] as $key) {
            if (array_key_exists($key, $validated)) {
                $out[$key] = $validated[$key];
            }
        }

        return $out;
    }
}
