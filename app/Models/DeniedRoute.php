<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Route;

class DeniedRoute extends Model
{
    protected $table    = 'denied_routes';
    protected $fillable = [];
    protected $hidden   = [];
    protected $guarded  = [];
    protected $appends  = ['route_url'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_has_denied_routes', // 중간 테이블명
            'route_id',              // 현재 모델의 FK
            'role_id'                // 상대 모델의 FK
        );
    }

    public function getRouteUrlAttribute(): string
    {
        return Route::has($this->route)
        ? route($this->route, [], false)
        : '';
    }
}
