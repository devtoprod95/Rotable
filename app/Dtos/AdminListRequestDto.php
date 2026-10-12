<?php
namespace App\Dtos;

class AdminListRequestDto extends Dto
{
    public string $role        = '';
    public ?string $isActive   = '';
    public array $deninedRoute = [];
    public string $startDate   = '';
    public string $endDate     = '';
    public string $searchType  = '';
    public string $searchText  = '';
    public array $sort         = ['field' => 'id', 'dir' => 'desc'];
    public int $page           = 1;
    public int $size           = 30;
}
