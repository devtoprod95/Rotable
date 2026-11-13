<?php

namespace App\Http\Requests\Admin\Rotation;

use App\Services\Rotation\RosterService;

/**
 * [개발용] 더미 체크인 명단 추가 — 남/여 인원.
 */
class RosterDevSeedRostersRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'male'   => 'required|integer|min:0|max:' . RosterService::DEV_ROSTER_MAX,
            'female' => 'required|integer|min:0|max:' . RosterService::DEV_ROSTER_MAX,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'male.*'   => '남성 인원은 0~' . RosterService::DEV_ROSTER_MAX . ' 사이 숫자로 입력해 주세요.',
            'female.*' => '여성 인원은 0~' . RosterService::DEV_ROSTER_MAX . ' 사이 숫자로 입력해 주세요.',
        ];
    }
}
