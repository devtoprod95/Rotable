{{--
    참가자 안내 슬라이드 덱 (공유 partial).
    - 관리자용(admin.rotation.guide)과 공개용(public.guide)이 함께 사용한다.
    - 의존성: jQuery, Tabler Icons(ti). 포함하는 쪽에서 반드시 로드되어 있어야 한다.
      (관리자 레이아웃 layouts.app 에는 포함, 공개 페이지는 CDN 으로 직접 로드)

    옵션 변수:
    - $initialType : 'wine' | 'solo' (기본 'wine') — 최초로 보여줄 타입
    - $showToolbar : bool (기본 true) — 상단 타입 토글/전체화면 버튼 표시 여부
--}}
@php
    $initialType = ($initialType ?? 'wine') === 'solo' ? 'solo' : 'wine';
    $showToolbar = $showToolbar ?? true;
@endphp

@if ($showToolbar)
    <div class="guide-toolbar d-flex align-items-center flex-wrap gap-2">
        {{-- 소개팅 타입 토글 (와인파티 / 1:1 소개팅) --}}
        <div class="btn-group" role="group" id="typeToggle">
            <button type="button" class="btn btn-sm {{ $initialType === 'wine' ? 'btn-pink active' : 'btn-outline-pink' }}" data-type="wine">
                <i class="ti ti-glass-cocktail me-1"></i> 와인파티
            </button>
            <button type="button" class="btn btn-sm {{ $initialType === 'solo' ? 'btn-pink active' : 'btn-outline-pink' }}" data-type="solo">
                <i class="ti ti-users-group me-1"></i> 1:1 소개팅
            </button>
        </div>

        <span class="text-muted d-none d-lg-inline" style="font-size:0.85em;">
            (← / → / Space 로 이동)
        </span>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-primary btn-lg px-4" id="btnFullscreen">
                <i class="ti ti-maximize me-2"></i> 전체화면 발표
            </button>
        </div>
    </div>
@endif

{{-- 슬라이드 뷰포트 (내용은 JS 가 타입별로 그려 넣는다) --}}
<div id="slideStage" class="guide-stage" data-initial-type="{{ $initialType }}">
    <div id="slideTrack"></div>

    {{-- 좌/우 네비 (화면 위 오버레이) --}}
    <button type="button" class="guide-nav guide-nav--prev" id="btnPrev" aria-label="이전"><i class="ti ti-chevron-left"></i></button>
    <button type="button" class="guide-nav guide-nav--next" id="btnNext" aria-label="다음"><i class="ti ti-chevron-right"></i></button>

    {{-- 하단 진행 표시 --}}
    <div class="guide-progress">
        <div class="guide-dots" id="guideDots"></div>
        <div class="guide-counter"><span id="slideCur">1</span> / <span id="slideTotal">1</span></div>
    </div>
</div>

<style>
    /* 상단 툴바(타입 토글/전체화면 버튼)와 슬라이드 무대 사이 간격 */
    .guide-toolbar { margin-bottom: clamp(14px, 2vw, 22px); }

    /* 슬라이드 무대: 16:9 비율 유지, 다크 배경 */
    .guide-stage {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: radial-gradient(1200px 600px at 50% -10%, #2b2f55 0%, #15172b 60%, #0f1020 100%);
        color: #f4f5fb;
        overflow: hidden;
        border-radius: var(--tblr-border-radius, .5rem);
    }
    /* 1:1 테마일 때 살짝 다른 배경 톤 */
    .guide-stage[data-theme="solo"] { background: radial-gradient(1200px 600px at 50% -10%, #3a2a4f 0%, #1c1430 60%, #120e22 100%); }

    /* 전체화면 모드에서는 비율 대신 화면 전체 */
    .guide-stage:fullscreen { aspect-ratio: auto; height: 100vh; border-radius: 0; }

    .guide-slide {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        padding: clamp(32px, 6vw, 96px);
        opacity: 0; visibility: hidden;
        transform: translateX(40px);
        transition: opacity .35s ease, transform .35s ease;
    }
    .guide-slide.is-active { opacity: 1; visibility: visible; transform: none; }
    .guide-slide__inner { width: 100%; max-width: 1020px; }

    .guide-badge {
        display: inline-block; padding: 8px 20px; border-radius: 999px;
        font-weight: 700; font-size: clamp(.9rem, 1.6vw, 1.15rem); letter-spacing: .02em;
    }
    .guide-title { font-weight: 800; font-size: clamp(2.2rem, 6.4vw, 4.4rem); margin: .4em 0; line-height: 1.25; }
    .guide-h2 { font-weight: 800; font-size: clamp(1.7rem, 4.4vw, 3rem); margin: 0; line-height: 1.25; }
    .guide-lead { font-size: clamp(1.1rem, 2.6vw, 1.7rem); color: #c7cae6; line-height: 1.85; margin: 0; }
    .guide-hint { color: #aeb2d6; font-size: clamp(1rem, 1.9vw, 1.3rem); }

    /* 제목(배지+h2)과 본문 사이 간격 — 전체화면 기준으로 넉넉하게 */
    .guide-head { margin-bottom: clamp(28px, 5vh, 64px); }
    .guide-head .guide-badge { margin-bottom: clamp(14px, 2.4vh, 28px); }

    /* 흐름(스텝) */
    .guide-flow { display: flex; flex-direction: column; gap: clamp(16px, 2.6vh, 30px); }
    .guide-step { display: flex; align-items: center; gap: 22px; font-size: clamp(1rem, 2.1vw, 1.5rem); }
    .guide-step__no {
        flex: 0 0 auto; width: 1.9em; height: 1.9em; border-radius: 50%;
        background: rgba(255,255,255,.1); color: #fff; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
    }
    .guide-step__ico {
        flex: 0 0 auto; width: 1.6em; text-align: center; font-size: 1.7em; line-height: 1;
    }
    .guide-step .step-body { display: flex; flex-direction: column; justify-content: center; }
    .guide-step .step-desc { color: #aeb2d6; font-size: .78em; margin-top: 6px; line-height: 1.55; }

    /* 상단 안내 띠 (예: 미니게임 '필수 아님' 안내) */
    .guide-note {
        display: flex; align-items: center; gap: 14px;
        background: rgba(13,166,120,.14); border: 1px solid rgba(13,166,120,.35);
        border-radius: 14px; padding: clamp(14px, 2vh, 22px) clamp(18px, 2.4vw, 28px);
        margin-bottom: clamp(20px, 3vh, 34px);
        color: #d7f7ec; font-size: clamp(.95rem, 1.8vw, 1.3rem); line-height: 1.6;
    }
    .guide-note .ti { font-size: 1.5em; flex: 0 0 auto; color: #2fe0b0; }
    .guide-note b { color: #fff; }

    /* 룰/유의 */
    .guide-rules { display: flex; flex-direction: column; gap: clamp(16px, 2.4vh, 26px); }
    .guide-rule {
        display: flex; align-items: center; gap: 20px;
        background: rgba(255,255,255,.06); border-radius: 18px;
        padding: clamp(18px, 2.6vh, 28px) clamp(22px, 2.8vw, 34px);
        font-size: clamp(1rem, 2vw, 1.4rem); line-height: 1.6;
    }
    .guide-rule .rule-ico {
        font-size: 1.7em; flex: 0 0 auto; width: 1.4em; text-align: center;
        font-style: normal; line-height: 1;
    }

    /* 대화 주제 */
    .guide-topics { display: grid; grid-template-columns: repeat(2, 1fr); gap: clamp(18px, 2.4vw, 30px); }
    .guide-topic {
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.08);
        border-radius: 18px; padding: clamp(22px, 3.2vw, 36px);
        font-size: clamp(1.05rem, 2.1vw, 1.45rem); font-weight: 600; line-height: 1.55;
        display: flex; align-items: center;
    }
    /* compact: 문항이 많아 한 슬라이드에 모두 담을 때 (3열·조금 작은 글씨) */
    .guide-topics--compact { grid-template-columns: repeat(3, 1fr); gap: clamp(16px, 2vw, 26px); }
    .guide-topics--compact .guide-topic {
        border-radius: 16px; padding: clamp(18px, 2.4vw, 30px);
        font-size: clamp(.95rem, 1.85vw, 1.3rem); font-weight: 600; line-height: 1.5;
        word-break: keep-all;
    }
    @media (max-width: 760px) {
        .guide-topics, .guide-topics--compact { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .guide-topics, .guide-topics--compact { grid-template-columns: 1fr; }
    }

    /* 네비 버튼 */
    .guide-nav {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: clamp(40px, 5vw, 56px); height: clamp(40px, 5vw, 56px);
        border: none; border-radius: 50%; cursor: pointer;
        background: rgba(255,255,255,.12); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: clamp(1.2rem, 2.6vw, 1.8rem); z-index: 5;
        transition: background .15s ease;
    }
    .guide-nav:hover { background: rgba(255,255,255,.28); }
    .guide-nav--prev { left: clamp(10px, 2vw, 24px); }
    .guide-nav--next { right: clamp(10px, 2vw, 24px); }
    .guide-nav:disabled { opacity: .25; cursor: default; }

    /* 진행 표시 */
    .guide-progress {
        position: absolute; left: 0; right: 0; bottom: clamp(12px, 2.4vw, 26px);
        display: flex; flex-direction: column; align-items: center; gap: 8px; z-index: 5;
    }
    .guide-dots { display: flex; gap: 8px; }
    .guide-dots button {
        width: 10px; height: 10px; border-radius: 50%; border: none; padding: 0; cursor: pointer;
        background: rgba(255,255,255,.3); transition: background .15s ease, width .15s ease;
    }
    .guide-dots button.is-active { background: #fff; width: 26px; border-radius: 6px; }
    .guide-counter { color: #aeb2d6; font-size: .85rem; letter-spacing: .05em; }
</style>

<script>
    $(document).ready(function () {
        // HTML 이스케이프 유틸
        function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

        /* ---------------------------------------------------------------
         | 슬라이드 조각 빌더 — 각 슬라이드를 HTML 문자열로 만든다.
         * ------------------------------------------------------------- */
        function slideCover(badge, badgeCls, title, lead) {
            return `<div class="guide-slide__inner text-center">
                        <div class="guide-badge ${badgeCls} mb-4">${esc(badge)}</div>
                        <h1 class="guide-title">${title}</h1>
                        <p class="guide-lead">${lead}</p>
                    </div>`;
        }
        function slideFlow(badge, badgeCls, h2, steps) {
            var rows = steps.map(function (s, i) {
                return `<div class="guide-step">
                            <span class="guide-step__no">${i + 1}</span>
                            <span class="guide-step__ico">${s.icon}</span>
                            <div class="step-body"><div class="fw-bold">${esc(s.title)}</div>
                                 <div class="step-desc">${esc(s.desc)}</div></div>
                        </div>`;
            }).join('');
            return `<div class="guide-slide__inner">
                        <div class="guide-head">
                            <div class="guide-badge ${badgeCls}">${esc(badge)}</div>
                            <h2 class="guide-h2">${esc(h2)}</h2>
                        </div>
                        <div class="guide-flow">${rows}</div>
                    </div>`;
        }
        function slideRules(badge, badgeCls, h2, rules) {
            var rows = rules.map(function (r) {
                return `<div class="guide-rule"><span class="rule-ico">${r.ico}</span><div>${r.html}</div></div>`;
            }).join('');
            return `<div class="guide-slide__inner">
                        <div class="guide-head">
                            <div class="guide-badge ${badgeCls}">${esc(badge)}</div>
                            <h2 class="guide-h2">${esc(h2)}</h2>
                        </div>
                        <div class="guide-rules">${rows}</div>
                    </div>`;
        }
        function slideTopics(badge, badgeCls, h2, topics, hint, compact) {
            // compact: 문항이 많을 때 3열·작은 글씨로 한 슬라이드에 모두 담는다.
            //          (hint 는 5번째 인자, compact 는 6번째. hint 없이 compact만 줄 땐 hint=true 전달 가능)
            if (hint === true) { compact = true; hint = null; }
            var topicsCls = compact ? 'guide-topics guide-topics--compact' : 'guide-topics';
            var cells = topics.map(function (t) { return `<div class="guide-topic">${esc(t)}</div>`; }).join('');
            return `<div class="guide-slide__inner">
                        <div class="guide-head">
                            <div class="guide-badge ${badgeCls}">${esc(badge)}</div>
                            <h2 class="guide-h2">${esc(h2)}</h2>
                        </div>
                        <div class="${topicsCls}">${cells}</div>
                        ${hint ? `<div class="guide-hint mt-4"><i class="ti ti-bulb me-1"></i> ${esc(hint)}</div>` : ''}
                    </div>`;
        }

        /* ---------------------------------------------------------------
         | 공통 대화 주제 슬라이드 (두 타입 공통)
         |  - 아이스브레이킹 / 한 걸음 더 각각 "한 슬라이드"에 모두 담는다.
         |    문항이 많으므로 compact(3열·작은 글씨) 레이아웃을 쓴다.
         |  - 한 걸음 더: 조금 더 야릇하고 재밌는 질문 → "약속(룰)" 다음 배치
         * ------------------------------------------------------------- */
        var topicLight = slideTopics('대화 주제 · 아이스브레이킹', 'bg-purple-lt text-purple', '처음 뵙는데, 가볍게 인사부터 😊', [
            '오늘 뭐하다 오셨어요?',
            '여기 올 때 뭐 타고 오셨어요, 운전? 지하철?',
            '오늘 뭐 먹었어요?',
            'MBTI는 뭐예요?',
            '어디에 사세요?',
            '이 근처 자주 와보셨어요?',
            '회사는 이 근처세요?',
            '오늘 의상은 어떤 컨셉이세요?',
            '전공이 뭐예요?',
        ], true);

        var topicDeep = slideTopics('대화 주제 · 한 걸음 더', 'bg-pink-lt text-pink', '살짝 두근, 솔직하게 😏', [
            '이성볼 때 가장 먼저 보는 포인트는?',
            '얼굴 vs 몸매 어떤게 더 끌려요?',
            '썸 탈 땐 밀당파 vs 직진파? 어떤 편이세요?',
            '카톡 vs 전화, 어떤 걸 더 선호하세요?',
            '외적, 내적 이상형이 어떻게 되세요?',
            '최장, 최단 기간 연애 경험은?',
            '데이트 코스, 실내 데이트 vs 실외 데이트?',
            '맘에드는 이성한테는 어떻게 플러팅하세요?',
            '현재 마음에 두고 있는 이성이 있나요?',
            '썸의 진도는 어느 정도까지 허용되나요?',
            '최근 마지막 연애가 언제였나요? 또 얼마나 만났나요?',
            '내가 매력적으로 느끼는 이성의 신체부위는?',
        ], true);

        var outro = slideCover('마무리', 'bg-pink-lt text-pink',
            '오늘 로테이블과 함께해<br>주셔서 고마워요 💕',
            '용기 내어 이 자리에 와주신 당신, 그 자체로 멋져요.<br>좋은 사람과 좋은 추억, 가득 안고 돌아가시길 진심으로 응원할게요 🤍');

        // 오픈 기념 할인 안내 (두 타입 공통) — 현장 참가자 대상, 2차 전 모집 플랫폼에 후기 부탁
        var openEvent = slideRules('🎉 오픈 기념 할인 행사', 'bg-orange-lt text-orange', '좋은 후기 한 줄 부탁드려요 🙏', [
            { ico: '🔥', html: '오늘 자리는 <b>오픈 기념 할인 행사</b>로 모셔진 자리예요. 함께해 주셔서 고마워요!' },
            { ico: '✍️', html: '<b>1차 종료 후</b>에, 모집된 <b style="background:linear-gradient(transparent 55%, rgba(251,146,60,.6) 55%); padding:0 4px;">플랫폼(문토, 프립..)에 후기</b>를 한 줄 남겨 주세요.' },
            { ico: '🧡', html: '오늘 느끼신 <b>좋은 점</b>을 솔직하게 적어 주시면 큰 힘이 돼요. 좋은 후기 부탁드려요!' },
        ]);

        // 미니게임 안내 (와인파티 전용) — 로테이션(진행중)에 하단 '게임' 탭에서 즐길 수 있다.
        // 미니게임 안내 (와인파티 전용) — 상단에 "필수 아님 / 1차 전용" 안내 띠를 두고
        // 그 아래 게임 목록(스텝)을 보여준다. slideFlow 빌더엔 상단 안내 자리가 없어 직접 조립.
        var wineGamesSteps = [
            { icon: '🙋', title: 'Yes or No', desc: '같은 테이블끼리 익명으로 예/아니오 투표! 결과가 실시간으로 집계돼요. (옆사람 화면은 가려주세요 🙈)' },
            { icon: '🍯', title: '순한맛 이미지게임', desc: '"~한 사람?" 질문에 어울리는 이성을 다같이 손가락으로 지목! 가볍게 첫인상 몸풀기.' },
            { icon: '🌶️', title: '매운맛 이미지게임', desc: '조금 더 아찔하고 솔직한 질문으로 분위기를 확 달구는 지목 게임.' },
            { icon: '⚖️', title: '밸런스 게임', desc: 'A vs B 둘 중 끌리는 쪽을 엄지 업/다운으로! 서로의 연애관과 취향을 알아봐요.' },
        ];
        var wineGamesRows = wineGamesSteps.map(function (s, i) {
            return `<div class="guide-step">
                        <span class="guide-step__no">${i + 1}</span>
                        <span class="guide-step__ico">${s.icon}</span>
                        <div class="step-body"><div class="fw-bold">${esc(s.title)}</div>
                             <div class="step-desc">${esc(s.desc)}</div></div>
                    </div>`;
        }).join('');
        var wineGames = `<div class="guide-slide__inner">
                <div class="guide-head">
                    <div class="guide-badge bg-teal-lt text-teal">🎮 틈틈이 미니게임</div>
                    <h2 class="guide-h2">대화가 어색할 땐, 게임으로 분위기 UP!</h2>
                </div>
                <div class="guide-note">
                    <i class="ti ti-info-circle"></i>
                    <span>미니게임은 <b>필수가 아니에요.</b> 대화가 어색할 때 <b>테이블끼리 자유롭게</b> 즐기면 돼요.
                    다만 <b>2차는 마음에 드는 분과 더 깊이 대화하는 자리</b>라, 대화에 집중하실 수 있도록 게임은 열지 않아요. 양해 부탁드려요 🙏</span>
                </div>
                <div class="guide-flow">${wineGamesRows}</div>
            </div>`;

        /* ---------------------------------------------------------------
         | 타입별 슬라이드 세트
         |  - 상세페이지 샘플(_detailSample)의 이용안내 스텝/포인트/유의사항 참고
         * ------------------------------------------------------------- */
        var SLIDES = {
            // ===== 와인파티 =====
            // 요청: 아이스브레이킹을 맨 앞에, "한 걸음 더" 질문은 약속(룰) 다음에 배치.
            wine: [
                topicLight,

                slideCover('ROTABLE · 로테이블', 'bg-pink-lt text-pink',
                    '로테이블에 오신 걸 환영합니다 🍷',
                    '좋은 인연은 서두르지 않아도, 반드시 찾아와요.<br>오늘 이 자리에서 당신의 소중한 인연이 시작되길, 진심으로 응원할게요 🤍'),

                slideFlow('1. 서비스 이용안내', 'bg-blue-lt text-blue', '오늘은 이렇게 흘러가요', [
                    { icon: '🎫', title: '입장 & 닉네임', desc: '도착하시면 나만의 닉네임을 받고, 안내해 드리는 테이블에 편하게 앉으시면 돼요.' },
                    { icon: '📝', title: '입장등록 (프로필 작성)', desc: '폰으로 닉네임·연락처·4자리 비밀번호와 나이·직업·키·MBTI·지역, 그리고 장점·이상형·취미 같은 소개를 간단히 적어 주세요. 상대에게 보이는 내 프로필이 돼요!' },
                    { icon: '🔄', title: '로테이션 대화', desc: '테이블(3:3·4:4 등)에서 도란도란 대화하다가, 시간이 되면 다음 자리로 옮겨요.' },
                    { icon: '💌', title: '메시지 & 비밀 투표', desc: '대화 중 살짝 메시지도 보내고, 마음에 드는 분을 조용히 골라두세요.' },
                    { icon: '🍷', title: '자유 대화 (2차)', desc: '마음에 드는 이성 분과 자유롭게 앉아 더 이야기 나눠요.' },
                    { icon: '💘', title: '매칭 발표', desc: '서로 마음이 통했다면 성사! 결과는 다음 날 오전에 살며시 알려드려요.' },
                ]),

                slideRules('2. 함께 지켜요 · 약속', 'bg-red-lt text-red', '모두가 편한 자리가 되도록 🙂', [
                    { ico: '🤝', html: '<b>서로 존중해요</b> — 따뜻한 말과 배려가 가장 좋은 매력이에요.' },
                    { ico: '⏱️', html: '<b>시간은 지켜주세요</b> — 이동 안내가 나오면 가볍게 다음 자리로 옮겨 주세요.' },
                    { ico: '🙋', html: "<b>불편하면 언제든</b> — 운영진에게 '요청하기'로 살짝 알려주세요. 바로 도와드릴게요!" },
                    { ico: '🍊', html: '<b>와인이 어려우시면</b> — 술이 부담되시는 분을 위해 <b>자몽주스</b>도 준비되어 있어요. 편하게 즐기세요!' },
                    { ico: '🍷', html: '<b>과음은 금물이에요</b> — 취해서 큰 소리를 내거나 난동을 부리시면 즉시 퇴장 조치될 수 있어요.' },
                    { ico: '⚠️', html: '<b>기물 파손은 배상 책임</b> — 시설·집기를 파손하신 경우 배상 책임이 발생할 수 있으니 주의해 주세요.' },
                ]),

                wineGames,

                openEvent,

                topicDeep,

                outro,
            ],

            // ===== 1:1 소개팅 =====
            solo: [
                slideCover('ROTABLE · 로테이블', 'bg-pink-lt text-pink',
                    '로테이블에 오신 걸 환영합니다 💗',
                    '한 사람 한 사람, 마음을 다해 마주해 보세요.<br>오늘의 만남이 당신의 소중한 인연으로 이어지길, 진심으로 응원할게요 🤍'),

                slideFlow('1. 서비스 이용안내', 'bg-blue-lt text-blue', '오늘은 이렇게 흘러가요', [
                    { icon: '🎫', title: '입장 & 닉네임', desc: '도착하시면 나만의 닉네임을 받고, 안내해 드리는 자리에 편하게 앉으시면 돼요.' },
                    { icon: '📝', title: '입장등록 (프로필 작성)', desc: '스마트폰으로 닉네임·연락처·4자리 비밀번호와 나이·직업·키·MBTI·지역, 그리고 장점·이상형·취미 같은 소개를 간단히 적어 주세요. 상대에게 보이는 내 프로필이 돼요!' },
                    { icon: '☕', title: '1:1 로테이션 대화', desc: '한 분씩 마주 앉아 도란도란 대화하다가, 시간이 되면 다음 자리로 옮겨요.' },
                    { icon: '🤫', title: '대화 중 비밀 투표', desc: '대화를 나누면서 마음에 드는 분을 스마트폰으로 조용히·비공개로 저장해 두세요. 마감 전까지 언제든 바꿀 수 있어요.' },
                    { icon: '🚪', title: '순서대로 퇴장', desc: '모든 대화가 끝나면 여성분들이 먼저, 이어서 남성분들이 자리를 정리하고 퇴장해요.' },
                    { icon: '💘', title: '5분 내 매칭 발표 & 연락', desc: '남성분 퇴장 후 5분 이내에 매칭 결과를 발표해요. 매칭된 분의 정보를 확인하고, 편하게 연락 이어가시면 돼요!' },
                ]),

                slideRules('2. 함께 지켜요 · 약속', 'bg-red-lt text-red', '모두가 편한 자리가 되도록 🙂', [
                    { ico: '🤝', html: '<b>서로 존중해요</b> — 따뜻한 말과 배려가 가장 좋은 매력이에요.' },
                    { ico: '⏱️', html: '<b>시간은 지켜주세요</b> — 1:1 대화 종료 안내가 나오면 가볍게 다음 자리로 옮겨 주세요.' },
                    { ico: '🤫', html: '<b>투표는 비밀이에요</b> — 누굴 골랐는지 서로 묻지 않기로 해요.' },
                    { ico: '🚻', html: '<b>화장실은 이렇게</b> — 가급적 쉬는 시간에 다녀오시고, 대화 중이라면 상대에게 가볍게 양해를 구하고 다녀오세요.' },
                    { ico: '🍷', html: '<b>과음은 금물이에요</b> — 취해서 큰 소리를 내거나 난동을 부리시면 즉시 퇴장 조치될 수 있어요.' },
                    { ico: '⚠️', html: '<b>기물 파손은 배상 책임</b> — 시설·집기를 파손하신 경우 배상 책임이 발생할 수 있으니 주의해 주세요.' },
                ]),

                openEvent,

                // 1:1 전용 유의사항 — 선택한 상대에게 매칭 안 돼도 연락처가 공개될 수 있음
                slideRules('⚠️ 매칭 선택 전, 이것만요!', 'bg-red-lt text-red', '선택은 조금 신중하게 🙏', [
                    { ico: '3️⃣', html: '마음에 드는 분을 <b>최대 3명까지</b> 선택할 수 있어요.' },
                    { ico: '📣', html: '내가 고른 분께는 <b>서로 매칭이 안 되더라도 내 연락처가 전해질 수 있어요.</b>' },
                    { ico: '💡', html: '그러니 정말 마음에 드는 분을 <b>신중하게</b> 골라 주세요!' },
                ]),

                outro,
            ],
        };

        /* ---------------------------------------------------------------
         | 렌더링 & 네비게이션
         * ------------------------------------------------------------- */
        var $stage = $('#slideStage');
        var $track = $('#slideTrack');
        var $dots  = $('#guideDots');
        var current = 0;
        var slides = [];
        var currentType = ($stage.data('initial-type') === 'solo') ? 'solo' : 'wine';

        function buildType(type) {
            currentType = (type === 'solo') ? 'solo' : 'wine';
            slides = SLIDES[currentType];
            current = 0;

            $stage.attr('data-theme', currentType);

            // 슬라이드 DOM 생성
            $track.html(slides.map(function (html, i) {
                return `<section class="guide-slide" data-slide="${i}">${html}</section>`;
            }).join(''));

            // 점(dot) 네비 생성
            $dots.html('');
            for (var i = 0; i < slides.length; i++) {
                $dots.append('<button type="button" data-idx="' + i + '" aria-label="슬라이드 ' + (i + 1) + '"></button>');
            }
            $('#slideTotal').text(slides.length);
            render();
        }

        function render() {
            var $s = $track.find('.guide-slide');
            $s.removeClass('is-active').eq(current).addClass('is-active');
            $dots.children().removeClass('is-active').eq(current).addClass('is-active');
            $('#slideCur').text(current + 1);
            $('#btnPrev').prop('disabled', current === 0);
            $('#btnNext').prop('disabled', current === slides.length - 1);
        }
        function go(idx) { current = Math.max(0, Math.min(slides.length - 1, idx)); render(); }
        function next() { if (current < slides.length - 1) { go(current + 1); } }
        function prev() { if (current > 0) { go(current - 1); } }

        $('#btnNext').on('click', next);
        $('#btnPrev').on('click', prev);
        $dots.on('click', 'button', function () { go(parseInt($(this).data('idx'), 10)); });

        // 타입 토글 (툴바가 있을 때만 존재)
        $('#typeToggle').on('click', 'button', function () {
            var type = $(this).data('type');
            if (type === currentType) { return; }
            $('#typeToggle button').removeClass('active btn-pink').addClass('btn-outline-pink');
            $(this).addClass('active btn-pink').removeClass('btn-outline-pink');
            buildType(type);
        });

        // 키보드: ← / → / Space / Home / End / F5(전체화면 발표)
        $(document).on('keydown.guide', function (e) {
            if (e.key === 'F5') { e.preventDefault(); toggleFullscreen(); }
            else if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') { e.preventDefault(); next(); }
            else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); prev(); }
            else if (e.key === 'Home') { e.preventDefault(); go(0); }
            else if (e.key === 'End') { e.preventDefault(); go(slides.length - 1); }
        });

        // 무대 영역 클릭: 왼쪽 절반=이전, 오른쪽 절반=다음 (네비/점 버튼 제외)
        $stage.on('click', function (e) {
            if ($(e.target).closest('.guide-nav, .guide-dots').length) { return; }
            var offsetX = e.pageX - $stage.offset().left;
            if (offsetX < $stage.width() / 2) { prev(); } else { next(); }
        });

        // 전체화면 발표 (버튼 / F5 공용)
        function toggleFullscreen() {
            var el = $stage.get(0);
            if (!document.fullscreenElement) {
                (el.requestFullscreen || el.webkitRequestFullscreen || function () {}).call(el);
            } else {
                (document.exitFullscreen || document.webkitExitFullscreen || function () {}).call(document);
            }
        }
        $('#btnFullscreen').on('click', toggleFullscreen);
        document.addEventListener('fullscreenchange', function () {
            var on = !!document.fullscreenElement;
            $('#btnFullscreen').html(on
                ? '<i class="ti ti-minimize me-1"></i> 전체화면 종료'
                : '<i class="ti ti-maximize me-1"></i> 전체화면 발표');
        });

        // 최초 렌더
        buildType(currentType);
    });
</script>
