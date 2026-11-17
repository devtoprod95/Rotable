<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="Rotable은 로테이션 소개팅·매칭 행사 서비스입니다. 턴마다 자리를 이동하며 새로운 상대를 만나고, 마음에 드는 상대에게 익명 메시지로 호감을 전해보세요.">
        <meta name="keywords" content="Rotable, 로테이션, 소개팅, 매칭, 네트워킹, 익명 메시지, 로테이션 행사">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Rotable">
        <meta property="og:title" content="Rotable">
        <meta property="og:description" content="Rotable은 로테이션 소개팅·매칭 행사 서비스입니다. 턴마다 자리를 이동하며 새로운 상대를 만나고, 마음에 드는 상대에게 익명 메시지로 호감을 전해보세요.">
        <meta property="og:image" content="{{ asset('images/logo.png') }}">
        <meta property="og:url" content="{{ url()->current() }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="Rotable">
        <meta name="twitter:description" content="Rotable은 로테이션 소개팅·매칭 행사 서비스입니다. 턴마다 자리를 이동하며 새로운 상대를 만나고, 마음에 드는 상대에게 익명 메시지로 호감을 전해보세요.">
        <meta name="twitter:image" content="{{ asset('images/logo.png') }}">

        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

        {{-- 웹폰트: Pretendard 는 CDN 을 <link> 로 병렬 로딩 (CSS @import 직렬 로딩 방지). font-display: swap. --}}
        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
        {{-- 로고 폰트(Lilita One)는 우리 서버에서 직접 내려준다. (외부 연결 2개 절약) @font-face 는 아래 <style> --}}
        <link rel="preload" href="/fonts/lilita-one-latin.woff2" as="font" type="font/woff2" crossorigin>
        <style>
            /* 브랜드 로고 폰트 Lilita One (SIL OFL) — 로고 글자(영문)만 쓰므로 라틴 서브셋 하나만 자체 호스팅한다.
               문서 기준 경로(/fonts)로 풀리도록 CSS 파일이 아니라 여기에 둔다. (로컬 Vite dev 서버에서도 동작) */
            @font-face {
                font-family: 'Lilita One';
                font-style: normal;
                font-weight: 400;
                font-display: swap;
                src: url('/fonts/lilita-one-latin.woff2') format('woff2');
                unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
            }
        </style>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable-dynamic-subset.css">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
