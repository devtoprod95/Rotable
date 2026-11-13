<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\EventRound;
use Illuminate\Validation\Rule;

/**
 * 행사 정보 수정. 상태(status)는 로테이션 흐름에서 관리하므로 받지 않는다.
 */
class EventUpdateRequest extends ApiFormRequest
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
            'review_gate_enabled' => 'nullable|boolean',
        ];
    }
}
