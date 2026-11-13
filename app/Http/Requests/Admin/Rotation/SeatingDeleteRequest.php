<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * 좌석(기준자리) 1건 제거.
 */
class SeatingDeleteRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
        ];
    }
}
