<?php

namespace App\Http\Requests\Admin\Rotation;

/**
 * QR 링크 생성/수정. id 가 있으면 수정, 없으면 신규(코드 자동 발급).
 */
class QrStoreRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id'         => 'nullable|integer|exists:qr_links,id',
            'title'      => 'required|string|max:255',
            'target_url' => 'required|string|max:2048|url',
            'is_active'  => 'nullable|boolean',
        ];
    }

    /**
     * 검증(url) 전에 연결 대상 URL 을 정규화한다. 스킴 없이 입력해도 저장되도록.
     *  - 스킴(http/https)이 없으면 https:// 를 붙인다. (naver.com → https://naver.com)
     *  - `//naver.com` 같은 프로토콜-상대 URL 도 https 로 보정한다.
     */
    protected function prepareForValidation(): void
    {
        $url = trim((string) $this->input('target_url', ''));

        if ($url !== '' && preg_match('#^https?://#i', $url) !== 1) {
            $url = 'https://' . ltrim($url, '/');
        }

        $this->merge(['target_url' => $url]);
    }
}
