<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 상세페이지(홍보용) 제작 결과. 유형(type)별로 1행만 존재한다.
 *
 * @property string      $type              상세페이지 유형 ('wine' | 'solo')
 * @property string|null $html              작성한 상세페이지 HTML 전체
 * @property string|null $saved_by          마지막 저장 관리자 이름
 * @property string|null $saved_by_username 마지막 저장 관리자 아이디
 * @property \Illuminate\Support\Carbon|null $saved_at 마지막 저장 시각
 */
class EventPageDetail extends Model
{
    protected $table = 'event_page_details';

    /** @var list<string> */
    protected $fillable = [
        'type',
        'html',
        'saved_by',
        'saved_by_username',
        'saved_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'saved_at' => 'datetime',
    ];
}
