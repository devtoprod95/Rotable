<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\Rotation\DetailSaveStoreRequest;
use App\Http\Requests\Admin\Rotation\DetailSaveUpdateRequest;
use App\Services\Rotation\DetailPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 상세페이지 디자이너 — HTML 에디터로 꾸며 이미지(PNG)로 다운로드. 저장은 '저장 목록'(유형별 다중 스냅샷).
 */
class DetailPageController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected DetailPageService $detailPageService,
    ) {
        parent::__construct($request);
    }

    /**
     * 디자이너 화면. 초기 화면엔 유형별 "가장 최근 저장본"을 띄운다. (없으면 해당 유형 2열 샘플)
     */
    public function detailIndex(): View
    {
        $wine = $this->detailPageService->latestSnapshotPayload('wine');
        $solo = $this->detailPageService->latestSnapshotPayload('solo');

        return view('admin.rotation.detail', [
            // 유형별 초기 HTML (최근 저장본 없으면 2열 샘플)
            'savedWine'        => $wine['html'],
            'savedSolo'        => $solo['html'],
            // 유형별 "현재 편집 중" 저장본 id/제목 — 없으면 null
            'currentIdWine'    => $wine['id'],
            'currentIdSolo'    => $solo['id'],
            'currentTitleWine' => $wine['title'],
            'currentTitleSolo' => $solo['title'],
            // 유형별 마지막 저장 메타(누가·언제) — 없으면 null
            'metaWine'         => $wine['meta'],
            'metaSolo'         => $solo['meta'],
            // 유형(와인파티/1:1) × 열 수(1열/2열) 조합 샘플
            'wineOne'          => $this->detailPageService->detailSampleHtml(1, 'wine'),
            'wineTwo'          => $this->detailPageService->detailSampleHtml(2, 'wine'),
            'soloOne'          => $this->detailPageService->detailSampleHtml(1, 'solo'),
            'soloTwo'          => $this->detailPageService->detailSampleHtml(2, 'solo'),
        ]);
    }

    /**
     * 저장 목록 조회 — 특정 유형의 저장본 목록(최신순, html 제외).
     */
    public function detailSaveList(): JsonResponse
    {
        return response()->json([
            'items' => $this->detailPageService->snapshotList((string) $this->request->input('type', 'wine')),
        ]);
    }

    /**
     * 저장 목록 추가 — 현재 HTML 을 이름(라벨)을 붙여 새로 저장한다.
     */
    public function detailSaveStore(DetailSaveStoreRequest $request): JsonResponse
    {
        $result = $this->detailPageService->snapshotStore(
            (string) $request->validated('title', ''),
            (string) $request->validated('html', ''),
            (string) ($request->validated('type') ?? 'wine'),
        );

        return $this->respond($result, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 저장 목록 1건 불러오기.
     */
    public function detailSaveShow(int $id): JsonResponse
    {
        return $this->respond($this->detailPageService->snapshotLoad($id), Response::HTTP_NOT_FOUND);
    }

    /**
     * 저장 목록 1건 업데이트 — 현재 편집 중인 저장본의 HTML 을 덮어쓴다.
     */
    public function detailSaveUpdate(DetailSaveUpdateRequest $request, int $id): JsonResponse
    {
        return $this->respond(
            $this->detailPageService->snapshotUpdate($id, (string) $request->validated('html', '')),
            Response::HTTP_NOT_FOUND,
        );
    }

    /**
     * 저장 목록 1건 삭제.
     */
    public function detailSaveDelete(int $id): JsonResponse
    {
        return $this->respond($this->detailPageService->snapshotDelete($id), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
