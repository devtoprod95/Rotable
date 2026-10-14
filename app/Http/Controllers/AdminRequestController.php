<?php

namespace App\Http\Controllers;

use App\Models\AdminRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 관리자 플로팅 위젯용 — 회원이 보낸 요청(admin_requests) 조회/처리.
 */
class AdminRequestController extends Controller
{
    public function __construct(protected Request $request)
    {
    }

    /**
     * 요청 목록 (Tabulator ajax, 페이지네이션 + 검색/필터).
     */
    public function listData(): JsonResponse
    {
        $page = (int) $this->request->input('page', 1);
        $size = (int) $this->request->input('size', 30);
        $sort = $this->request->input('sort.0', ['field' => 'id', 'dir' => 'desc']);
        $eventId = (int) $this->request->input('eventId', 0);
        $memberId = (int) $this->request->input('memberId', 0);
        $read = $this->request->input('read', '');
        $searchText = $this->request->input('searchText', '');

        $query = AdminRequest::query()
            ->with(['member:id,name,gender', 'eventRound:id,title']);

        if ($eventId > 0) {
            $query->where('event_id', $eventId);
        }
        if ($memberId > 0) {
            $query->where('member_id', $memberId);
        }
        if ($read === 'Y') {
            $query->whereNotNull('read_at');
        } elseif ($read === 'N') {
            $query->whereNull('read_at');
        }
        if ($searchText !== '') {
            $query->where(function ($q) use ($searchText) {
                $q->where('body', 'like', '%' . $searchText . '%')
                    ->orWhereHas('member', fn ($mq) => $mq->where('name', 'like', '%' . $searchText . '%'));
            });
        }

        if (!empty($sort['field'])) {
            $query->orderBy($sort['field'], $sort['dir'] ?? 'desc');
        }

        $paginator = $query->paginate($size, ['*'], 'page', $page);

        $paginator->getCollection()->transform(fn (AdminRequest $req) => $this->transform($req));

        return response()->json($paginator);
    }

    /**
     * 최근 요청 목록 + 미확인 개수.
     * 플로팅 위젯 최초 로딩 시 호출한다.
     */
    public function index(): JsonResponse
    {
        $limit = (int) $this->request->input('limit', 30);

        $requests = AdminRequest::query()
            ->with(['member:id,name,gender', 'eventRound:id,title'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (AdminRequest $req) => $this->transform($req))
            ->values();

        $unread = AdminRequest::whereNull('read_at')->count();

        return response()->json([
            'requests'    => $requests,
            'unreadCount' => $unread,
        ]);
    }

    /**
     * 요청 1건을 확인(읽음) 처리한다.
     */
    public function markRead(int $id): JsonResponse
    {
        $req = AdminRequest::find($id);
        if ($req === null) {
            return apiRes(Response::HTTP_NOT_FOUND, helpersFailMessage('요청을 찾을 수 없습니다.'));
        }

        if ($req->read_at === null) {
            $req->read_at = now();
            $req->save();

            // 요청을 보낸 회원에게 "확인됨" 실시간 전파.
            broadcast(new \App\Events\AdminRequestRead((int) $req->member_id, [(int) $req->id]));
        }

        return apiRes(Response::HTTP_OK, helpersSuccessMessage());
    }

    /**
     * 모든 미확인 요청을 한 번에 확인 처리한다.
     */
    public function markAllRead(): JsonResponse
    {
        // 처리 전에 대상(회원별 요청 id)을 먼저 수집한다.
        $unread = AdminRequest::whereNull('read_at')->get(['id', 'member_id']);

        if ($unread->isEmpty()) {
            return apiRes(Response::HTTP_OK, helpersSuccessMessage());
        }

        AdminRequest::whereIn('id', $unread->pluck('id'))->update(['read_at' => now()]);

        // 회원별로 묶어 각 회원 채널로 "확인됨"을 전파.
        $unread
            ->groupBy('member_id')
            ->each(function ($rows, $memberId) {
                broadcast(new \App\Events\AdminRequestRead(
                    (int) $memberId,
                    $rows->pluck('id')->map(fn ($id) => (int) $id)->all()
                ));
            });

        return apiRes(Response::HTTP_OK, helpersSuccessMessage());
    }

    /**
     * 위젯 렌더용 변환.
     *
     * @return array<string, mixed>
     */
    private function transform(AdminRequest $req): array
    {
        return [
            'id'          => $req->id,
            'body'        => $req->body,
            'read_at'     => $req->read_at?->toIso8601String(),
            'created_at'  => $req->created_at?->toIso8601String(),
            'event_id'    => $req->event_id,
            'event_title' => $req->eventRound?->title,
            'table_no'    => $req->table_no,
            'member'      => $req->member ? [
                'id'     => $req->member->id,
                'name'   => $req->member->name,
                'gender' => $req->member->gender,
            ] : null,
        ];
    }
}
