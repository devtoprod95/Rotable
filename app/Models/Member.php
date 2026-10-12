<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'members';

    /** 성별 */
    public const GENDER_MALE   = 'M'; // 남
    public const GENDER_FEMALE = 'F'; // 여

    /**
     * 이미 로그인된 상태에서도 입장/입장등록 화면 접근이 허용되는 예외 회원 이름.
     * (운영/디버깅 편의용)
     */
    public const BYPASS_REDIRECT_NAME = '김도한';

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'name',
        'phone',
        'password',
        'gender',
        'birth_year',
        'job',
        'height',
        'mbti',
        'region',
        'is_smoker',
    ];

    /** @var list<string> */
    protected $hidden = ['password'];

    /**
     * 비밀번호는 요구사항에 따라 평문으로 저장한다.
     * (암호화/해싱하지 않으므로 'hashed' 캐스트를 사용하지 않는다.)
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_year' => 'integer',
            'height'     => 'integer',
            'is_smoker'  => 'boolean',
        ];
    }

    /**
     * 입장/등록한 회차.
     *
     * @return BelongsTo<EventRound, Member>
     */
    public function eventRound(): BelongsTo
    {
        return $this->belongsTo(EventRound::class, 'event_id');
    }
}
