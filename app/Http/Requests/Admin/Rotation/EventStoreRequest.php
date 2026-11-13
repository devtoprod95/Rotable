<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventRound;
use Illuminate\Validation\Rule;

/**
 * 행사 생성. 상태는 생성 시 무조건 대기(P)로 고정하므로 입력받지 않는다.
 */
class EventStoreRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title'               => 'required|string|max:255',
            'type'                => ['required', Rule::in([EventRound::TYPE_WINE, EventRound::TYPE_ONE])],
            'event_time'          => 'required|date',
            'table_count'         => 'nullable|integer|min:0|max:100',
            'review_gate_enabled' => 'nullable|boolean',
        ];
    }
}
