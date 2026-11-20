<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventCost;

/**
 * 행사 비용 항목 저장 (목록 통째로). 입력 예) costs[0][name]=대관비, costs[0][amount]=200000
 * 이름이 비어 있는 줄은 버린다.
 */
class EventCostSaveRequest extends ApiFormRequest
{
    /**
     * 빈 줄을 버리고, 금액의 쉼표·공백·"원"만 지운다. (마이너스·소수점 등이 섞이면 거부)
     */
    protected function prepareForValidation(): void
    {
        $costs = [];
        foreach ((array) $this->input('costs', []) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $costs[] = ['name' => $name, 'amount' => $this->cleanNumber($row['amount'] ?? null, 0)];
        }
        $this->merge(['costs' => $costs]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'costs'          => ['array', 'max:' . EventCost::MAX_PER_EVENT],
            'costs.*.name'   => ['required', 'string', 'max:50'],
            'costs.*.amount' => ['required', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'costs.max'              => '비용 항목은 최대 ' . EventCost::MAX_PER_EVENT . '개까지 입력할 수 있습니다.',
            'costs.*.name.max'       => '비용 항목명은 50자 이내로 입력해 주세요.',
            'costs.*.amount.integer' => '비용 금액은 0 이상의 정수(원)로 입력해 주세요.',
            'costs.*.amount.max'     => '비용 금액이 너무 큽니다.',
        ];
    }
}
