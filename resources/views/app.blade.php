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
        {{-- 로고 폰트(Lilita One)는 우리 서버에서 직접 내려준다. (외부 연결 2개 절약) @font-face 는 app.css --}}
        <link rel="preload" href="/fonts/lilita-one-latin.woff2" as="font" type="font/woff2" crossorigin>
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
