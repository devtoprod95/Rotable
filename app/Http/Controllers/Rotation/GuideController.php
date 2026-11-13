<?php

namespace App\Http\Controllers\Rotation;

use App\Http\Requests\Admin\Rotation\ScriptSaveRequest;
use App\Models\EventRound;
use App\Services\Rotation\EventContextService;
use App\Services\Rotation\EventNoticeService;
use App\Services\Rotation\ScriptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 안내 — 참가자 안내 슬라이드 · 채팅방 공지/당일 안내 멘트 · 호스트 발표 멘트 · 예외 상황 대처 · 공개 페이지 모음.
 * (안내 슬라이드·발표 멘트는 비인증 공개 페이지도 함께 제공한다)
 */
class GuideController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected EventContextService $eventContextService,
        protected EventNoticeService $noticeService,
        protected ScriptService $scriptService,
    ) {
        parent::__construct($request);
    }

    /**
     * 참가자 안내 슬라이드 화면 (관리자).
     *
     * @param  string|null  $type  'wine'(와인파티) | 'solo'(1:1). 그 외 값이면 와인으로 시작.
     */
    public function guideIndex(?string $type = null): View
    {
        return view('admin.rotation.guide', ['initialType' => $this->slideType($type)]);
    }

    /**
     * 참가자 안내 슬라이드 — 외부 공개용(비인증). 관리자 페이지와 동일한 덱(partial)을 쓴다.
     */
    public function guidePublic(?string $type = null): View
    {
        return view('public.guide', ['initialType' => $this->slideType($type)]);
    }

    /**
     * 채팅방 공지 — '공지 안내' 탭(시작 시간·타입별 공지)과 '당일 안내 멘트' 탭(당일 리마인드).
     * 행사 미지정 시 지금 운영할 행사. 종료(X)된 행사는 목록에서 뺀다. (선택값이 목록에 없으면 첫 행사)
     */
    public function guideNotice(): View
    {
        $events  = $this->eventContextService->selectableEvents()
            ->where('status', '!=', EventRound::STATUS_CLOSED)
            ->values();
        $eventId = (int) ($this->request->input('eventId')
            ?: ($this->eventContextService->currentEventId() ?? 0));
        $event   = $events->firstWhere('id', $eventId) ?? $events->first();
        $place   = (string) $this->request->input('place', '');

        return view('admin.rotation.guideNotice', [
            'events'      => $events,
            'event'       => $event,
            'place'       => $place !== '' ? $place : EventNoticeService::DEFAULT_PLACE,
            'notice'      => $event ? $this->noticeService->build($event, $place) : '',
            'typeOptions' => EventRound::typeOptions(),
            // 탭: notice(공지 안내) | reminder(당일 안내 멘트). 새로고침돼도 보던 탭을 유지한다.
            'tab'         => $this->request->input('tab') === 'reminder' ? 'reminder' : 'notice',
        ]);
    }

    /**
     * 예외 상황 대처 설명서 — 현장 돌발 상황별 처리 방법.
     */
    public function guideException(): View
    {
        return view('admin.rotation.guideException');
    }

    /**
     * 공개 페이지 모음(허브) — 외부 공유/현장 안내용 공개 페이지 링크를 한 화면에 모은다.
     */
    public function publicHub(): View
    {
        return view('admin.rotation.publicHub');
    }

    /**
     * 호스트 발표 멘트(대본) — 외부 공개용(비인증). 핸드폰으로 보기 좋은 단일 페이지.
     */
    public function scriptPublic(?string $type = null): View
    {
        $type = $this->scriptService->normalizeType($type);

        return view('public.script', [
            'type'   => $type,
            'script' => $this->scriptService->get($type),
        ]);
    }

    /**
     * 호스트 발표 멘트 편집 화면 (관리자). 유형별 현재 멘트(저장값 또는 기본값)를 불러와 편집한다.
     */
    public function scriptIndex(): View
    {
        return view('admin.rotation.script', [
            'type'     => $this->scriptService->normalizeType($this->request->query('type')),
            'wine'     => $this->scriptService->get('wine'),
            'solo'     => $this->scriptService->get('solo'),
            'defaults' => ScriptService::DEFAULTS,
        ]);
    }

    /**
     * 호스트 발표 멘트 저장 (관리자). 빈 줄·빈 섹션은 ScriptSaveRequest 에서 정리한다.
     */
    public function scriptSave(ScriptSaveRequest $request): JsonResponse
    {
        $this->scriptService->save(
            (string) $request->validated('type'),
            (string) ($request->validated('title') ?? ''),
            $request->cleanedSections(),
        );

        return apiRes(Response::HTTP_OK, ['msg' => '발표 멘트를 저장했습니다.']);
    }

    /**
     * 안내 슬라이드 유형 정규화: 'solo' 외에는 와인파티.
     */
    private function slideType(?string $type): string
    {
        return $type === 'solo' ? 'solo' : 'wine';
    }
}
