<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 상세페이지(홍보용) "이름 붙여 저장한 목록" 1건.
 *
 * - event_page_details(유형당 1행, 현재 작업본)와 달리, 작성 HTML 전체를
 *   이름(title)을 붙여 유형(type)별로 여러 개 보관하는 스냅샷이다.
 *
 * @property int         $id
 * @property string      $type              상세페이지 유형 ('wine' | 'solo')
 * @property string      $title             저장 이름(라벨)
 * @property string|null $html              저장 시점의 상세페이지 HTML 전체
 * @property string|null $saved_by          저장한 관리자 이름
 * @property string|null $saved_by_username 저장한 관리자 아이디
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class EventPageDetailSave extends Model
{
    protected $table = 'event_page_detail_saves';

    /** @var list<string> */
    protected $fillable = [
        'type',
        'title',
        'html',
        'saved_by',
        'saved_by_username',
    ];
}
