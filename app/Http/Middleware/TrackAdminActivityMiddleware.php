<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Closure;

class TrackAdminActivityMiddleware
{
    private const TTL = 600; // 10분

    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->isMethod('GET') &&
            !$request->ajax() &&
            !$request->expectsJson() &&
            Auth::guard('admin')->check()
        ) {
            $this->updateActivity($request);
        }

        return $next($request);
    }

    private function updateActivity(Request $request): void
    {
        $user = Auth::guard('admin')->user();
        $key  = "online_admin:{$user->id}";

        Redis::hmset($key, [
            'id'             => $user->id,
            'name'           => $user->name,
            'current_page'   => $request->path(),
            'last_active_at' => now()->toDateTimeString(),
        ]);

        Redis::expire($key, self::TTL);
    }
}
