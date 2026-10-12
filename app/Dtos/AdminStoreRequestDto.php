<?php
namespace App\Dtos;

class AdminStoreRequestDto extends Dto
{
    public int|null $id      = null;
    public string $username  = '';
    public ?string $password = null;
    public string $name      = '';
    public array $roles      = [];
    public ?string $isActive = '';

    public function __construct(object $data)
    {
        $this->id       = $data->id;
        $this->username = $data->username;
        $this->password = $data->password ?? null;
        $this->name     = $data->name;
        $this->roles    = $data->roles;
        $this->isActive = $data->is_active;
    }

    public function bind(mixed $data): void
    {
    }
}
