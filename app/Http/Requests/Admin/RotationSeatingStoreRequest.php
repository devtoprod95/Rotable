<?php
namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class RotationSeatingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id'          => 'required|integer|exists:event_rounds,id',
            'rotation_table_id' => 'required|integer|exists:rotation_tables,id',
            'member_id'         => 'required|integer|exists:members,id',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute 항목은 필수 입력입니다.',
            'integer'  => ':attribute 값은 정수여야 합니다.',
            'min'      => ':attribute 값은 :min 이상이어야 합니다.',
            'exists'   => '존재하지 않는 :attribute 입니다.',
        ];
    }

    public function attributes(): array
    {
        return [
            'event_id'          => '행사',
            'rotation_table_id' => '테이블',
            'member_id'         => '회원',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $msg = $validator->errors()->first();

        throw new HttpResponseException(apiRes(JsonResponse::HTTP_BAD_REQUEST, helpersFailMessage($msg)));
    }
}
