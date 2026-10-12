<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum Menu: string
{
    case Dashboard = 'dashboard';
    case Rotation  = 'rotation';
    case Admin     = 'admin';
    case Settings  = 'settings';

    private static function menuDefinitions(): array
    {
        return [
            self::Dashboard->value => [
                'name'    => '대시보드',
                'icon'    => 'dashboard',
                'route'   => 'admin.dashboard',
                'pattern' => 'admin.dashboard*',
            ],
            self::Rotation->value => [
                'name'     => '로테이션',
                'icon'     => 'rotate',
                'pattern'  => 'admin.rotation*',
                'children' => [
                    ['name' => '행사 관리', 'route' => 'admin.rotation.event.index', 'pattern' => 'admin.rotation.event*'],
                    ['name' => '좌석 배정', 'route' => 'admin.rotation.seating.index', 'pattern' => 'admin.rotation.seating*'],
                ],
            ],
            self::Admin->value => [
                'name'     => '관리자',
                'icon'     => 'user-cog',
                'pattern'  => 'admin*',
                'children' => [
                    ['name' => '관리자 목록', 'route' => 'admin.index', 'pattern' => 'admin.index*'],
                    [
                        'name'     => '권한 관리',
                        'pattern'  => 'admin.role*',
                        'children' => [
                            ['name' => '권한 목록', 'route' => 'admin.role.index', 'pattern' => 'admin.role.index*'],
                            ['name' => '페이지 권한 목록', 'route' => 'admin.role.route.index', 'pattern' => 'admin.role.route.index*'],
                        ],
                    ],
                ],
            ],
            self::Settings->value => [
                'name'     => '설정',
                'icon'     => 'settings',
                'pattern'  => 'admin.settings*',
                'children' => [
                    ['name' => '아이콘', 'route' => 'admin.settings.icons', 'pattern' => 'admin.settings.icons*'],
                    ['name' => 'K6 부하 테스트', 'route' => 'admin.settings.k6.index', 'pattern' => 'admin.settings.k6.index*'],
                    ['name' => '일반', 'route' => 'admin.settings.general', 'pattern' => 'admin.settings.general*'],
                    ['name' => '보안', 'route' => 'admin.settings.security', 'pattern' => 'admin.settings.security*'],
                    [
                        'name'     => '알림',
                        'pattern'  => 'admin.settings.notifications*',
                        'children' => [
                            ['name' => '이메일', 'route' => 'admin.settings.notifications.email', 'pattern' => 'admin.settings.notifications.email*'],
                            ['name' => 'SMS', 'route' => 'admin.settings.notifications.sms', 'pattern' => 'admin.settings.notifications.sms*'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function info(): array
    {
        return self::menuDefinitions()[$this->value];
    }

    public static function menuPath(?string $routeName = null, ?string $path = null): array
    {
        $routeName ??= (string) request()->route()?->getName();
        $path ??= trim((string) request()->path(), '/');

        $bestMenuPath = [];

        foreach (self::menuDefinitions() as $menuDefinition) {
            $matchedMenuPath = self::findMenuPath($menuDefinition, $routeName, $path);

            // 더 깊은 메뉴일수록 더 구체적인 현재 페이지 경로다.
            if ($matchedMenuPath !== null && count($matchedMenuPath) > count($bestMenuPath)) {
                $bestMenuPath = $matchedMenuPath;
            }
        }

        return $bestMenuPath;
    }

    private static function findMenuPath(array $menuDefinition, string $routeName, string $path, array $currentPath = []): ?array
    {
        if (! self::matchesMenuItem($menuDefinition, $routeName, $path)) {
            return null;
        }

        // 현재 메뉴를 경로에 넣고, 자식 메뉴가 더 맞으면 그 경로로 교체한다.
        $currentPath[] = [
            'name'  => $menuDefinition['name'],
            'route' => $menuDefinition['route'] ?? null,
        ];

        $bestChildPath = $currentPath;

        foreach ($menuDefinition['children'] ?? [] as $childMenu) {
            $childPath = self::findMenuPath($childMenu, $routeName, $path, $currentPath);

            // 자식 메뉴가 더 구체적으로 맞는 경우, 현재 경로보다 자식 경로를 사용한다.
            if ($childPath !== null && count($childPath) > count($bestChildPath)) {
                $bestChildPath = $childPath;
            }
        }

        return $bestChildPath;
    }

    private static function matchesMenuItem(array $menuDefinition, string $routeName, string $path): bool
    {
        $pattern = $menuDefinition['pattern'] ?? null;

        if ($pattern === null) {
            return false;
        }

        // route name 기준과 실제 URL 기준을 둘 다 허용한다.
        return Str::is($pattern, $routeName)
            || Str::is($pattern, $path)
            || Str::is(ltrim($pattern, '/'), $path)
            || Str::is($pattern, '/' . $path);
    }
}
