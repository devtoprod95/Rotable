{{--
    홍보(신청 안내) 공개 페이지 — 유형(와인파티 / 1:1)별 소개·예정 일정·핵심 기능·신청 플랫폼 링크.
    컨트롤러(PublicPromoController@index)에서 받는 값:
    - $type      : 'wine' | 'one'
    - $types     : [type => label]
    - $schedules : 예정 행사(EventRound) 컬렉션, 가까운 순
    - $platforms : [['key','name','color','url'], ...]  (config/promo.php)
--}}
@php
    $intro = [
        'wine' => [
            'badge'   => 'WINE PARTY',
            'title'   => '와인 한 잔과 함께<br>모두와 마주치는 로테이션 파티',
            'desc'    => '3:3 · 4:4 테이블에 앉아 단체로 대화하고, 턴마다 자리를 옮겨 오늘 온 모든 분과 이야기해요.',
            'image'   => 'images/on-site_wine01.png',
            'accent'  => '#cf2f4c',
        ],
        'one'  => [
            'badge'   => '1:1 ROTATION',
            'title'   => '한 분 한 분과 마주 앉는<br>1:1 로테이션 소개팅',
            'desc'    => '정해진 시간 동안 1:1로 대화하고, 턴마다 상대가 바뀌어 모든 이성과 한 번씩 만나요.',
            'image'   => 'images/on-site_solo01.png',
            'accent'  => '#e86aa6',
        ],
    ][$type];

    $features = [
        'wine' => [
            ['ico' => '🔄', 'title' => '테이블 로테이션', 'desc' => '여자는 자리 고정, 남자가 한 칸씩 이동하며 모든 테이블을 만나요.'],
            ['ico' => '🎲', 'title' => '테이블 미니게임', 'desc' => 'Yes or No · 이미지게임 · 밸런스 게임으로 어색함 없이 시작해요.'],
            ['ico' => '💬', 'title' => '익명 메시지',     'desc' => '대화 중 마음에 든 상대에게 익명으로 메시지를 보낼 수 있어요.'],
            ['ico' => '🤫', 'title' => '비밀 투표',       'desc' => '마음에 든 상대를 최대 3명까지 비공개로 선택해요.'],
            ['ico' => '💌', 'title' => '다음 날 매칭 발표', 'desc' => '서로 선택한 경우에만 다음 날 오전 9시에 결과를 알려드려요.'],
        ],
        'one'  => [
            ['ico' => '🔄', 'title' => '1:1 로테이션',    'desc' => '턴마다 상대가 바뀌어 모든 이성과 1:1로 한 번씩 대화해요.'],
            ['ico' => '📱', 'title' => '프로필 확인',     'desc' => '앱에서 이번 턴 상대의 프로필을 보며 대화를 시작해요.'],
            ['ico' => '💬', 'title' => '익명 메시지',     'desc' => '대화 중 마음에 든 상대에게 익명으로 메시지를 보낼 수 있어요.'],
            ['ico' => '🤫', 'title' => '비밀 선택',       'desc' => '마음에 든 상대를 최대 3명까지 비공개로 선택해요.'],
            ['ico' => '💌', 'title' => '상호 매칭',       'desc' => '서로 선택한 경우에만 매칭이 성사돼요.'],
        ],
    ][$type];

    $weekdays    = ['일', '월', '화', '수', '목', '금', '토'];
    $hasSchedule = $schedules->isNotEmpty();
@endphp
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>{{ $types[$type] }} 신청 · 로테이블</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="로테이블 · 로테이션 소개팅 신청">
    <meta property="og:description" content="와인파티 · 1:1 로테이션 소개팅. 문토 · 프립에서 신청하세요.">
    <meta property="og:image" content="{{ asset('images/rotable_main_banner.png') }}">

    <style>
        :root { --accent: {{ $intro['accent'] }}; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            background: #0f1020; color: #e9eaf5;
            font-family: 'Pretendard', 'Apple SD Gothic Neo', 'Noto Sans KR', system-ui, sans-serif;
            line-height: 1.65; -webkit-text-size-adjust: 100%;
            padding: env(safe-area-inset-top) 0 env(safe-area-inset-bottom);
        }
        a { color: inherit; }
        .wrap { width: 100%; max-width: 960px; margin: 0 auto; padding: 20px 18px 72px; }

        /* 상단: 브랜드 + 유형 탭 */
        .top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1.15rem; color: #fff; text-decoration: none; }
        .brand img { height: 1.6em; width: auto; }
        .tabs { display: inline-flex; padding: 4px; gap: 4px; border-radius: 999px; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1); }
        .tabs a {
            text-decoration: none; font-weight: 700; font-size: .92rem;
            padding: 8px 18px; border-radius: 999px; color: #b8bbdb;
        }
        .tabs a.active { background: var(--accent); color: #fff; }

        /* 히어로 */
        .hero { display: grid; grid-template-columns: 1fr; gap: 20px; align-items: center; margin: 24px 0 36px; }
        .hero__img { border-radius: 20px; overflow: hidden; aspect-ratio: 4 / 3; background: #1b1c33; }
        .hero__img img { display: block; width: 100%; height: 100%; object-fit: cover; }
        .badge { display: inline-block; font-size: .75rem; font-weight: 800; letter-spacing: .12em; color: var(--accent); margin-bottom: 8px; }
        .hero__title { font-weight: 800; font-size: clamp(1.55rem, 5.5vw, 2.3rem); line-height: 1.3; margin: 0 0 12px; color: #fff; }
        .hero__desc { color: #aeb2d6; font-size: 1rem; margin: 0; }

        /* 섹션 공통 */
        .section { margin-bottom: 36px; }
        .section__title { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 1.15rem; color: #fff; margin: 0 0 14px; }
        .section__title::before { content: ''; width: 4px; height: 1.1em; border-radius: 2px; background: var(--accent); }

        /* 예정 일정 */
        .schedule { display: grid; grid-template-columns: 1fr; gap: 10px; }
        .schedule__item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 16px; border-radius: 14px;
            background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.09);
        }
        .schedule__date {
            flex: 0 0 auto; width: 58px; text-align: center; border-radius: 12px; padding: 6px 0;
            background: color-mix(in srgb, var(--accent) 22%, transparent); color: #fff;
        }
        .schedule__md { font-weight: 800; font-size: 1.05rem; line-height: 1.2; }
        .schedule__wd { font-size: .78rem; opacity: .85; }
        .schedule__info { min-width: 0; }
        .schedule__name { font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .schedule__time { font-size: .88rem; color: #aeb2d6; }
        .empty {
            display: flex; align-items: center; gap: 14px;
            padding: 18px; border-radius: 16px; color: #aeb2d6;
            background: rgba(255,255,255,.04); border: 1px dashed rgba(255,255,255,.16);
        }
        .empty__ico { flex: 0 0 auto; font-size: 2rem; line-height: 1; }
        .empty__title { font-weight: 800; color: #fff; }
        .empty__desc { font-size: .88rem; margin-top: 2px; }

        /* 핵심 기능 */
        .features { display: grid; grid-template-columns: 1fr; gap: 10px; }
        .feature { padding: 16px; border-radius: 14px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.09); }
        .feature__head { display: flex; align-items: center; gap: 8px; font-weight: 800; color: #fff; }
        .feature__ico { font-size: 1.3rem; line-height: 1; }
        .feature__desc { margin-top: 6px; font-size: .9rem; color: #aeb2d6; }

        /* 신청 카드 (2열) */
        .apply { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .apply__card {
            display: flex; flex-direction: column; justify-content: space-between; gap: 18px;
            min-height: 132px; padding: 18px; border-radius: 18px; text-decoration: none; color: #fff;
            background: var(--btn); border: 1px solid rgba(255,255,255,.14);
            transition: transform .12s ease, filter .12s ease;
        }
        .apply__card:hover { transform: translateY(-2px); filter: brightness(1.12); }
        .apply__name { font-weight: 800; font-size: 1.3rem; }
        .apply__desc { font-size: .85rem; opacity: .85; }
        .apply__go { display: inline-flex; align-items: center; justify-content: center; align-self: stretch; padding: 10px 0; border-radius: 12px; background: rgba(255,255,255,.16); font-weight: 800; font-size: .95rem; }
        .apply__card.is-disabled { background: rgba(255,255,255,.05); color: #8b8fb5; pointer-events: none; }
        .apply__card.is-disabled .apply__go { background: rgba(255,255,255,.06); }

        /* 일정 없음 — 신청 대신 '알림 받기' 톤의 아웃라인 카드 */
        .apply__card.is-waiting { background: rgba(255,255,255,.03); border: 1.5px solid var(--btn); }
        .apply__card.is-waiting .apply__name { color: var(--btn); }
        .apply__card.is-waiting .apply__go { background: transparent; border: 1px solid rgba(255,255,255,.18); color: #e9eaf5; }

        .foot { margin-top: 40px; text-align: center; color: #6f739a; font-size: .8rem; }

        /* 하단 고정 신청하기 바 (가로 전체) */
        html { scroll-behavior: smooth; }
        body { padding-bottom: calc(76px + env(safe-area-inset-bottom)); }
        #apply { scroll-margin-top: 20px; }
        .float-apply {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
            padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
            background: rgba(15,16,32,.88); border-top: 1px solid rgba(255,255,255,.08);
            -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px);
        }
        .float-apply a {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 54px; border-radius: 14px;
            background: var(--accent); color: #fff; text-decoration: none;
            font-weight: 800; font-size: 1.05rem;
        }
        .float-apply a:active { filter: brightness(.92); }
        .float-apply.is-waiting a { background: transparent; border: 1.5px solid var(--accent); color: #fff; }

        /* 태블릿 이상 */
        @media (min-width: 640px) {
            .schedule { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .features { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .apply__card { min-height: 160px; padding: 22px; }
        }
        /* 데스크톱 */
        @media (min-width: 900px) {
            .wrap { padding-top: 32px; }
            .hero { grid-template-columns: 1.05fr 1fr; gap: 36px; margin: 36px 0 48px; }
            .schedule { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .features { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        /* 아주 좁은 화면 */
        @media (max-width: 360px) {
            .apply { gap: 8px; }
            .apply__card { padding: 14px; }
            .apply__name { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <a class="brand" href="{{ route('public.promo', ['type' => $type]) }}">
            <img src="{{ asset('images/logo.png') }}" alt="">
            <span>ROTABLE</span>
        </a>
        <nav class="tabs">
            @foreach (['one', 'wine'] as $typeKey)
                <a href="{{ route('public.promo', ['type' => $typeKey]) }}"
                   class="{{ $type === $typeKey ? 'active' : '' }}">{{ $types[$typeKey] }}</a>
            @endforeach
        </nav>
    </div>

    {{-- 1. 유형 소개 --}}
    <section class="hero">
        <div class="hero__img">
            <img src="{{ asset($intro['image']) }}" alt="{{ $types[$type] }} 현장">
        </div>
        <div>
            <span class="badge">{{ $intro['badge'] }}</span>
            <h1 class="hero__title">{!! $intro['title'] !!}</h1>
            <p class="hero__desc">{{ $intro['desc'] }}</p>
        </div>
    </section>

    {{-- 2. 예정 일정 --}}
    <section class="section">
        <h2 class="section__title">다가오는 일정</h2>
        @if (! $hasSchedule)
            <div class="empty">
                <div class="empty__ico">🗓️</div>
                <div>
                    <div class="empty__title">다음 일정을 준비하고 있어요</div>
                    <div class="empty__desc">아래 플랫폼에서 지난 모임과 후기를 먼저 둘러보세요.</div>
                </div>
            </div>
        @else
            <div class="schedule">
                @foreach ($schedules as $schedule)
                    <div class="schedule__item">
                        <div class="schedule__date">
                            <div class="schedule__md">{{ $schedule->event_time->format('n/j') }}</div>
                            <div class="schedule__wd">{{ $weekdays[$schedule->event_time->dayOfWeek] }}요일</div>
                        </div>
                        <div class="schedule__info">
                            <div class="schedule__name">{{ $schedule->title }}</div>
                            <div class="schedule__time">{{ $schedule->event_time->format('H:i') }} 시작</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- 3. 핵심 기능 --}}
    <section class="section">
        <h2 class="section__title">로테이블은 이렇게 진행돼요</h2>
        <div class="features">
            @foreach ($features as $feature)
                <div class="feature">
                    <div class="feature__head">
                        <span class="feature__ico">{{ $feature['ico'] }}</span>
                        <span>{{ $feature['title'] }}</span>
                    </div>
                    <div class="feature__desc">{{ $feature['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 4. 신청 플랫폼 (2열 카드) — 일정이 없으면 '둘러보기' 톤으로 바꿔 보여준다 --}}
    <section class="section" id="apply">
        <h2 class="section__title">{{ $hasSchedule ? $types[$type].' 신청하기' : '플랫폼 둘러보기' }}</h2>
        <div class="apply">
            @foreach ($platforms as $platform)
                @php
                    $hasUrl    = filled($platform['url']);
                    $cardClass = ! $hasUrl ? 'is-disabled' : (! $hasSchedule ? 'is-waiting' : '');
                    $desc      = ! $hasUrl ? '준비중입니다' : ($hasSchedule ? $platform['name'].' 앱에서 신청' : $platform['name'].'에서 모임 둘러보기');
                    $goLabel   = ! $hasUrl ? '준비중' : ($hasSchedule ? '신청하러 가기 →' : '둘러보기 →');
                @endphp
                <a @if ($hasUrl) href="{{ $platform['url'] }}" target="_blank" rel="noopener" @endif
                   class="apply__card {{ $cardClass }}"
                   style="--btn: {{ $platform['color'] }};">
                    <div>
                        <div class="apply__name">{{ $platform['name'] }}</div>
                        <div class="apply__desc">{{ $desc }}</div>
                    </div>
                    <span class="apply__go">{{ $goLabel }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <div class="foot">© ROTABLE</div>
</div>

{{-- 하단 고정 버튼 — 누르면 신청 섹션(#apply)으로 이동. 일정이 없으면 '둘러보기' 아웃라인 스타일 --}}
<div class="float-apply {{ $hasSchedule ? '' : 'is-waiting' }}">
    <a href="#apply">{{ $hasSchedule ? $types[$type].' 신청하기' : '둘러보기' }}</a>
</div>
</body>
</html>
