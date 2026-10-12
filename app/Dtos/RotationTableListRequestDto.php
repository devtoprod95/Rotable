<?php
namespace App\Dtos;

class RotationTableListRequestDto extends Dto
{
    public string $eventId    = '';
    public string $searchText = '';
    public array $sort        = ['field' => 'id', 'dir' => 'desc'];
    public int $page          = 1;
    public int $size          = 30;
}
