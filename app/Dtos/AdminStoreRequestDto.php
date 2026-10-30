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
        $this->id       = $data->id ?? null;
        $this->username = $data->username;
        $this->password = $data->password ?? null;
        $this->name     = $data->name;
        // 비최고관리자는 roles/is_active 를 전송하지 않으므로 기본값으로 안전하게 처리한다.
        // (실제 반영 여부는 AdminService::store 에서 최고관리자만 적용)
        $this->roles    = $data->roles ?? [];
        $this->isActive = $data->is_active ?? null;
    }

    public function bind(mixed $data): void
    {
    }
}
