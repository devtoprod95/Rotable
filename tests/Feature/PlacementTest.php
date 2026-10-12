<?php

namespace Tests\Feature;

use App\Enums\Ad\AdRequestStatus;
use App\Enums\Bank;
use Tests\TestCase;

class PlacementTest extends TestCase
{
    public function test_bank_enum_labels(): void
    {
        $this->assertEquals('국민은행', Bank::KB->label());
        $this->assertEquals('신한은행', Bank::SHINHAN->label());
        $this->assertEquals('카카오뱅크', Bank::KAKAO->label());
        $this->assertEquals('iM뱅크(대구은행)', Bank::IM->label());
    }

    public function test_ad_request_status_enum_labels(): void
    {
        $this->assertEquals('검수요청', AdRequestStatus::PENDING->label());
        $this->assertEquals('승인완료', AdRequestStatus::APPROVED->label());
        $this->assertEquals('반려', AdRequestStatus::REJECTED->label());
    }
}
