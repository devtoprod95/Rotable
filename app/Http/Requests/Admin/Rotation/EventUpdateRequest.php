<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventRound;
use Illuminate\Validation\Rule;

/**
 * 행사 정보 수정. 상태(status)는 로테이션 흐름에서 관리하므로 받지 않는다.
 */
class EventUpdateRequest extends ApiFormRequest
{
    use EventFeeFields;

    /**
     * 참가비·수수료(선택 입력)를 숫자로 정리한다.
     */
    protected function prepareForValidation(): void
    {
        $this->prepareFeeFields();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'title'               => 'required|string|max:255',
            'type'                => ['required', Rule::in([EventRound::TYPE_WINE, EventRound::TYPE_ONE])],
            'event_time'          => 'required|date',
            'review_gate_enabled' => 'nullable|boolean',
            'drink_enabled'       => 'nullable|boolean',
        ], $this->feeRules());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->feeMessages();
    }
}
