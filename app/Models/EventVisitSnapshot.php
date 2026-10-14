<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 방문 통계 일일 스냅샷.
 *
 * @property \Illuminate\Support\Carbon $snapshot_date
 * @property int $total_visits
 * @property int $total_male
 * @property int $total_female
 * @property int $visit_1
 * @property int $visit_1_male
 * @property int $visit_1_female
 * @property int $visit_2
 * @property int $visit_2_male
 * @property int $visit_2_female
 * @property int $visit_3
 * @property int $visit_3_male
 * @property int $visit_3_female
 * @property int $visit_4
 * @property int $visit_4_male
 * @property int $visit_4_female
 * @property int $visit_5
 * @property int $visit_5_male
 * @property int $visit_5_female
 * @property int $visit_6_plus
 * @property int $visit_6_plus_male
 * @property int $visit_6_plus_female
 */
class EventVisitSnapshot extends Model
{
    protected $table = 'event_visit_snapshots';

    /** @var list<string> */
    protected $fillable = [
        'snapshot_date',
        'total_visits',
        'total_male',
        'total_female',
        'visit_1', 'visit_1_male', 'visit_1_female',
        'visit_2', 'visit_2_male', 'visit_2_female',
        'visit_3', 'visit_3_male', 'visit_3_female',
        'visit_4', 'visit_4_male', 'visit_4_female',
        'visit_5', 'visit_5_male', 'visit_5_female',
        'visit_6_plus', 'visit_6_plus_male', 'visit_6_plus_female',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'total_visits'  => 'integer',
            'total_male'    => 'integer',
            'total_female'  => 'integer',
            'visit_1'            => 'integer',
            'visit_1_male'       => 'integer',
            'visit_1_female'     => 'integer',
            'visit_2'            => 'integer',
            'visit_2_male'       => 'integer',
            'visit_2_female'     => 'integer',
            'visit_3'            => 'integer',
            'visit_3_male'       => 'integer',
            'visit_3_female'     => 'integer',
            'visit_4'            => 'integer',
            'visit_4_male'       => 'integer',
            'visit_4_female'     => 'integer',
            'visit_5'            => 'integer',
            'visit_5_male'       => 'integer',
            'visit_5_female'     => 'integer',
            'visit_6_plus'       => 'integer',
            'visit_6_plus_male'  => 'integer',
            'visit_6_plus_female' => 'integer',
        ];
    }
}
