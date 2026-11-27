<?php

/*
|--------------------------------------------------------------------------
| 홍보(신청 안내) 공개 페이지 — 유형별 신청 플랫폼 링크
|--------------------------------------------------------------------------
| /public/promo/{type}(wine|one) 에 노출된다. 유형(와인파티 / 1:1)마다 플랫폼 상품 주소가 다르다.
| URL 은 .env 에서 지정하고, 비어 있으면 카드가 '준비중'으로 비활성 표시된다.
*/
return [
    'platforms' => [
        'munto' => [
            'name'  => '문토',
            'color' => '#ff7a1a',
            'urls'  => [
                'wine' => env('PROMO_WINE_MUNTO_URL'),
                'one'  => env('PROMO_ONE_MUNTO_URL'),
            ],
        ],
        'frip'  => [
            'name'  => '프립',
            'color' => '#7b4dff',
            'urls'  => [
                'wine' => env('PROMO_WINE_FRIP_URL'),
                'one'  => env('PROMO_ONE_FRIP_URL'),
            ],
        ],
    ],
];
