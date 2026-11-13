<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\Rotation\BepStoreRequest;
use App\Http\Requests\Admin\Rotation\IdsRequest;
use App\Models\EventBepEstimate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 분석·도구 — 손익분기(BEP) 계산기(견적 저장/불러오기) · N빵(수익 정산) 계산기.
 * 계산 자체는 화면(JS)에서 실시간으로 수행한다.
 */
class CalculatorController extends RotationBaseController
{
    /**
     * 손익분기점(BEP) 계산기 화면.
     */
    public function bepIndex(): View
    {
        return view('admin.rotation.bep');
    }

    /**
     * N빵(수익 정산) 계산기 화면. 입력값은 쿼리파라미터로 유지된다.
     */
    public function splitIndex(): View
    {
        return view('admin.rotation.split');
    }

    /**
     * 저장된 BEP 견적 목록. (최신순)
     */
    public function bepList(): JsonResponse
    {
        $estimates = EventBepEstimate::orderByDesc('id')
            ->get(['id', 'name', 'type', 'payload', 'updated_at'])
            ->map(fn (EventBepEstimate $estimate): array => [
                'id'         => $estimate->id,
                'name'       => $estimate->name,
                'type'       => $estimate->type,
                'payload'    => $estimate->payload,
                'updated_at' => $estimate->updated_at?->format('Y-m-d H:i:s'),
            ]);

        return apiRes(Response::HTTP_OK, ['data' => $estimates]);
    }

    /**
     * BEP 견적 저장. id 가 있으면 수정, 없으면 신규 생성.
     */
    public function bepStore(BepStoreRequest $request): JsonResponse
    {
        $id   = (int) $request->validated('id', 0);
        $data = [
            'name'    => $request->validated('name'),
            'type'    => $request->validated('type'),
            'payload' => $request->validated('payload'),
        ];

        if ($id > 0) {
            $estimate = EventBepEstimate::findOrFail($id);
            $estimate->update($data);
        } else {
            $estimate = EventBepEstimate::create($data + ['created_by' => auth('admin')->id()]);
        }

        return apiRes(Response::HTTP_OK, [
            'data' => ['id' => $estimate->id],
            'msg'  => '저장되었습니다.',
        ]);
    }

    /**
     * BEP 견적 삭제. (복수 선택)
     */
    public function bepDelete(IdsRequest $request): JsonResponse
    {
        EventBepEstimate::whereIn('id', $request->validated('ids'))->delete();

        return apiRes(Response::HTTP_OK, ['msg' => '삭제되었습니다.']);
    }
}
