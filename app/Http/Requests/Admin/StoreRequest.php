<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 또는 권한 로직
    }

    public function rules(): array
    {
        // 활성여부·권한 변경은 최고관리자만 가능하다.
        // 비최고관리자는 해당 필드를 전송하지 않으므로(화면에서 숨김) 필수 검증을 걸지 않는다.
        $admin        = auth('admin')->user();
        $isSuperAdmin = $admin !== null && $admin->hasRole(\App\Enums\Role::SuperAdmin->value);

        return [
            'id'                    => 'nullable|integer',
            'username'              => 'required|string|max:50',
            'password'              => 'nullable|string',
            'password_confirmation' => 'nullable|string',
            'name'                  => 'required|string|max:30',
            'roles'                 => ($isSuperAdmin ? 'required|array|min:1' : 'nullable|array'),
            'roles.*'               => 'string',
            'is_active'             => ($isSuperAdmin ? 'required|in:0,1' : 'nullable|in:0,1'),
        ];
    }

    public function messages(): array
    {
        return [
            'required'  => ':attribute 항목은 필수 입력입니다.',
            'string'    => ':attribute 항목은 문자열이어야 합니다.',
            'max'       => ':attribute 항목은 최대 :max자까지 입력 가능합니다.',
            'min'       => ':attribute 항목은 최소 :min자 이상이어야 합니다.',
            'array'     => ':attribute 항목은 배열 형태여야 합니다.',
            'roles.min' => '권한을 최소 1개 이상 선택해야 합니다.',
            'integer'   => ':attribute 값은 정수여야 합니다.',
            'in'        => ':attribute 선택값이 올바르지 않습니다.',
        ];
    }

    public function attributes(): array
    {
        return [
            'username'              => '아이디',
            'password'              => '비밀번호',
            'password_confirmation' => '비밀번호 확인',
            'name'                  => '이름',
            'roles'                 => '권한',
            'is_active'             => '활성 여부',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $msg = $validator->errors()->first();

        throw new HttpResponseException(apiRes(JsonResponse::HTTP_BAD_REQUEST, helpersFailMessage($msg)));
    }
}
