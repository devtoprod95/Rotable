<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QrLink extends Model
{
    protected $table = 'qr_links';

    /** @var list<string> */
    protected $fillable = [
        'code',
        'title',
        'target_url',
        'is_active',
        'hit_count',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hit_count' => 'integer',
        ];
    }

    /**
     * 이 QR 링크를 생성한 관리자.
     *
     * @return BelongsTo<Admin, QrLink>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * 중복되지 않는 공개 리다이렉트 코드를 생성한다.
     */
    public static function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = Str::lower(Str::random($length));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * 공개 리다이렉트 주소 (/qr/{code}) 의 절대 URL.
     */
    public function getPublicUrlAttribute(): string
    {
        return url('/qr/' . $this->code);
    }
}
