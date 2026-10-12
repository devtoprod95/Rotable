<?php

namespace App\Models;

use App\Enums\Role as EnumsRole;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table    = 'roles';
    protected $fillable = [];
    protected $hidden   = [];
    protected $guarded  = [];

    // protected function casts(): array
    // {
    //     return [
    //         'name' => EnumsRole::class,
    //     ];
    // }

    public function admins()
    {
        return $this->belongsToMany(
            Admin::class,              // 1. 대상 모델
            'admin_has_roles',         // 2. 중간 테이블명
            'role_id',                 // 3. 현재 모델(Role)의 외래키
            'admin_id'                 // 4. 상대 모델(Admin)의 외래키
        );
    }

    public function deniedRoutes()
    {
        return $this->belongsToMany(DeniedRoute::class, 'role_has_denied_routes', 'role_id', 'route_id');
    }
}
