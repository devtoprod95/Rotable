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
     * 금액·비율 입력 정리. 쉼표·공백·"원"·"%" 만 지운다.
     *
     * - 비면 $empty, 숫자만 남으면 정수, 그 밖의 글자(마이너스·소수점 등)가 섞이면 원래 문자열을 그대로 돌려
     *   integer 규칙에서 거부되게 한다. (예: "-5000", "2.5" 를 5000, 25 로 잘못 읽지 않게)
     */
    protected function cleanNumber(mixed $value, ?int $empty = null): int|string|null
    {
        $text = preg_replace('/[,\s원%]/u', '', (string) ($value ?? ''));
        if ($text === '') {
            return $empty;
        }

        return ctype_digit($text) ? (int) $text : (string) $value;
    }

    /**
     * 비어 있지 않으면 정수, 비어 있으면 null. (선택 입력 숫자 필드용)
     */
    public function nullableInt(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }
}
