<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\Rotation\IdsRequest;
use App\Http\Requests\Admin\Rotation\QrStoreRequest;
use App\Models\QrLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * QR 코드 설정 — 연결 대상 URL 을 등록하면 공개 주소(/qr/{code})가 발급되고, 접근 시 대상 URL 로 리다이렉트한다.
 */
class QrController extends RotationBaseController
{
    /**
     * QR 코드 설정 화면 (목록 + 생성 폼).
     */
    public function qrIndex(): View
    {
        return view('admin.rotation.qr', [
            'links' => QrLink::orderByDesc('id')->get(),
        ]);
    }

    /**
     * QR 링크 생성/수정. id 가 있으면 수정, 없으면 신규 생성(코드 자동 발급).
     * (대상 URL 의 스킴 보정은 QrStoreRequest 에서 검증 전에 처리)
     */
    public function qrStore(QrStoreRequest $request): JsonResponse
    {
        $id   = (int) $request->validated('id', 0);
        $data = [
            'title'      => $request->validated('title'),
            'target_url' => $request->validated('target_url'),
            'is_active'  => $request->boolean('is_active', true),
        ];

        if ($id > 0) {
            $link = QrLink::findOrFail($id);
            $link->update($data);
        } else {
            $link = QrLink::create($data + [
                'code'       => QrLink::generateUniqueCode(),
                'created_by' => auth('admin')->id(),
            ]);
        }

        return apiRes(Response::HTTP_OK, [
            'data' => [
                'id'         => $link->id,
                'code'       => $link->code,
                'public_url' => $link->public_url,
            ],
            'msg'  => '저장되었습니다.',
        ]);
    }

    public function qrDelete(IdsRequest $request): JsonResponse
    {
        QrLink::whereIn('id', $request->validated('ids'))->delete();

        return apiRes(Response::HTTP_OK, ['msg' => '삭제되었습니다.']);
    }
}
