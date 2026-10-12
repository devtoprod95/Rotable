<?php
namespace App\Dtos;

class AdminRoleRouteStoreRequestDto extends Dto
{
    public string $route     = '';
    public string $routeName = '';
    public array $roles      = [];

    public function __construct(object $data)
    {
        $this->route     = $data->route;
        $this->routeName = $data->route_name;
        $this->roles     = $data->roles;
    }

    public function bind(mixed $data): void
    {
    }
}
