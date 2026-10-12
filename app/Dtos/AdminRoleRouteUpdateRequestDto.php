<?php
namespace App\Dtos;

class AdminRoleRouteUpdateRequestDto extends Dto
{
    public int $id           = 0;
    public string $routeName = '';
    public array $roles      = [];

    public function __construct(object $data)
    {
        $this->id        = $data->id;
        $this->routeName = $data->route_name;
        $this->roles     = $data->roles;
    }

    public function bind(mixed $data): void
    {
    }
}
