<?php

namespace App\Http\Controllers\Rotation;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 로테이션(관리자) 컨트롤러 공통 기반.
 *
 * 컨트롤러는 "입력 검증(FormRequest) → 서비스 호출 → 응답 변환"만 담당한다.
 * 서비스는 공통 결과 배열(helpersSuccessMessage / helpersFailMessage 형태)을 돌려주므로,
 * 응답 변환은 respond() 한 곳에서 처리한다.
 */
abstract class RotationBaseController extends Controller
{
    public function __construct(protected Request $request)
    {
    }

    /**
     * 서비스 결과 배열을 표준 JSON 응답으로 변환한다.
     *
     * @param  array{isSuccess?:bool}  $result      서비스 결과
     * @param  int                     $failStatus  실패 시 HTTP 상태 (기본 422: 요청은 정상이나 처리 불가)
     */
    protected function respond(array $result, int $failStatus = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return apiRes(($result['isSuccess'] ?? false) === true ? Response::HTTP_OK : $failStatus, $result);
    }

    /**
     * 권한 부족 응답(403).
     */
    protected function forbidden(string $message): JsonResponse
    {
        return apiRes(Response::HTTP_FORBIDDEN, helpersFailMessage($message));
    }

    /**
     * 현재 로그인한 관리자가 최고관리자인지.
     */
    protected function isSuperAdmin(): bool
    {
        return auth('admin')->user()?->hasRole(Role::SuperAdmin->value) ?? false;
    }

    /**
     * 더미 데이터 도구 사용 가능 여부: 최고관리자 && 운영(production) 환경이 아님.
     */
    protected function canUseDummyTools(): bool
    {
        return $this->isSuperAdmin() && ! app()->isProduction();
    }
}
