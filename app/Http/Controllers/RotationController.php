<?php

namespace App\Http\Controllers;

use App\Dtos\RotationTableListRequestDto;
use App\Http\Requests\Admin\RotationSeatingStoreRequest;
use App\Models\EventRound;
use App\Services\RotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RotationController extends Controller
{
    public function __construct(
        protected Request $request,
        protected RotationService $rotationService
    ) {
    }

    /* ======================================================================
     | 행사(회차) 관리 — event_rounds 생성/목록/삭제
     * ==================================================================== */

    public function eventIndex(): View
    {
        $params = [
            'page'     => $this->request->input('page') ?: 1,
            'size'     => $this->request->input('size') ?: 30,
            'sort'     => $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'],
            'searchText' => $this->request->input('searchText') ?: '',
            'type'     => $this->request->input('type') ?: '',
            'status'   => $this->request->input('status') ?: '',
            'types'    => EventRound::typeOptions(),
            'statuses' => EventRound::statusOptions(),
            // 방문 통계: 가장 최근 자정 스냅샷 1건. (없으면 null)
            'visitSnapshot' => \App\Models\EventVisitSnapshot::orderByDesc('snapshot_date')->first(),
        ];

        return view('admin.rotation.eventList', $params);
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

    public function eventStore(): JsonResponse
    {
        // 상태는 생성 시 무조건 '대기(P)'로 고정하므로 입력받지 않는다.
        $validator = Validator::make($this->request->all(), [
            'title'       => 'required|string|max:255',
            'type'        => ['required', Rule::in([EventRound::TYPE_WINE, EventRound::TYPE_ONE])],
            'event_time'  => 'required|date',
            'table_count' => 'nullable|integer|min:0|max:100',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage($validator->errors()->first()));
        }

        $data = $validator->validated();
        $data['created_by'] = auth('admin')->id();

        $result = $this->rotationService->eventStore((object) $data);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    public function eventDelete(): JsonResponse
    {
        if ($this->invalidIds()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->eventDelete($this->request->input('ids'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * 행사 상세 — 생성 페이지와 공용 폼 뷰를 사용하며, 테이블을 CRUD 한다.
     */
    public function eventDetail(int $id): View
    {
        $event = $this->rotationService->eventDetail($id);
        abort_if($event === null, 404);

        return view('admin.rotation.eventForm', [
            'event'    => $event,
            'types'    => EventRound::typeOptions(),
            'statuses' => EventRound::statusOptions(),
        ]);
    }

    /**
     * 행사 정보 수정 (상세 화면). 상태(status)는 로테이션 흐름에서 관리하므로 수정하지 않는다.
     */
    public function eventUpdate(int $id): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'title'      => 'required|string|max:255',
            'type'       => ['required', Rule::in([EventRound::TYPE_WINE, EventRound::TYPE_ONE])],
            'event_time' => 'required|date',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage($validator->errors()->first()));
        }

        $result = $this->rotationService->eventUpdate($id, (object) $validator->validated());

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
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
        $result = $this->rotationService->tableAdd($id);

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * 행사 상세에서 테이블 삭제.
     */
    public function eventTableDelete(int $id): JsonResponse
    {
        if ($this->invalidIds()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->tableDelete($this->request->input('ids'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /* ======================================================================
     | 메시지 — 누가 누구에게 보냈는지 조회
     * ==================================================================== */

    /**
     * 행사 메시지 목록 (Tabulator ajax).
     */
    public function eventMessageList(int $id): JsonResponse
    {
        return response()->json($this->rotationService->eventMessageList(
            $id,
            (int) $this->request->input('page', 1),
            (int) $this->request->input('size', 30),
            $this->request->input('sort.0', ['field' => 'id', 'dir' => 'desc']),
            [
                'searchText' => $this->request->input('searchText', ''),
                'read'       => $this->request->input('read', ''),
                'senderId'   => (int) $this->request->input('senderId', 0),
                'convA'      => (int) $this->request->input('convA', 0),
                'convB'      => (int) $this->request->input('convB', 0),
            ]
        ));
    }

    /* ======================================================================
     | 매칭 — 상호 매칭 쌍 조회
     * ==================================================================== */

    /**
     * 행사의 투표/매칭 목록 (모달 Tabulator ajax).
     * - matched=1 이면 "상호 매칭된 쌍"만 남자 기준으로 묶어 반환한다.
     * - 그 외에는 누가 누구를 선택했는지 전체 투표를 선택한 사람 기준으로 묶어 반환한다.
     * Tabulator 가 기대하는 { data, total } 형태로 반환한다.
     */
    public function eventMatchList(int $id): JsonResponse
    {
        $matchedOnly = $this->request->boolean('matched');

        $matches = $matchedOnly
            ? $this->rotationService->matchedPairList($id)
            : $this->rotationService->matchList($id);

        // 매칭 전용 조회는 "쌍 수"를 total 로 반환한다.
        // (한 사람이 여러 명과 매칭될 수 있어 행 수 != 쌍 수 이므로 targets 합으로 센다.)
        if ($matchedOnly) {
            $total = array_sum(array_map(static fn ($row) => count($row['targets'] ?? []), $matches));
        } else {
            $total = count($matches);
        }

        return response()->json([
            'data'  => $matches,
            'total' => $total,
        ]);
    }

    /**
     * 특정 회원을 "선택(지목)한 사람들" 목록 (받은 투표수 모달 Tabulator ajax).
     * Tabulator 가 기대하는 { data, total } 형태로 반환한다.
     */
    public function eventMemberVoterList(int $id, int $memberId): JsonResponse
    {
        $voters = $this->rotationService->memberVoterList($id, $memberId);

        return response()->json([
            'data'  => $voters,
            'total' => count($voters),
        ]);
    }

    /**
     * 특정 회원이 "선택(투표)한 상대들" 목록 (매칭 여부 포함).
     * Tabulator 가 기대하는 { data, total } 형태로 반환한다.
     */
    public function eventMemberVotedList(int $id, int $memberId): JsonResponse
    {
        $targets = $this->rotationService->memberVotedTargetList($id, $memberId);

        return response()->json([
            'data'  => $targets,
            'total' => count($targets),
        ]);
    }

    /* ======================================================================
     | 상세페이지 디자이너 — HTML 에디터로 꾸며서 이미지(PNG)로 다운로드
     | 작성한 HTML 은 DB 가 아니라 storage 파일(공용 템플릿 1개)에 저장한다.
     * ==================================================================== */

    /**
     * 상세페이지 디자이너 화면.
     * 저장된 HTML 이 있으면 그걸, 없으면 기본 샘플 blade 를 초기 내용으로 넘긴다.
     */
    public function detailIndex(): View
    {
        return view('admin.rotation.detail', [
            // 유형별 저장본 (없으면 해당 유형 2열 샘플로 폴백)
            'savedWine'    => $this->rotationService->detailHtmlLoad('wine'),
            'savedSolo'    => $this->rotationService->detailHtmlLoad('solo'),
            // 유형(와인파티/1:1) × 열 수(1열/2열) 조합 샘플
            'wineOne'      => $this->rotationService->detailSampleHtml(1, 'wine'),
            'wineTwo'      => $this->rotationService->detailSampleHtml(2, 'wine'),
            'soloOne'      => $this->rotationService->detailSampleHtml(1, 'solo'),
            'soloTwo'      => $this->rotationService->detailSampleHtml(2, 'solo'),
        ]);
    }

    /**
     * 상세페이지 HTML 저장 (storage 파일에 기록).
     */
    public function detailSave(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'html' => 'nullable|string',
            'type' => 'nullable|in:wine,solo',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage($validator->errors()->first()));
        }

        $result = $this->rotationService->detailHtmlSave(
            (string) $this->request->input('html', ''),
            (string) $this->request->input('type', 'wine'),
        );

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /* ======================================================================
     | 만남 이력 조회 — 전화번호(정확 일치) 기준. 만남 = 같은 행사 공동 참여.
     * ==================================================================== */

    /**
     * 만남 이력 기본 조회 화면. (번호 1개로 함께 참여한 상대 조회)
     */
    public function historyIndex(): View
    {
        return view('admin.rotation.history.search');
    }

    /**
     * 만남 이력 기본 조회 — 특정 전화번호의 사람이 함께 참여한 상대 목록(ajax).
     */
    public function historySearch(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'phone' => 'required|string|max:20',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage($validator->errors()->first()));
        }

        return response()->json(
            $this->rotationService->meetingPartnersByPhone((string) $this->request->input('phone'))
        );
    }

    /**
     * 만남 이력 상세 조회 화면. (번호 2개로 두 사람의 만남 이력 조회)
     * (쿼리스트링 phoneA/phoneB 가 있으면 초기값으로 채운다.)
     */
    public function historyDetailIndex(): View
    {
        return view('admin.rotation.history.detail', [
            'initialPhoneA' => (string) $this->request->query('phoneA', ''),
            'initialPhoneB' => (string) $this->request->query('phoneB', ''),
        ]);
    }

    /**
     * 만남 이력 상세 조회 — 두 전화번호의 만남 이력 상세(ajax).
     */
    public function historyDetailData(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'phoneA' => 'required|string|max:20',
            'phoneB' => 'required|string|max:20',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage($validator->errors()->first()));
        }

        return response()->json(
            $this->rotationService->meetingHistoryBetween(
                (string) $this->request->input('phoneA'),
                (string) $this->request->input('phoneB')
            )
        );
    }

    /* ======================================================================
     | 좌석 배정 — 단일 화면 (턴 선택 + 테이블 그리드, 넣을 때마다 즉시 저장)
     * ==================================================================== */

    public function seatingIndex(): View
    {
        $events  = EventRound::orderByDesc('id')->get();
        $eventId = (int) ($this->request->input('eventId') ?: ($events->first()->id ?? 0));
        $roundNo = (int) ($this->request->input('roundNo') ?? 0);

        return view('admin.rotation.seating', [
            'events'   => $events,
            'eventId'  => $eventId,
            'roundNo'  => $roundNo,
            'statuses' => EventRound::statusOptions(),
        ]);
    }

    /**
     * 특정 행사·턴의 회원 목록과 좌석 현황을 한 번에 반환.
     */
    public function seatingData(): JsonResponse
    {
        $eventId = (int) $this->request->input('eventId');
        $roundNo = (int) $this->request->input('roundNo');

        return response()->json([
            'members' => $this->rotationService->seatingMembers($eventId),
            'board'   => $this->rotationService->seatingBoard($eventId, $roundNo),
        ]);
    }

    /**
     * 특정 행사·턴의 좌석 현황을 테이블 그리드 데이터로 반환.
     */
    public function seatingBoard(): JsonResponse
    {
        $eventId = (int) $this->request->input('eventId');
        $roundNo = (int) $this->request->input('roundNo');

        return response()->json($this->rotationService->seatingBoard($eventId, $roundNo));
    }

    /**
     * 행사의 좌석 배정 대상 회원 목록 (성별 포함).
     */
    public function seatingMembers(): JsonResponse
    {
        $eventId = (int) $this->request->input('eventId');

        return response()->json($this->rotationService->seatingMembers($eventId));
    }

    /**
     * 좌석 추가 (턴 없으면 자동 생성). 넣을 때마다 호출.
     */
    public function seatingStore(RotationSeatingStoreRequest $request): JsonResponse
    {
        $result = $this->rotationService->seatingUpsert((object) $request->validated());

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * 좌석 제거.
     */
    public function seatingDelete(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->seatingDelete((int) $this->request->input('id'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * 로테이션 진행: 현재 턴 기준으로 다음 턴을 자동 생성한다.
     * (여자 고정 / 남자 한 칸 이동)
     */
    public function seatingRotate(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'event_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->rotateProgress((int) $this->request->input('event_id'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * 투표 시작: 로테이션이 끝난 행사의 상태를 '투표중(V)'으로 전환한다.
     */
    public function seatingFinish(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'event_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->openVoting((int) $this->request->input('event_id'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * 투표 마감(수동): 행사 상태를 '종료(X)'로 전환한다.
     * (자동 종료 스케줄러와 별개로, 관리자가 즉시 마감할 때 사용)
     */
    public function seatingCloseVoting(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'event_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->closeVoting((int) $this->request->input('event_id'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }

    /**
     * ids 공통 검증 (벌크 삭제용).
     */
    private function invalidIds(): bool
    {
        $validator = Validator::make($this->request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer',
        ]);

        return $validator->fails();
    }
}
