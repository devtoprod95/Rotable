<?php
namespace App\Dtos;

class AdminRoleListRequestDto extends Dto
{
    public array $deninedRoute = [];
    public string $searchType  = '';
    public string $searchText  = '';
    public array $sort         = ['field' => 'id', 'dir' => 'desc'];
    public int $page           = 1;
    public int $size           = 30;

    public function bind(mixed $data): void
    {
        $this->deninedRoute = $data['deninedRoute'];
        $this->searchType   = $data['searchType'];
        $this->searchText   = $data['searchText'];
        $this->sort         = $data['sort'];
        $this->page         = $data['page'];
        $this->size         = $data['size'];
        $this->sort         = $data['sort'];
    }
}
