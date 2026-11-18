<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>로테이블 참가자 안내</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="로테이블 참가자 안내">
    <meta property="og:description" content="로테이블 소개팅 이용안내와 대화 주제를 확인하세요.">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">

    {{-- Tabler UI (버튼/배지 유틸) + Tabler Icons (ti) : 관리자 레이아웃과 동일 소스 --}}
    <link rel="stylesheet" href="{{ asset('css/tabler.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    {{-- jQuery : 슬라이드 덱(partial)이 의존 --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        html, body { height: 100%; }
        body {
            margin: 0; background: #0f1020; color: #f4f5fb;
            min-height: 100vh; display: flex; flex-direction: column;
        }
        .public-wrap {
            width: 100%; max-width: 1280px; margin: 0 auto;
            padding: clamp(12px, 2.4vw, 28px);
            display: flex; flex-direction: column; gap: clamp(12px, 2vw, 20px);
            flex: 1 1 auto;
        }
        .public-brand {
            display: flex; align-items: center; gap: 10px;
            font-weight: 800; font-size: clamp(1.1rem, 2.4vw, 1.5rem); color: #fff;
        }
        .public-brand img { height: 1.6em; width: auto; }
        .public-event { display: flex; align-items: center; gap: 8px; margin-left: auto; font-size: .9rem; font-weight: 600; color: #c7cae6; }
        .public-event select { max-width: 260px; }
    </style>
</head>
<body>
    <div class="public-wrap">
        <div class="public-brand">
            <img src="{{ asset('images/logo.png') }}" alt="ROTABLE"> 로테이블 참가자 안내
            {{-- 입장등록 QR 이 연결될 행사 선택 --}}
            @if (! empty($events) && $events->count() > 0)
                <label class="public-event">
                    <i class="ti ti-qrcode"></i> 행사
                    <select class="form-select form-select-sm" id="publicGuideEvent">
                        @foreach ($events as $ev)
                            <option value="{{ $ev->id }}" {{ (int) $eventId === (int) $ev->id ? 'selected' : '' }}>{{ $ev->label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>

        {{-- 공유 슬라이드 덱 (관리자 페이지와 동일 partial) --}}
        @include('admin.rotation._guideDeck', ['initialType' => $initialType, 'registerUrl' => $registerUrl ?? null])
    </div>
    {{-- 입장등록 QR 이미지 생성 (전역: QRCode) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // 행사를 바꾸면 같은 주소에 eventId 만 바꿔 다시 연다. (슬라이드 QR 이 그 행사로 다시 만들어진다)
        $('#publicGuideEvent').on('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('eventId', this.value);
            window.location.href = url.toString();
        });
    </script>
</body>
</html>
