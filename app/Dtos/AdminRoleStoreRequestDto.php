<?php
namespace App\Dtos;

class AdminRoleStoreRequestDto extends Dto
{
    public string $displayName = '';
    public string $name        = '';

    public function __construct(object $data)
    {
        $this->displayName = $data->display_name;
        $this->name        = $data->name;
    }

    public function bind(mixed $data): void
    {
    }
}
