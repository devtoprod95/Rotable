<?php

namespace App\Http\Middleware;

use App\Enums\CacheKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('admin')->check()) {
            /** @var \App\Models\Admin $admin */
            $admin            = auth('admin')->user();
            $currentRouteName = $request->route()->getName();

            // 관리자의 모든 역할 ID를 가져와 고유한 캐시 키 생성
            $roleIds  = $admin->roles->pluck('id')->sort()->implode('_');
            $cacheKey = CacheKey::AdminDeniedRoutesRoles->forRoleIds($roleIds);

            // 역할별 금지된 라우트들의 리스트를 캐시에서 가져옴 (각 역할이 금지하고 있는 라우트들의 집합)
            $rolesDeniedRoutes = cache()->remember($cacheKey, 3600, function () use ($admin) {
                $admin->loadMissing('roles.deniedRoutes');
                // 각 역할별로 금지된 라우트 배열을 구성
                return $admin->roles->mapWithKeys(function ($role) {
                    return [$role->id => $role->deniedRoutes->pluck('route')->toArray()];
                })->toArray();
            });

            // 모든 역할의 금지 목록을 확인하여,
            // '모든 역할에서 금지된 경우'에만 접근을 차단함
            $isDeniedInAllRoles = collect($rolesDeniedRoutes)->every(function ($deniedList) use ($currentRouteName) {
                return in_array($currentRouteName, $deniedList);
            });

            if ($admin->roles->isNotEmpty() && $isDeniedInAllRoles) {
                abort(403, '이 페이지에 접근할 권한이 없습니다. 관리자에 문의해주세요.');
            }
        }

        return $next($request);
    }
}
