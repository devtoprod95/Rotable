<?php

namespace App\Enums;

enum Admin: string
{
    case ACTIVE   = '1';
    case INACTIVE = '0';

    case ListSearchUsername      = 'username';
    case ListSearchName          = 'name';
    case RoleListSearchName      = 'display_name';
    case RoleRouteListSearchName = 'route_name';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE                  => '활성',
            self::INACTIVE                => '정지',
            self::ListSearchUsername      => '아아디',
            self::ListSearchName          => '이름',
            self::RoleListSearchName      => '권한명',
            self::RoleRouteListSearchName => '페이지명',
        };
    }

    public static function isActives(): array
    {
        return [
            self::ACTIVE->value   => self::ACTIVE->label(),
            self::INACTIVE->value => self::INACTIVE->label(),
        ];
    }

    public static function listSearchTypes(): array
    {
         return [
            self::ListSearchUsername->value => self::ListSearchUsername->label(),
            self::ListSearchName->value     => self::ListSearchName->label(),
        ];
    }

    public static function roleListSearchTypes(): array
    {
         return [
            self::RoleListSearchName->value => self::RoleListSearchName->label(),
        ];
    }

    public static function roleRouteListSearchTypes(): array
    {
         return [
            self::RoleRouteListSearchName->value => self::RoleRouteListSearchName->label(),
        ];
    }

    public function toBool(): bool
    {
        return $this === self::ACTIVE;
    }
}
