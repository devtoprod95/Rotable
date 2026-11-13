<?php

namespace App\Http\Requests\Admin\Rotation;

use Illuminate\Validation\Rule;

/**
 * 손익분기(BEP) 견적 저장. id 가 있으면 수정, 없으면 신규.
 */
class BepStoreRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id'      => 'nullable|integer|exists:event_bep_estimates,id',
            'name'    => 'required|string|max:255',
            'type'    => ['required', Rule::in(['wine', 'one'])],
            'payload' => 'required|array',
        ];
    }
}
