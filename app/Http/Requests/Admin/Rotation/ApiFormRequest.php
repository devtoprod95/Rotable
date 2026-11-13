<?php

namespace App\Http\Requests\Admin\Rotation;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

/**
 * 로테이션(관리자) API 요청 검증 공통 기반.
 *
 * - 권한은 라우트 미들웨어(role/superAdmin)에서 처리하므로 authorize() 는 true.
 * - 검증 실패 시 프로젝트 표준 응답(apiRes 400 + 첫 번째 오류 메시지)을 돌려준다.
 *   (화면은 xhr.responseJSON.error.message 를 읽는다)
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage($validator->errors()->first()))
        );
    }

    /**
     * 비어 있지 않으면 정수, 비어 있으면 null. (선택 입력 숫자 필드용)
     */
    public function nullableInt(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }
}
