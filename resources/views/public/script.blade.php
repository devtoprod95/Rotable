{{--
    호스트 발표 멘트(대본) 공개 페이지 — 핸드폰으로 보며 읽는 진행 멘트.
    컨트롤러(RotationController@scriptPublic)에서 $type, $script 를 받는다.
    - $type   : 'wine' | 'solo'
    - $script : ['title' => string, 'sections' => [['no','heading','lines'=>[]], ...]]
--}}
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>{{ $script['title'] }} · 로테이블</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $script['title'] }} · 로테이블">
    <meta property="og:description" content="호스트 진행 멘트(대본)">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">

    <style>
        :root { --accent: {{ $type === 'solo' ? '#e86aa6' : '#cf2f4c' }}; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            background: #0f1020; color: #f4f5fb;
            font-family: 'Pretendard', 'Apple SD Gothic Neo', 'Noto Sans KR', system-ui, sans-serif;
            line-height: 1.75; -webkit-text-size-adjust: 100%;
            padding: env(safe-area-inset-top) 0 env(safe-area-inset-bottom);
        }
        .wrap { width: 100%; max-width: 760px; margin: 0 auto; padding: 20px 18px 64px; }

        .brand {
            display: flex; align-items: center; gap: 10px;
            font-weight: 800; font-size: 1.15rem; color: #fff; margin-bottom: 6px;
        }
        .brand img { height: 1.6em; width: auto; }

        .doc-title {
            font-weight: 800; font-size: clamp(1.5rem, 6vw, 2rem);
            margin: 12px 0 4px; color: #fff;
        }
        .doc-sub { color: #aeb2d6; font-size: .95rem; margin-bottom: 8px; }

        /* 타입 전환 토글 */
        .type-toggle { display: inline-flex; gap: 8px; margin: 10px 0 24px; }
        .type-toggle a {
            text-decoration: none; font-weight: 700; font-size: .9rem;
            padding: 8px 16px; border-radius: 999px;
            color: #c7cae6; background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
        }
        .type-toggle a.active { background: var(--accent); color: #fff; border-color: transparent; }

        .section {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 18px; padding: 20px 20px 18px; margin-bottom: 18px;
        }
        .section__head {
            display: flex; align-items: center; gap: 12px; margin-bottom: 14px;
        }
        .section__no {
            flex: 0 0 auto; font-size: 1.5rem; line-height: 1;
            color: var(--accent); font-weight: 800;
        }
        .section__heading {
            font-weight: 800; font-size: clamp(1.15rem, 4.4vw, 1.45rem); color: #fff;
        }
        .section__lines { margin: 0; padding: 0; list-style: none; }
        .section__lines li {
            position: relative; padding: 8px 0 8px 18px;
            font-size: clamp(1.05rem, 4.2vw, 1.25rem); color: #eef0fb;
            border-top: 1px dashed rgba(255,255,255,.08);
        }
        .section__lines li:first-child { border-top: none; }
        .section__lines li::before {
            content: ''; position: absolute; left: 0; top: 1.15em;
            width: 7px; height: 7px; border-radius: 50%; background: var(--accent);
        }

        .foot { text-align: center; color: #70749a; font-size: .85rem; margin-top: 28px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="ROTABLE"> 로테이블 진행 멘트
        </div>

        <h1 class="doc-title">{{ $script['title'] }}</h1>
        <div class="doc-sub">안내 슬라이드를 보며 순서대로 읽어 주세요.</div>

        <div class="type-toggle">
            <a href="{{ route('public.script', ['type' => 'wine']) }}"
               class="{{ $type === 'wine' ? 'active' : '' }}">🍷 와인파티</a>
            <a href="{{ route('public.script', ['type' => 'solo']) }}"
               class="{{ $type === 'solo' ? 'active' : '' }}">💗 1:1 소개팅</a>
        </div>

        @foreach ($script['sections'] as $section)
            <div class="section">
                <div class="section__head">
                    <span class="section__no">{{ $section['no'] }}</span>
                    <span class="section__heading">{{ $section['heading'] }}</span>
                </div>
                <ul class="section__lines">
                    @foreach ($section['lines'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <div class="foot">ROTABLE · 호스트 전용 진행 멘트</div>
    </div>
</body>
</html>
