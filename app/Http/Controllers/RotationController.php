<?php

namespace App\Http\Controllers;

use App\Dtos\RotationTableListRequestDto;
use App\Http\Requests\Admin\RotationSeatingStoreRequest;
use App\Models\EventRound;
use App\Models\QrLink;
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
            'page'       => $this->request->input('page') ?: 1,
            'size'       => $this->request->input('size') ?: 30,
            'sort'       => $this->request->input('sort')[0] ?? ['field' => 'id', 'dir' => 'desc'],
            'searchText' => $this->request->input('searchText') ?: '',
            'type'       => $this->request->input('type') ?: '',
            'status'     => $this->request->input('status') ?: '',
            'types'      => EventRound::typeOptions(),
            'statuses'   => EventRound::statusOptions(),
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
     | QR 코드 설정 — 연결 대상 URL 을 등록하면 공개 주소(/qr/{code}) 가 발급된다.
     | 그 공개 주소를 QR 로 만들어 배포하고, 접근 시 대상 URL 로 리다이렉트한다.
     * ==================================================================== */

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
     * QR 링크 생성/수정.
     * - id 가 있으면 수정, 없으면 신규 생성(코드 자동 발급).
     */
    public function qrStore(): JsonResponse
    {
        // 스킴(http/https)이 없으면 https:// 를 자동으로 붙여 정규화한다. (예: naver.com → https://naver.com)
        // 검증(|url) 전에 보정해야 사용자가 스킴 없이 입력해도 저장된다.
        $this->request->merge([
            'target_url' => $this->normalizeTargetUrl((string) $this->request->input('target_url', '')),
        ]);

        $validator = Validator::make($this->request->all(), [
            'id'         => 'nullable|integer|exists:qr_links,id',
            'title'      => 'required|string|max:255',
            'target_url' => 'required|string|max:2048|url',
            'is_active'  => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage($validator->errors()->first()));
        }

        $id = (int) $this->request->input('id', 0);

        $data = [
            'title'      => $this->request->input('title'),
            'target_url' => $this->request->input('target_url'),
            'is_active'  => $this->request->boolean('is_active', true),
        ];

        if ($id > 0) {
            $link = QrLink::findOrFail($id);
            $link->update($data);
        } else {
            $data['code']       = QrLink::generateUniqueCode();
            $data['created_by'] = auth('admin')->id();
            $link = QrLink::create($data);
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

    /**
     * 연결 대상 URL 정규화.
     *
     * - 스킴(http:// 또는 https://)이 없으면 https:// 를 앞에 붙인다. (예: naver.com → https://naver.com)
     * - `//naver.com` 처럼 스킴만 생략된 프로토콜-상대 URL 도 https 로 보정한다.
     * - 앞뒤 공백은 제거한다. 빈 값이면 그대로 반환한다. (required 검증에서 걸러짐)
     */
    private function normalizeTargetUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return $url;
        }

        // 이미 http:// 또는 https:// 로 시작하면 그대로 둔다. (대소문자 무시)
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        // 프로토콜-상대(//host) 는 앞의 슬래시를 떼고 https 를 붙인다.
        $url = ltrim($url, '/');

        return 'https://' . $url;
    }


    public function qrDelete(): JsonResponse
    {
        if ($this->invalidIds()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        QrLink::whereIn('id', $this->request->input('ids'))->delete();

        return apiRes(Response::HTTP_OK, ['msg' => '삭제되었습니다.']);
    }

    /* ======================================================================
     | 참가자 안내 슬라이드 — 행사장에서 PPT 처럼 띄워 이용안내/룰/대화 주제 안내
     * ==================================================================== */

    /**
     * 참가자 안내 슬라이드 화면. (서비스 이용안내·룰 + 대화 주제 소개)
     */
    public function guideIndex(): View
    {
        return view('admin.rotation.guide');
    }

    /**
     * 예외 상황 대처 설명서 — 현장 돌발 상황별 처리 방법. (별도 페이지)
     */
    public function guideException(): View
    {
        return view('admin.rotation.guideException');
    }

    /**
     * 참가자 안내 슬라이드 — 외부 공개용(비인증). 관리자 페이지와 동일한 덱(partial)을 쓴다.
     *
     * @param  string|null  $type  'wine'(와인파티) | 'solo'(1:1). 없거나 그 외 값이면 와인으로 시작.
     */
    public function guidePublic(?string $type = null): View
    {
        return view('public.guide', [
            'initialType' => $type === 'solo' ? 'solo' : 'wine',
        ]);
    }

    /**
     * 호스트 발표 멘트(대본) — 외부 공개용(비인증). 안내 슬라이드를 보며 읽는 진행 멘트.
     * 핸드폰으로 보기 좋은 단일 페이지로 렌더한다.
     *
     * @param  string|null  $type  'wine'(와인파티) | 'solo'(1:1). 없거나 그 외 값이면 와인으로 시작.
     */
    public function scriptPublic(?string $type = null): View
    {
        $type = $type === 'solo' ? 'solo' : 'wine';

        /** @var array<string, array{title: string, sections: list<array{no: string, heading: string, lines: list<string>}>}> $scripts */
        $scripts = [
            'wine' => [
                'title' => '와인파티 발표 멘트',
                'sections' => [
                    [
                        'no' => '①',
                        'heading' => '로테이블 인사',
                        'lines' => [
                            '안녕하세요, 로테이블입니다! 🍷',
                            '오늘 이렇게 멋진 선남선녀 분들이 자리를 빛내 주셔서 감사합니다.',
                            '잠깐만 슬라이드에 집중해 주시면 안내만 빠르게 드리고, 바로 편하게 대화 나누실 수 있도록 하겠습니다!',
                        ],
                    ],
                    [
                        'no' => '②',
                        'heading' => '오늘의 진행 순서',
                        'lines' => [
                            '먼저 오늘 진행 순서부터 말씀드릴게요.',
                            '지금 앉아 계신 테이블에서 대화를 나누시다가, 안내가 나오면 자리를 이동하시게 됩니다.',
                            '이동은 남성분들만 하시는데, 지금 테이블에서 바로 다음 번호 테이블로 한 칸씩 옮겨 주시면 돼요. 예를 들어 1번은 2번, 2번은 3번으로요.',
                            '마지막 번호 테이블에 계신 남성분은 다시 1번 테이블로 돌아오시면 됩니다. 여성분들은 계속 그 자리에 앉아 계시면 돼요.',
                            '대화 중에 마음에 드는 분이 계시면 앱에서 메시지도 보내시고, 매칭 투표 시간에 투표를 해주시면 되겠습니다.',
                            '투표가 끝나면 2차 자유 대화 시간이 이어지는데, 2차는 필수 참여가 아니니 편하게 선택하시면 돼요. 자리도 따로 정해져 있지 않으니, 마음에 드는 이성분께 자유롭게 가서 앉으시면 됩니다.',
                            '매칭 결과는 내일 오전에 매칭하기 화면에서 확인하실 수 있습니다.',
                            '서로 매칭이 되면 그때 상대방 연락처가 공개되고, 매칭이 안 되면 연락처는 공개되지 않아요. 내가 받은 투표수도 함께 확인하실 수 있습니다.',
                        ],
                    ],
                    [
                        'no' => '③',
                        'heading' => '함께 지킬 약속',
                        'lines' => [
                            '모두가 편하게 즐기실 수 있도록, 몇 가지만 부탁드릴게요.',
                            '서로 존중해 주시고, 이동 안내가 나오면 자기 식기도구와 짐을 챙겨서 신속하게 다음 자리로 옮겨 주세요.',
                            '술이 부담되시는 분을 위해 자몽주스도 준비돼 있으니 편하게 드시고, 과음은 조금만 주의 부탁드려요.',
                            '그리고 와인잔이나 집기 같은 시설물이 파손되면 배상 책임이 생길 수 있으니, 다루실 때 조금만 조심해 주세요.',
                            '진행 중에 불편한 점이 있으시면 언제든 앱에 있는 요청하기로 알려 주시면 바로 도와 드리겠습니다.',
                        ],
                    ],
                    [
                        'no' => '④',
                        'heading' => '미니게임 소개',
                        'lines' => [
                            '대화가 조금 어색하실 때를 위해 미니게임도 준비했어요.',
                            '꼭 하셔야 하는 건 아니에요.',
                            '다만 2차에서는 대화에 집중하는 시간이라, 그때는 게임이 열리지 않습니다.',
                        ],
                    ],
                    [
                        'no' => '⑤',
                        'heading' => '오픈 기념 안내',
                        'lines' => [
                            '마지막으로, 오늘은 오픈 기념 할인으로 모신 자리예요.',
                            '1차가 끝난 뒤에, 신청하셨던 플랫폼에 후기 한 줄만 남겨 주시면 저희한테 큰 힘이 됩니다.',
                            '그럼 지금부터 로테이블 시작하겠습니다. 감사합니다!',
                        ],
                    ],
                ],
            ],
            'solo' => [
                'title' => '1:1 소개팅 발표 멘트',
                'sections' => [
                    [
                        'no' => '①',
                        'heading' => '로테이블 인사',
                        'lines' => [
                            '안녕하세요, 로테이블입니다! 💗',
                            '오늘 이렇게 멋진 선남선녀 분들이 자리를 빛내 주셔서 감사합니다.',
                            '잠깐만 슬라이드에 집중해 주시면 안내만 빠르게 드리고, 바로 편하게 대화 나누실 수 있도록 하겠습니다!',
                        ],
                    ],
                    [
                        'no' => '②',
                        'heading' => '오늘의 진행 순서',
                        'lines' => [
                            '먼저 오늘 진행 순서부터 말씀드릴게요.',
                            '한 분씩 마주 앉아 1:1로 대화를 나누시다가, 안내가 나오면 자리를 이동하시게 됩니다.',
                            '이동은 남성분들만 하시는데, 지금 자리에서 바로 다음 번호 자리로 한 칸씩 옮겨 주시면 돼요. 예를 들어 1번은 2번, 2번은 3번으로요.',
                            '마지막 번호 자리에 계신 남성분은 다시 1번 자리로 돌아오시면 됩니다. 여성분들은 계속 그 자리에 앉아 계시면 돼요.',
                            '대화를 나누시면서 마음에 드는 분이 계시면 앱에서 조용히, 매칭 투표로 저장해 두세요.',
                            '모든 대화가 끝나면 여성분들 먼저, 이어서 남성분들이 나가시고, 5분 안에 매칭 결과를 발표해 드립니다.',
                            '서로 매칭이 되면 그때 상대방 연락처가 공개되고, 매칭이 안 되면 연락처는 공개되지 않아요. 내가 받은 투표수도 함께 확인하실 수 있습니다.',
                        ],
                    ],
                    [
                        'no' => '③',
                        'heading' => '함께 지킬 약속',
                        'lines' => [
                            '모두가 편하게 즐기실 수 있도록, 몇 가지만 부탁드릴게요.',
                            '서로 존중해 주시고, 대화 종료 안내가 나오면 자기 짐을 챙겨서 신속하게 다음 자리로 옮겨 주세요.',
                            '매칭 투표는 비밀이니까, 누굴 고르셨는지는 서로 묻지 않기로 해요.',
                            '과음은 조금만 주의해 주시고, 불편한 점이 있으시면 언제든 운영진에게 말씀해 주세요.',
                            '그리고 집기 같은 시설물이 파손되면 배상 책임이 생길 수 있으니, 다루실 때 조금만 조심해 주세요.',
                        ],
                    ],
                    [
                        'no' => '④',
                        'heading' => '오픈 기념 안내',
                        'lines' => [
                            '오늘은 오픈 기념 할인으로 모신 자리예요.',
                            '대화가 끝난 뒤에, 신청하셨던 플랫폼에 후기 한 줄만 남겨 주시면 저희한테 큰 힘이 됩니다.',
                        ],
                    ],
                    [
                        'no' => '⑤',
                        'heading' => '매칭 선택 전 유의사항',
                        'lines' => [
                            '마지막으로, 매칭 선택 전에 이것만 꼭 기억해 주세요.',
                            '마음에 드는 분을 최대 세 분까지 선택하실 수 있어요.',
                            '제가 선택한 분께는, 서로 매칭이 되지 않더라도 제 연락처가 전해질 수 있습니다.',
                            '그러니 정말 마음에 드는 분을 신중하게 골라 주세요. 자, 그럼 시작하겠습니다!',
                        ],
                    ],
                ],
            ],
        ];

        return view('public.script', [
            'type' => $type,
            'script' => $scripts[$type],
        ]);
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
     * 로테이션 되돌리기: 현재 턴을 한 단계 이전으로 되돌린다.
     * (실수로 "로테이션 진행"을 한 번 더 눌렀을 때 복구용)
     */
    public function seatingRotateBack(): JsonResponse
    {
        $validator = Validator::make($this->request->all(), [
            'event_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return apiRes(Response::HTTP_BAD_REQUEST, helpersFailMessage());
        }

        $result = $this->rotationService->rotateBack((int) $this->request->input('event_id'));

        return apiRes(($result['isSuccess'] === true ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR), $result);
    }
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
