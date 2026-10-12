<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class RoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 또는 권한 로직
    }

    public function rules(): array
    {
        return [
            'display_name' => 'required|string|max:50',
            'name'         => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute 항목은 필수 입력입니다.',
            'string'   => ':attribute 항목은 문자열이어야 합니다.',
            'max'      => ':attribute 항목은 최대 :max자까지 입력 가능합니다.',
            'array'    => ':attribute 항목은 배열 형태여야 합니다.',
            'min'      => ':attribute 항목은 최소 1개 이상 선택해야 합니다.',
            'integer'  => ':attribute 값은 정수여야 합니다.',
            'in'       => ':attribute 선택값이 올바르지 않습니다.',
        ];
    }

    public function attributes(): array
    {
        return [
            'display_name' => '권한명',
            'name'         => '권한값',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $msg = $validator->errors()->first();

        throw new HttpResponseException(apiRes(JsonResponse::HTTP_BAD_REQUEST, helpersFailMessage($msg)));
    }
}
