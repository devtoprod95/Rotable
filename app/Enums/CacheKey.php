<?php

namespace App\Enums;

enum CacheKey: string
{
    case VisitorStats               = 'visitor_stats';
    case VisitorTotalUntilYesterday = 'visitor_total_until_yesterday';
    case HTTP_STATUS_COUNTS         = 'http_status_counts';
    case AdminDeniedRoutesRoles     = 'admin_denied_routes_roles';

     public function forToday(): string
    {
        return $this->value . ':' . now()->toDateString();
    }

    public function forRoleIds(string $roleIds): string
    {
        return $this->value . '_' . $roleIds;
    }
}
