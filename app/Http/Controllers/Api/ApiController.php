<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApiController extends Controller
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function log(): JsonResponse
    {
        $request = $this->request;

        // 1. 요청 정보를 활용하여 고유 멱등키 자동 생성 (15초 주기로 갱신하여 무한 테스트 가능)
        $timeWindow = floor(time() / 15);
        $lockKey    = md5($request->ip() . '|' . $request->method() . '|' . $request->path() . '|' . $timeWindow);

        // 2. 이미 등록된 락/멱등 요청이 있는지 DB 조회
        $existing = DB::table('locks')
            ->where('key', $lockKey)
            ->first();

        if ($existing) {
            // 이미 성공적으로 처리가 끝났다면 저장된 성공 결과를 그대로 반환 (멱등성 보장)
            if ($existing->status === 'completed') {
                $responseBody = json_decode($existing->response_body, true);
                return response()->json([
                    'status'      => $existing->response_code,
                    'msg'         => '이미 처리 완료된 멱등 요청입니다. (DB 저장 응답 반환)',
                    'key'         => $lockKey,
                    'time_window' => $timeWindow,
                    'data'        => $responseBody,
                    'cached'      => true
                ], $existing->response_code);
            }

            // 아직 처리 중이라면 동시성 락 충돌 처리 (409 Conflict)
            return response()->json([
                'status' => 409,
                'msg'    => '동일한 요청이 현재 처리 중입니다. (DB 락 획득 실패)',
                'key'    => $lockKey,
            ], 409);
        }

        // 3. 새로운 요청에 대해 DB 락 획득 시도 (Unique Key 제약조건 이용)
        try {
            DB::table('locks')->insert([
                'key'        => $lockKey,
                'status'     => 'processing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            // 동시에 여러 요청이 들어와서 유니크 키 충돌이 발생한 경우 (레이스 컨디션 차단)
            return response()->json([
                'status' => 409,
                'msg'    => '동일한 요청이 동시에 인입되어 차단되었습니다. (Unique Key 충돌)',
                'key'    => $lockKey,
            ], 409);
        }

        // 4. 최초 요청 처리 및 비즈니스 로직 수행
        try {
            $responseData = [
                'processed_at' => now()->toDateTimeString(),
                'client_ip'    => $request->ip(),
                'method'       => $request->method(),
                'path'         => $request->path(),
                'random_id'    => rand(10000, 99999),
            ];

            // 5. 성공 시 DB에 처리 완료로 갱신 및 응답 본문 저장
            DB::table('locks')
                ->where('key', $lockKey)
                ->update([
                    'status'        => 'completed',
                    'response_code' => 200,
                    'response_body' => json_encode($responseData, JSON_UNESCAPED_UNICODE),
                    'updated_at'    => now(),
                ]);

            return response()->json([
                'status'      => 200,
                'msg'         => '요청이 성공적으로 처리되었습니다. (최초 처리)',
                'key'         => $lockKey,
                'time_window' => $timeWindow,
                'data'        => $responseData,
                'cached'      => false
            ]);
        } catch (Throwable $e) {
            // 실패 시 다른 요청이 다시 시도할 수 있도록 락 레코드 삭제
            DB::table('locks')
                ->where('key', $lockKey)
                ->delete();

            return response()->json([
                'status' => 500,
                'msg'    => '처리 중 예외가 발생하여 락이 해제되었습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
