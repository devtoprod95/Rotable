<?php

namespace App\Http\Controllers;

use App\Models\QrLink;
use Illuminate\Http\RedirectResponse;

class QrRedirectController extends Controller
{
    /**
     * 공개 QR 리다이렉트. (/qr/{code})
     *
     * 코드에 해당하는 활성 QR 링크가 있으면 설정된 대상 URL 로 302 이동한다.
     * 없거나 비활성이면 404.
     */
    public function redirect(string $code): RedirectResponse
    {
        $link = QrLink::where('code', $code)->where('is_active', true)->first();

        abort_if($link === null, 404);

        // 누적 접근 횟수 증가 (지연 없이 원자적으로).
        $link->increment('hit_count');

        return redirect()->away($link->target_url);
    }
}
