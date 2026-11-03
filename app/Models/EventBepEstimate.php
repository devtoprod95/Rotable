<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 손익분기(BEP) 계산기 저장 견적.
 *
 * 관리자가 계산기 화면에 입력한 값을 이름을 붙여 스냅샷(payload)으로 저장한다.
 *
 * @property int         $id
 * @property string      $name       저장 이름(견적 라벨)
 * @property string      $type       행사 타입 ('wine' | 'one')
 * @property array       $payload    계산기 입력값 스냅샷
 * @property int|null    $created_by admins.id (저장 관리자)
 */
class EventBepEstimate extends Model
{
    protected $table = 'event_bep_estimates';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'type',
        'payload',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * 이 견적을 저장한 관리자.
     *
     * @return BelongsTo<Admin, EventBepEstimate>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
