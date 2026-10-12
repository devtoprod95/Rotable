<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>

    <meta name="description" content="Laravel 13 기반의 관리자 전용 페이지 보일러플레이트 프로젝트입니다. 새 프로젝트를 시작할 때 바로 이어서 작업할 수 있도록 관리자 페이지의 기본 레이아웃(헤더, 사이드 네비게이션, 인증 등)을 미리 구축해 둬 프로젝트의 작업속도를 보장하기 위해 개발되졌습니다.">
    <meta name="keywords" content="대시보드, 웹개발, 어드민">

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('images/logo.png') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Dashboard')">
    <meta property="og:description" content="Laravel 13 기반의 관리자 전용 페이지 보일러플레이트 프로젝트입니다. 새 프로젝트를 시작할 때 바로 이어서 작업할 수 있도록 관리자 페이지의 기본 레이아웃(헤더, 사이드 네비게이션, 인증 등)을 미리 구축해 둬 프로젝트의 작업속도를 보장하기 위해 개발되졌습니다.">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Dashboard')">
    <meta name="twitter:description" content="Laravel 13 기반의 관리자 전용 페이지 보일러플레이트 프로젝트입니다. 새 프로젝트를 시작할 때 바로 이어서 작업할 수 있도록 관리자 페이지의 기본 레이아웃(헤더, 사이드 네비게이션, 인증 등)을 미리 구축해 둬 프로젝트의 작업속도를 보장하기 위해 개발되졌습니다.">
    <meta name="twitter:image" content="{{ asset('images/logo.png') }}">

    <title>{{ env('APP_NAME') }} | 로그인</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('css/tabler.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body class="d-flex flex-column bg-light"> <div class="page page-center">
    <div class="container container-tight py-4">

        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" style="height: 100px;">
            <h2 class="fw-bold mt-2 mb-0">{{ config('app.name') }} Admin</h2>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="{{ asset('js/tabler.min.js') }}?v={{ filemtime(public_path('js/tabler.min.js')) }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://npmcdn.com/flatpickr/dist/l10n/ko.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="{{ asset('js/common.js') }}?v={{ filemtime(public_path('js/common.js')) }}"></script>

        @yield('content')
    </div>

</body>
</html>
