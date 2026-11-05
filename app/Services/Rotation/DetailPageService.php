<?php

namespace App\Services\Rotation;

use App\Models\EventPageDetail;
use App\Models\EventPageDetailSave;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/**
 * 상세페이지(홍보용) 디자이너 도메인 서비스 — event_page_details.
 *
 * HTML 에디터로 꾸민 상세페이지를 유형(wine/solo)별로 저장/로드하고 샘플을 렌더링한다.
 * (RotationService 에서 분리)
 */
class DetailPageService
{
    /** @var array<string, mixed> 기본 응답 메시지 템플릿 */
    protected array $returnMsg = [];

    public function __construct()
    {
        $this->returnMsg = helpersDefaultMessage();
    }

    /** 유형 값 정규화 ('wine' | 'solo') */
    private function detailType(?string $type): string
    {
        return $type === 'solo' ? 'solo' : 'wine';
    }

    /**
     * 저장된 상세페이지 HTML 을 유형별로 반환한다.
     * 저장본이 없으면 해당 유형의 기본 샘플(2열) 을 렌더링해서 돌려준다.
     *
     * @param string $type 상세페이지 유형 ('wine' 와인파티 | 'solo' 1:1 소개팅)
     */
    public function detailHtmlLoad(string $type = 'wine'): string
    {
        return $this->detailPayload($type)['html'];
    }

    /**
     * 상세페이지의 마지막 저장 메타(누가·언제)를 유형별로 반환한다.
     * 저장 이력이 없으면 null.
     *
     * @param string $type 상세페이지 유형 ('wine' | 'solo')
     * @return array{saved_by:string, saved_by_username:?string, saved_at:string}|null
     */
    public function detailMetaLoad(string $type = 'wine'): ?array
    {
        return $this->detailPayload($type)['meta'];
    }

    /**
     * 상세페이지 유형별 저장본(html)과 저장 메타(meta)를 "단 한 번의 조회"로 함께 반환한다.
     *
     * @param string $type 상세페이지 유형 ('wine' 와인파티 | 'solo' 1:1 소개팅)
     * @return array{
     *     html: string,
     *     meta: array{saved_by:string, saved_by_username:?string, saved_at:string}|null
     * }
     */
    public function detailPayload(string $type = 'wine'): array
    {
        $type = $this->detailType($type);

        $detail = EventPageDetail::where('type', $type)->first();

        $html = ($detail !== null && $detail->html !== null && $detail->html !== '')
            ? (string) $detail->html
            : $this->detailSampleHtml(2, $type);

        $meta = ($detail === null || $detail->saved_at === null)
            ? null
            : [
                'saved_by'          => (string) ($detail->saved_by ?? '알 수 없음'),
                'saved_by_username' => $detail->saved_by_username,
                'saved_at'          => $detail->saved_at->format('Y-m-d H:i:s'),
            ];

        return ['html' => $html, 'meta' => $meta];
    }

    /**
     * 기본 샘플 HTML 을 렌더링한다.
     * 실제 행사 데이터와 무관하게 더미 참여자(남/여 각 5명) 로 구성한다.
     *
     * @param int    $columns 행사 배치 열 수 (1 또는 2)
     * @param string $type    상세페이지 유형 ('wine' 와인파티 | 'solo' 1:1 소개팅)
     */
    public function detailSampleHtml(int $columns = 2, string $type = 'wine'): string
    {
        return trim(View::make('admin.rotation._detailSample', [
            'columns' => $columns === 1 ? 1 : 2,
            'type'    => $type === 'solo' ? 'solo' : 'wine',
        ])->render());
    }

    /**
     * 상세페이지 HTML 을 유형별로 DB(event_page_details)에 저장한다.
     * 함께 "누가·언제 저장했는지" 메타도 기록하고, 그 메타를 결과에 담아 돌려준다.
     *
     * @param string $html 저장할 HTML
     * @param string $type 상세페이지 유형 ('wine' 와인파티 | 'solo' 1:1 소개팅)
     * @return array{isSuccess:bool, message?:string, saved?:array{saved_by:string, saved_by_username:?string, saved_at:string}}
     */
    public function detailHtmlSave(string $html, string $type = 'wine'): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $type = $this->detailType($type);

            /** @var \App\Models\Admin|null $admin */
            $admin   = Auth::guard('admin')->user();
            $savedAt = now();

            EventPageDetail::updateOrCreate(
                ['type' => $type],
                [
                    'html'              => $html,
                    'saved_by'          => $admin?->name ?? '알 수 없음',
                    'saved_by_username' => $admin?->username,
                    'saved_at'          => $savedAt,
                ],
            );

            $returnMsg          = helpersSuccessMessage('상세페이지를 저장했습니다.');
            $returnMsg['saved'] = [
                'saved_by'          => $admin?->name ?? '알 수 없음',
                'saved_by_username' => $admin?->username,
                'saved_at'          => $savedAt->format('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /* ======================================================================
     | 이름 붙여 저장한 목록 (event_page_detail_saves) — 유형별 다중 스냅샷
     * ==================================================================== */

    /**
     * "저장 목록"을 최신순으로 반환한다. (html 본문은 제외한 경량 목록)
     *
     * @param  string  $type  상세페이지 유형 ('wine' | 'solo' | 'all'). 'all' 이면 유형 무관 전체.
     * @return array<int, array{id:int, title:string, type:string, saved_by:?string, saved_at:?string}>
     */
    public function snapshotList(string $type = 'wine'): array
    {
        $query = EventPageDetailSave::query()->orderByDesc('id');

        // 'all'(또는 알 수 없는 값이 아닌 명시 유형)일 때만 유형 필터를 건다.
        if ($type !== 'all') {
            $query->where('type', $this->detailType($type));
        }

        return $query
            ->get(['id', 'title', 'type', 'saved_by', 'updated_at'])
            ->map(fn (EventPageDetailSave $s): array => [
                'id'       => (int) $s->id,
                'title'    => (string) $s->title,
                'type'     => (string) $s->type,
                'saved_by' => $s->saved_by,
                'saved_at' => $s->updated_at?->format('Y-m-d H:i'),
            ])
            ->all();
    }

    /**
     * 저장 목록 1건의 HTML 을 반환한다. (불러오기)
     *
     * @return array{isSuccess:bool, message?:string, html?:string, title?:string, type?:string}
     */
    public function snapshotLoad(int $id): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $save = EventPageDetailSave::findOrFail($id);

            $returnMsg          = helpersSuccessMessage('불러왔습니다.');
            $returnMsg['html']  = (string) ($save->html ?? '');
            $returnMsg['title'] = (string) $save->title;
            $returnMsg['type']  = (string) $save->type;
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage('저장본을 찾을 수 없습니다.');
        }

        return $returnMsg;
    }

    /**
     * 상세페이지 HTML 을 "이름(title)을 붙여" 유형별 목록에 새로 저장한다.
     *
     * @param  string  $title  저장 이름(라벨)
     * @param  string  $html   저장할 HTML 전체
     * @param  string  $type   상세페이지 유형 ('wine' | 'solo')
     * @return array{isSuccess:bool, message?:string, item?:array{id:int, title:string, saved_by:?string, saved_at:?string}}
     */
    public function snapshotStore(string $title, string $html, string $type = 'wine'): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $type  = $this->detailType($type);
            $title = trim($title);
            if ($title === '') {
                return helpersFailMessage('저장 이름을 입력해 주세요.');
            }

            /** @var \App\Models\Admin|null $admin */
            $admin = Auth::guard('admin')->user();

            $save = EventPageDetailSave::create([
                'type'              => $type,
                'title'             => mb_substr($title, 0, 100),
                'html'              => $html,
                'saved_by'          => $admin?->name ?? '알 수 없음',
                'saved_by_username' => $admin?->username,
            ]);

            $returnMsg         = helpersSuccessMessage('저장 목록에 추가했습니다.');
            $returnMsg['item'] = [
                'id'       => (int) $save->id,
                'title'    => (string) $save->title,
                'saved_by' => $save->saved_by,
                'saved_at' => $save->updated_at?->format('Y-m-d H:i'),
            ];
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }

    /**
     * 저장 목록 1건의 HTML 을 덮어써서 업데이트한다. (현재 편집 중인 항목 저장)
     * 저장자/시각(updated_at)도 갱신한다.
     *
     * @param  int     $id    업데이트할 저장본 id
     * @param  string  $html  저장할 HTML 전체
     * @return array{isSuccess:bool, message?:string, item?:array{id:int, title:string, saved_by:?string, saved_at:?string}}
     */
    public function snapshotUpdate(int $id, string $html): array
    {
        $returnMsg = $this->returnMsg;

        try {
            $save = EventPageDetailSave::findOrFail($id);

            /** @var \App\Models\Admin|null $admin */
            $admin = Auth::guard('admin')->user();

            $save->html              = $html;
            $save->saved_by          = $admin?->name ?? '알 수 없음';
            $save->saved_by_username = $admin?->username;
            $save->save(); // updated_at 자동 갱신

            $returnMsg         = helpersSuccessMessage('저장했습니다.');
            $returnMsg['item'] = [
                'id'       => (int) $save->id,
                'title'    => (string) $save->title,
                'saved_by' => $save->saved_by,
                'saved_at' => $save->updated_at?->format('Y-m-d H:i'),
            ];
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage('저장본을 찾을 수 없습니다.');
        }

        return $returnMsg;
    }

    /**
     * 초기/유형전환 로드용 — 특정 유형의 "가장 최근 저장본"을 반환한다.
     * 저장본이 있으면 그 id/title/html/meta 를, 없으면 기본 샘플(2열)과 id=null 을 돌려준다.
     *
     * @param  string  $type  상세페이지 유형 ('wine' | 'solo')
     * @return array{
     *     id: ?int,
     *     title: ?string,
     *     html: string,
     *     meta: array{saved_by:string, saved_at:string}|null
     * }
     */
    public function latestSnapshotPayload(string $type = 'wine'): array
    {
        $type = $this->detailType($type);

        $save = EventPageDetailSave::where('type', $type)->orderByDesc('id')->first();

        if ($save === null) {
            // 저장본이 없으면 기본 샘플(2열)로 시작한다. (현재 편집 id 없음)
            return [
                'id'    => null,
                'title' => null,
                'html'  => $this->detailSampleHtml(2, $type),
                'meta'  => null,
            ];
        }

        return [
            'id'    => (int) $save->id,
            'title' => (string) $save->title,
            'html'  => (string) ($save->html ?? ''),
            'meta'  => [
                'saved_by' => (string) ($save->saved_by ?? '알 수 없음'),
                'saved_at' => $save->updated_at?->format('Y-m-d H:i:s') ?? '',
            ],
        ];
    }

    /**
     * 저장 목록 1건을 삭제한다.
     *
     * @return array{isSuccess:bool, message?:string}
     */
    public function snapshotDelete(int $id): array
    {
        $returnMsg = $this->returnMsg;

        try {
            EventPageDetailSave::where('id', $id)->delete();
            $returnMsg = helpersSuccessMessage('삭제했습니다.');
        } catch (\Throwable $th) {
            $returnMsg = helpersFailMessage($th->getMessage());
        }

        return $returnMsg;
    }
}
