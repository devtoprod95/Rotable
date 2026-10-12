<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use SoftDeletes;
    use HasFactory;

    protected $table    = 'admins';
    protected $fillable = [];
    protected $hidden   = [];
    protected $guarded  = [];

    /**
     * 관리자 - 역할 다대다 관계 정의
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_has_roles', 'admin_id', 'role_id');
    }

    /**
     * 특정 역할을 가지고 있는지 확인하는 헬퍼 메서드
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }
}
