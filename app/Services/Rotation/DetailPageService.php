<?php

namespace App\Services\Rotation;

use App\Models\EventPageDetail;
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
}
