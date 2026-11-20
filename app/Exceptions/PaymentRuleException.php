<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * 결제 유형·페이백 규칙 위반으로 저장을 되돌려야 할 때 쓰는 예외.
 *
 * 트랜잭션 안에서 던지면 그 안의 저장(명단 추가·프로필 수정 등)이 모두 롤백되고,
 * 컨트롤러가 잡아서 메시지를 그대로 관리자에게 보여준다.
 */
class PaymentRuleException extends RuntimeException
{
}
