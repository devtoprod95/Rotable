<?php
namespace App\Dtos;

class AdminRoleRouteListRequestDto extends Dto
{
    public string $searchType  = '';
    public string $searchText  = '';
    public array $sort         = ['field' => 'id', 'dir' => 'desc'];
    public int $page           = 1;
    public int $size           = 30;

    public function bind(mixed $data): void
    {
        $this->searchType   = $data['searchType'];
        $this->searchText   = $data['searchText'];
        $this->sort         = $data['sort'];
        $this->page         = $data['page'];
        $this->size         = $data['size'];
        $this->sort         = $data['sort'];
    }
}
