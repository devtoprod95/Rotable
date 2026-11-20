<?php

namespace App\Http\Controllers\Rotation;

use App\Dtos\RotationTableListRequestDto;
use App\Http\Requests\Admin\Rotation\EventDrinkSaveRequest;
use App\Http\Requests\Admin\Rotation\EventMemberDrinkServedRequest;
use App\Http\Requests\Admin\Rotation\EventMemberUpdateRequest;
use App\Http\Requests\Admin\Rotation\EventStoreRequest;
use App\Http\Requests\Admin\Rotation\EventUpdateRequest;
use App\Http\Requests\Admin\Rotation\IdsRequest;
use App\Models\EventRound;
use App\Models\EventVisitSnapshot;
use App\Models\Member;
use App\Services\Rotation\EventContextService;
use App\Services\Rotation\MemberLabelService;
use App\Services\Rotation\MemberProfileService;
use App\Services\Rotation\RotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 행사(회차) 관리 — event_rounds 생성/목록/수정/삭제 + 행사의 테이블(rotation_tables) CRUD.
 */
class EventController extends RotationBaseController
{
    public function __construct(
        Request $request,
        protected RotationService $rotationService,
        protected MemberLabelService $memberLabelService,
        protected MemberProfileService $memberProfileService,
        protected EventContextService $eventContextService,
    ) {
        parent::__construct($request);
    }

    public function eventIndex(): View
    {
        return view('admin.rotation.eventList', [
            'page'          => $this->request->input('page') ?: 1,
            'size'          => $this->request->input('size') ?: 30,
            'sort'          => $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'],
            'searchText'    => $this->request->input('searchText') ?: '',
            'type'          => $this->request->input('type') ?: '',
            'status'        => $this->request->input('status') ?: '',
            'types'         => EventRound::typeOptions(),
            'statuses'      => EventRound::statusOptions(),
            // 방문 통계: 가장 최근 자정 스냅샷 1건. (없으면 null)
            'visitSnapshot' => EventVisitSnapshot::orderByDesc('snapshot_date')->first(),
        ]);
    }

    public function eventList(): JsonResponse
    {
        return response()->json($this->rotationService->eventList(
            (int) $this->request->input('page', 1),
            (int) $this->request->input('size', 30),
            $this->request->input('sort.0', ['field' => 'id', 'dir' => 'desc']),
            [
                'searchText' => $this->request->input('searchText', ''),
                'type'       => $this->request->input('type', ''),
                'status'     => $this->request->input('status', ''),
            ]
        ));
    }

    /**
     * 행사 생성 전용 페이지. 상세(eventDetail)와 공용 폼 뷰를 사용한다.
     */
    public function eventCreate(): View
    {
        return view('admin.rotation.eventForm', [
            'event' => null,
            'types' => EventRound::typeOptions(),
        ]);
    }

    public function eventStore(EventStoreRequest $request): JsonResponse
    {
        // 참가비·수수료는 정리된 값(feeData)으로 바꿔 넘긴다.
        $data               = array_merge($request->validated(), $request->feeData());
        $data['created_by'] = auth('admin')->id();

        return $this->respond($this->rotationService->eventStore((object) $data), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function eventDelete(IdsRequest $request): JsonResponse
    {
        return $this->respond($this->rotationService->eventDelete($request->validated('ids')), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 사이드바 "행사 상세" 진입점. 지금 운영할 행사(진행 중 > 가까운 예정 > 최근)의 상세로 보낸다.
     * 행사가 하나도 없으면 행사 생성 화면으로 보낸다. (행사는 상세 화면 상단 탭바에서 바꿀 수 있다.)
     */
    public function eventDetailCurrent(): RedirectResponse
    {
        $eventId = $this->eventContextService->currentEventId();

        if ($eventId === null) {
            return redirect()->route('admin.rotation.event.create');
        }

        return redirect()->route('admin.rotation.event.detail', ['id' => $eventId]);
    }

    /**
     * 행사 상세 — 생성 페이지와 공용 폼 뷰를 사용하며, 테이블을 CRUD 한다.
     */
    public function eventDetail(int $id): View
    {
        $event = $this->rotationService->eventDetail($id);
        abort_if($event === null, 404);

        return view('admin.rotation.eventForm', [
            'event'          => $event,
            'types'          => EventRound::typeOptions(),
            'statuses'       => EventRound::statusOptions(),
            // 참여자 표기 `닉네임(플랫폼)_N호` — 회원 id => 라벨
            'memberLabels'   => $this->memberLabelsFor($id, $event->members),
        ]);
    }

    /**
     * 행사 정보 수정 (상세 화면). 상태(status)는 로테이션 흐름에서 관리하므로 수정하지 않는다.
     */
    public function eventUpdate(EventUpdateRequest $request, int $id): JsonResponse
    {
        // 참가비·수수료 필드가 없으면 feeData 가 비어 기존 값을 그대로 둔다.
        $data = array_diff_key($request->validated(), array_flip(['fee_prices', 'early_bird_rates', 'platform_fee_rates']));

        return $this->respond($this->rotationService->eventUpdate($id, (object) array_merge($data, $request->feeData())), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 행사 음료 선택지 저장 (1:1 소개팅). 입력한 목록으로 통째로 맞춘다.
     */
    public function eventDrinkSave(EventDrinkSaveRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->rotationService->eventDrinkSync($id, $request->validated('drinks') ?? []), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 행사 상세의 참여자 정보 수정. 번호표·성별·테이블은 체크인 화면에서만 바꾼다.
     * 비밀번호는 최고관리자만 바꿀 수 있다. (일반 관리자가 보내도 무시)
     */
    public function eventMemberUpdate(EventMemberUpdateRequest $request, int $id, int $memberId): JsonResponse
    {
        return $this->respond(
            $this->memberProfileService->update($id, $memberId, $request->validated(), $this->isSuperAdmin()),
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    /**
     * 회원 상세 모달용 데이터 1건. 행사 상세의 상세보기 버튼과 같은 형태(label·age·last_login_at, 최고관리자는 password 포함).
     */
    public function eventMemberShow(int $id, int $memberId): JsonResponse
    {
        $member = Member::where('event_id', $id)->find($memberId);
        abort_if($member === null, 404);

        $labelMap = $this->memberLabelService->labelMap($id);

        $data = $member->toArray();
        if ($this->isSuperAdmin()) {
            $data['password'] = $member->password;
        }
        $data['age']           = $member->birth_year ? (now()->year - $member->birth_year + 1) : null;
        $data['last_login_at'] = $member->last_login_at?->format('Y-m-d H:i');
        $data['label']         = $this->memberLabelService->label($labelMap, $member->gender, $member->name, $member->platform);

        return response()->json(['data' => $data]);
    }

    /**
     * 관리자가 해당 회원으로 바로 로그인한다. (회원 화면 확인/현장 지원용)
     * 회원(web) guard 만 건드리므로 관리자 로그인은 유지되고, 이미 다른 회원으로 로그인돼 있으면 덮어쓴다.
     */
    public function eventMemberImpersonate(Request $request, int $id, int $memberId): RedirectResponse
    {
        $member = Member::where('event_id', $id)->find($memberId);
        abort_if($member === null, 404);

        Auth::guard('web')->login($member);
        $request->session()->regenerate();
        // 관리자 대리 로그인 표시 — 이 세션에서는 메시지/답장 읽음 처리를 하지 않는다.
        $request->session()->put('admin_impersonating', true);

        return redirect()->route('home');
    }

    /**
     * 음료 전달 완료 체크/해제 (1:1 소개팅 음료 선택 현황).
     */
    public function eventMemberDrinkServed(EventMemberDrinkServedRequest $request, int $id, int $memberId): JsonResponse
    {
        return $this->respond($this->memberProfileService->setDrinkServed($id, $memberId, $request->boolean('served')));
    }

    /**
     * 행사 상세의 테이블 목록 (Tabulator ajax).
     */
    public function eventTableList(int $id): JsonResponse
    {
        $dto = new RotationTableListRequestDto();
        $dto->bind([
            'eventId' => (string) $id,
            'page'    => $this->request->input('page', 1),
            'size'    => $this->request->input('size', 100),
            'sort'    => $this->request->input('sort.0', ['field' => 'table_no', 'dir' => 'asc']),
        ]);

        return response()->json($this->rotationService->tableList($dto));
    }

    /**
     * 행사 상세에서 테이블 1개 추가.
     */
    public function eventTableAdd(int $id): JsonResponse
    {
        return $this->respond($this->rotationService->tableAdd($id), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 행사 상세에서 테이블 삭제.
     */
    public function eventTableDelete(IdsRequest $request, int $id): JsonResponse
    {
        return $this->respond($this->rotationService->tableDelete($request->validated('ids')), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 행사 회원들의 관리자 표기 라벨 맵(회원 id => `닉네임(플랫폼)_N호`).
     *
     * @param  iterable<int, Member>  $members
     * @return array<int, string>
     */
    private function memberLabelsFor(int $eventId, iterable $members): array
    {
        $map = $this->memberLabelService->labelMap($eventId);

        $out = [];
        foreach ($members as $m) {
            $out[$m->id] = $this->memberLabelService->label($map, $m->gender, $m->name, $m->platform);
        }

        return $out;
    }
}
