@extends('layouts.app')

@section('title', '참가자 안내')

@section('content')
{{--
    참가자 안내 슬라이드 (PPT 형태).
    - 행사장 스크린/빔프로젝터에 띄워 참가자에게 안내한다.
    - 소개팅 안내는 [와인파티 / 1:1 소개팅] 두 타입으로 나뉜다. (상단 토글로 전환)
      → 타입별 문구는 상세페이지 샘플(_detailSample)의 이용안내/포인트/유의사항을 참고.
    - 대화 주제(아이스브레이킹)는 두 타입 공통.
    - 좌/우 버튼·키보드(←/→/Space)·화면 터치(좌우 절반)로 슬라이드 이동.
    - "전체화면 발표" 버튼으로 몰입형(Fullscreen) 발표 모드 전환.

    슬라이드 구성은 JS 의 SLIDES[type] 배열로 정의하고, 타입을 바꾸면 다시 렌더한다.
--}}

<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-presentation me-1"></i> 참가자 안내 슬라이드</h3>

                {{-- 소개팅 타입 토글 (와인파티 / 1:1 소개팅) --}}
                <div class="btn-group ms-2" role="group" id="typeToggle">
                    <button type="button" class="btn btn-sm btn-pink active" data-type="wine">
                        <i class="ti ti-glass-cocktail me-1"></i> 와인파티
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-pink" data-type="solo">
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

            <div class="card-body p-0">
                {{-- 슬라이드 뷰포트 (내용은 JS 가 타입별로 그려 넣는다) --}}
                <div id="slideStage" class="guide-stage">
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
            </div>
        </div>
    </div>
</div>

<style>
    /* 슬라이드 무대: 16:9 비율 유지, 다크 배경 */
    .guide-stage {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        background: radial-gradient(1200px 600px at 50% -10%, #2b2f55 0%, #15172b 60%, #0f1020 100%);
        color: #f4f5fb;
        overflow: hidden;
        border-bottom-left-radius: var(--tblr-border-radius, .5rem);
        border-bottom-right-radius: var(--tblr-border-radius, .5rem);
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
                    '오늘 하루, 마음 편히 즐기다 가세요.<br>좋은 사람과의 만남, 저희가 응원할게요!'),

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
                    { ico: '🤫', html: '<b>투표는 비밀이에요</b> — 누굴 골랐는지 서로 묻지 않기! 매칭된 분께만 살짝 전해져요.' },
                    { ico: '⏱️', html: '<b>시간은 지켜주세요</b> — 이동 안내가 나오면 가볍게 다음 자리로 옮겨 주세요.' },
                    { ico: '📱', html: '<b>연락처는 천천히</b> — 개인 연락처는 매칭 발표(다음 날 오전) 후에 주고받아요.' },
                    { ico: '🙋', html: "<b>불편하면 언제든</b> — 운영진에게 '요청하기'로 살짝 알려주세요. 바로 도와드릴게요!" },
                ]),

                topicDeep,

                outro,
            ],

            // ===== 1:1 소개팅 =====
            solo: [
                slideCover('ROTABLE · 로테이블', 'bg-pink-lt text-pink',
                    '로테이블에 오신 걸 환영합니다 💗',
                    '오늘 하루, 마음 편히 즐기다 가세요.<br>좋은 사람과의 만남, 저희가 응원할게요!'),

                topicLight,

                slideFlow('1. 서비스 이용안내', 'bg-blue-lt text-blue', '오늘은 이렇게 흘러가요', [
                    { icon: '🎫', title: '입장 & 닉네임', desc: '도착하시면 나만의 닉네임을 받고, 안내해 드리는 자리에 편하게 앉으시면 돼요.' },
                    { icon: '📝', title: '입장등록 (프로필 작성)', desc: '폰으로 닉네임·연락처·4자리 비밀번호와 나이·직업·키·MBTI·지역, 그리고 장점·이상형·취미 같은 소개를 간단히 적어 주세요. 상대에게 보이는 내 프로필이 돼요!' },
                    { icon: '☕', title: '1:1 로테이션 대화', desc: '한 분씩 마주 앉아 도란도란 대화하다가, 시간이 되면 다음 자리로 옮겨요.' },
                    { icon: '🤫', title: '스마트폰 비밀 투표', desc: '마음에 드는 분을 조용히·비공개로 골라두시면 끝이에요. 아주 간단하죠?' },
                    { icon: '💘', title: '바로 매칭 발표', desc: '끝나면 그 자리에서 결과 확인! 다음 날까지 두근두근 기다릴 필요 없어요.' },
                    { icon: '💌', title: '상대 정보 & 연락', desc: '골라 주신 분의 정보를 폰으로 확인하고, 편하게 연락 이어가시면 돼요.' },
                ]),

                slideRules('2. 함께 지켜요 · 약속', 'bg-red-lt text-red', '모두가 편한 자리가 되도록 🙂', [
                    { ico: '🤝', html: '<b>서로 존중해요</b> — 따뜻한 말과 배려가 가장 좋은 매력이에요.' },
                    { ico: '⏱️', html: '<b>시간은 지켜주세요</b> — 1:1 대화 종료 안내가 나오면 가볍게 다음 자리로 옮겨 주세요.' },
                    { ico: '🤫', html: '<b>투표는 비밀이에요</b> — 누굴 골랐는지 서로 묻지 않기로 해요.' },
                    { ico: '🙋', html: "<b>불편하면 언제든</b> — 운영진에게 '요청하기'로 살짝 알려주세요. 바로 도와드릴게요!" },
                ]),

                // 1:1 전용 유의사항 — 선택한 상대에게 매칭 안 돼도 연락처가 공개될 수 있음
                slideRules('⚠️ 매칭 선택 전, 이것만요!', 'bg-red-lt text-red', '선택은 조금 신중하게 🙏', [
                    { ico: '📣', html: '내가 고른 분께는 <b>서로 매칭이 안 되더라도 내 연락처가 전해질 수 있어요.</b>' },
                    { ico: '💡', html: '그러니 정말 마음에 드는 분을 <b>신중하게</b> 골라 주세요!' },
                ]),

                topicDeep,

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
        var currentType = 'wine';

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

        // 타입 토글
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

        // 최초: 와인파티
        buildType('wine');
    });
</script>
@endsection
