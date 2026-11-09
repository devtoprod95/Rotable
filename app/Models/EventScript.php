<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 호스트 발표 멘트(대본). 유형(type)별로 1행만 존재한다.
 *
 * @property string      $type     발표 멘트 유형 ('wine' | 'solo')
 * @property string|null $title    멘트 제목
 * @property array|null  $sections 섹션 구조 [{no,heading,lines[]}]
 * @property string|null $saved_by 마지막 저장 관리자 이름
 * @property \Illuminate\Support\Carbon|null $saved_at 마지막 저장 시각
 */
class EventScript extends Model
{
    protected $table = 'event_scripts';

    /** @var list<string> */
    protected $fillable = [
        'type',
        'title',
        'sections',
        'saved_by',
        'saved_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'sections' => 'array',
        'saved_at' => 'datetime',
    ];
}
