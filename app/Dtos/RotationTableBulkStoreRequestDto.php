<?php
namespace App\Dtos;

class RotationTableBulkStoreRequestDto extends Dto
{
    public int $eventId = 0;
    public int $count   = 0;

    public function __construct(object $data)
    {
        $this->eventId = (int) $data->event_id;
        $this->count   = (int) $data->count;
    }

    public function bind(mixed $data): void
    {
    }
}
