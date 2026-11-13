<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Models\Member;
use Illuminate\Validation\Rule;

/**
 * 체크인 명단 1건 수정. 보낸 항목만 바꾼다.
 */
class RosterUpdateRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'     => 'nullable|string|max:50',
            'platform' => ['nullable', Rule::in([Member::PLATFORM_MUNTO, Member::PLATFORM_FRIP])],
            'gender'   => ['nullable', Rule::in([Member::GENDER_MALE, Member::GENDER_FEMALE])],
        ];
    }
}
