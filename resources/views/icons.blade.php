@extends('layouts.app')

@section('title', '아이콘 목록')

@section('content')
@php
    // [클래스, 한글 이름] 쌍으로 구성
    $iconGroups = [
        '핵심 작업 (CRUD)' => [
            ['ti ti-plus', '추가'], ['ti ti-trash', '삭제'], ['ti ti-edit', '수정'], ['ti ti-check', '확인'],
            ['ti ti-x', '취소'], ['ti ti-refresh', '새로고침'], ['ti ti-search', '검색'], ['ti ti-filter', '필터'],
            ['ti ti-download', '다운로드'], ['ti ti-upload', '업로드'], ['ti ti-copy', '복사'], ['ti ti-eye', '보기'],
            ['ti ti-eye-off', '숨기기'], ['ti ti-printer', '인쇄'], ['ti ti-file-export', '내보내기'], ['ti ti-file-import', '가져오기'],
            ['ti ti-send', '전송'], ['ti ti-rotate-clockwise', '회전'], ['ti ti-list', '목록'],
            ['ti ti-square-plus', '항목 추가'], ['ti ti-square-x', '닫기'], ['ti ti-pencil', '편집'], ['ti ti-circle-plus', '항목 추가(원형)'],
            ['ti ti-circle-check', '완료 표시'], ['ti ti-circle-x', '오류 표시'], ['ti ti-restore', '복원'], ['ti ti-arrows-sort', '정렬'],
            ['ti ti-sort-ascending', '오름차순'], ['ti ti-sort-descending', '내림차순'], ['ti ti-checklist', '체크리스트'], ['ti ti-clipboard-check', '승인 처리'],
        ],
        '이커머스' => [
            ['ti ti-shopping-cart', '장바구니'], ['ti ti-shopping-cart-plus', '장바구니 추가'], ['ti ti-shopping-bag', '쇼핑백'],
            ['ti ti-package', '상품 패키지'], ['ti ti-barcode', '바코드'], ['ti ti-qrcode', 'QR코드'],
            ['ti ti-tag', '상품 태그/할인'], ['ti ti-tags', '카테고리/태그'], ['ti ti-receipt', '영수증/주문내역'],
            ['ti ti-credit-card', '결제'], ['ti ti-wallet', '지갑/결제수단'], ['ti ti-truck', '배송/운송'],
            ['ti ti-truck-delivery', '배송 중'], ['ti ti-package-export', '출고'], ['ti ti-package-import', '입고'],
            ['ti ti-box', '재고/상품'], ['ti ti-building-store', '매장/스토어'], ['ti ti-discount-2', '할인'],
            ['ti ti-gift', '선물/이벤트'], ['ti ti-coin', '포인트/적립금'], ['ti ti-currency-won', '결제 금액'],
            ['ti ti-clipboard-list', '주문 목록'], ['ti ti-checklist', '검수/확인'], ['ti ti-zoom-money', '가격 조회'],
            ['ti ti-shopping-cart-x', '주문 취소'], ['ti ti-truck-return', '반품/교환'], ['ti ti-shield-check', '구매 안전/보증'],
        ],
        '보안 및 인프라' => [
            ['ti ti-lock-access', '접근 제어'], ['ti ti-shield-check', '인증 상태'], ['ti ti-fingerprint', '지문/생체 인증'],
            ['ti ti-alert-triangle', '보안 경고'], ['ti ti-spy', '로그 감사'], ['ti ti-network', '네트워크 제어'],
            ['ti ti-database-cog', 'DB 최적화'], ['ti ti-shield-lock', '보안 설정'], ['ti ti-user-exclamation', '계정 잠금'],
            ['ti ti-key-off', '권한 회수'], ['ti ti-password-user', '비밀번호 정책'], ['ti ti-api', 'API 연결'],
        ],
        '마케팅 및 프로모션' => [
            ['ti ti-speakerphone', '광고 캠페인'], ['ti ti-discount', '쿠폰 발행'], ['ti ti-mail-forward', '이메일 발송'],
            ['ti ti-device-mobile-message', '푸시 알림'], ['ti ti-ad', '광고 관리'], ['ti ti-seo', 'SEO 설정'],
            ['ti ti-brand-google', '검색 엔진 등록'], ['ti ti-layout-navbar', '배너 관리'], ['ti ti-gift-card', '기프트카드'],
            ['ti ti-chart-dots-3', '성과 측정'], ['ti ti-users-group', '타겟 그룹'], ['ti ti-message-chatbot', '챗봇/CS 자동화'],
        ],
        '고객 지원 (CS)' => [
            ['ti ti-headset', '고객 상담'], ['ti ti-help-triangle', '자주 묻는 질문(FAQ)'], ['ti ti-message-report', '문의 답변'],
            ['ti ti-thumb-up', '만족도 조사'], ['ti ti-notes', '상담 메모'], ['ti ti-user-question', '고객 문의'],
            ['ti ti-clock-exclamation', '처리 지연'], ['ti ti-file-check', '처리 완료'], ['ti ti-message-circle-off', '상담 종료'],
            ['ti ti-mailbox', '보관함'], ['ti ti-clipboard-text', 'CS 매뉴얼'], ['ti ti-mood-smile', '고객 피드백'],
        ],
        '사용자 및 관리' => [
            ['ti ti-user', '사용자'], ['ti ti-users', '사용자 그룹'], ['ti ti-user-circle', '프로필'], ['ti ti-user-plus', '사용자 추가'],
            ['ti ti-user-minus', '사용자 제거'], ['ti ti-user-off', '비활성 사용자'], ['ti ti-id', '신분증'], ['ti ti-lock', '잠금'],
            ['ti ti-shield', '보안'], ['ti ti-shield-check', '보안 인증'], ['ti ti-shield-x', '보안 차단'],
            ['ti ti-password', '비밀번호'], ['ti ti-key', '키/권한'], ['ti ti-fingerprint', '지문 인증'], ['ti ti-login', '로그인'],
            ['ti ti-logout', '로그아웃'], ['ti ti-badge', '배지/등급'], ['ti ti-award', '수상'], ['ti ti-ghost', '익명'],
            ['ti ti-user-check', '승인된 사용자'], ['ti ti-user-cog', '사용자 설정'], ['ti ti-users-group', '조직/팀'],
            ['ti ti-crown', '관리자/등급'], ['ti ti-settings-bolt', '권한 설정'],
        ],
        '시스템 및 설정' => [
            ['ti ti-settings', '설정'], ['ti ti-adjustments', '환경설정'], ['ti ti-tool', '도구'], ['ti ti-tools', '도구 모음'],
            ['ti ti-dashboard', '대시보드'], ['ti ti-layout', '레이아웃'], ['ti ti-grid-dots', '그리드'], ['ti ti-apps', '앱 목록'],
            ['ti ti-database', '데이터베이스'], ['ti ti-server', '서버'], ['ti ti-cloud', '클라우드'], ['ti ti-wifi', '와이파이'],
            ['ti ti-battery', '배터리'], ['ti ti-power', '전원'], ['ti ti-terminal', '터미널'], ['ti ti-code', '코드'],
            ['ti ti-brand-github', '깃허브'], ['ti ti-bug', '버그/오류'], ['ti ti-device-desktop', '데스크톱'], ['ti ti-device-mobile', '모바일'],
            ['ti ti-plug', '연동/플러그인'], ['ti ti-cpu', '프로세서'], ['ti ti-history', '이력'], ['ti ti-refresh-alert', '동기화 오류'],
            ['ti ti-cloud-upload', '클라우드 업로드'], ['ti ti-cloud-download', '클라우드 다운로드'], ['ti ti-loader-2', '로딩'], ['ti ti-toggle-left', '토글 끄기'],
            ['ti ti-toggle-right', '토글 켜기'],
        ],
        '콘텐츠 및 미디어' => [
            ['ti ti-file', '파일'], ['ti ti-folder', '폴더'], ['ti ti-file-text', '문서'], ['ti ti-file-zip', '압축 파일'],
            ['ti ti-file-code', '코드 파일'], ['ti ti-clipboard', '클립보드'], ['ti ti-note', '메모'],
            ['ti ti-bookmark', '북마크'], ['ti ti-tags', '태그'], ['ti ti-photo', '사진'], ['ti ti-camera', '카메라'],
            ['ti ti-video', '동영상'], ['ti ti-music', '음악'], ['ti ti-microphone', '마이크'], ['ti ti-brush', '브러시'],
            ['ti ti-palette', '색상 팔레트'], ['ti ti-color-swatch', '색상 견본'], ['ti ti-typography', '타이포그래피'], ['ti ti-text-wrap', '줄바꿈'],
            ['ti ti-file-spreadsheet', '엑셀 파일'], ['ti ti-folder-plus', '폴더 추가'], ['ti ti-paperclip', '첨부파일'],
            ['ti ti-link', '링크'], ['ti ti-photo-plus', '이미지 추가'], ['ti ti-zoom-in', '확대'], ['ti ti-zoom-out', '축소'],
        ],
        '통계 및 리포트' => [
            ['ti ti-chart-bar', '막대 그래프'], ['ti ti-chart-pie', '원형 그래프'], ['ti ti-chart-line', '선 그래프'], ['ti ti-chart-area', '영역 그래프'],
            ['ti ti-trending-up', '상승 추세'], ['ti ti-trending-down', '하락 추세'], ['ti ti-table', '표'], ['ti ti-report', '리포트'],
            ['ti ti-receipt', '영수증'], ['ti ti-wallet', '지갑'], ['ti ti-currency-dollar', '달러'],
            ['ti ti-currency-won', '원화'], ['ti ti-gift', '선물/이벤트'], ['ti ti-bell', '알림'], ['ti ti-calendar', '달력'],
            ['ti ti-clock', '시간'], ['ti ti-alarm', '알람'], ['ti ti-hourglass', '대기중'], ['ti ti-calendar-event', '일정'],
            ['ti ti-percentage', '퍼센트/할인'], ['ti ti-coin', '포인트/적립'], ['ti ti-cash', '현금'], ['ti ti-credit-card', '카드 결제'],
            ['ti ti-bell-ringing', '새 알림'], ['ti ti-calendar-due', '마감일'], ['ti ti-target-arrow', '목표'], ['ti ti-flag-3', '깃발/마일스톤'],
        ],
        '커뮤니케이션' => [
            ['ti ti-mail', '메일'], ['ti ti-mail-opened', '읽은 메일'], ['ti ti-message', '메시지'], ['ti ti-messages', '대화'],
            ['ti ti-message-circle', '말풍선'], ['ti ti-brand-whatsapp', '왓츠앱'], ['ti ti-phone', '전화'], ['ti ti-brand-telegram', '텔레그램'],
            ['ti ti-at', '이메일 기호'], ['ti ti-speakerphone', '공지'], ['ti ti-share', '공유'], ['ti ti-news', '뉴스'],
            ['ti ti-article', '게시글'], ['ti ti-question-mark', '도움말'], ['ti ti-info-circle', '안내'], ['ti ti-alert-triangle', '경고'],
            ['ti ti-thumb-up', '좋아요'], ['ti ti-thumb-down', '싫어요'], ['ti ti-heart', '즐겨찾기'], ['ti ti-star', '별점'],
            ['ti ti-alert-circle', '오류 안내'], ['ti ti-help-circle', '문의'], ['ti ti-message-2', '댓글'], ['ti ti-bell-x', '알림 끄기'],
        ],
        '위치 및 이동' => [
            ['ti ti-map-pin', '위치'], ['ti ti-map', '지도'], ['ti ti-compass', '나침반'], ['ti ti-world', '전세계'],
            ['ti ti-bus', '버스'], ['ti ti-car', '자동차'], ['ti ti-bike', '자전거'], ['ti ti-plane', '항공편'],
            ['ti ti-ship', '선박'], ['ti ti-anchor', '정박/고정'], ['ti ti-location', '현재 위치'], ['ti ti-road', '도로'],
            ['ti ti-building', '건물'], ['ti ti-home', '홈'], ['ti ti-home-2', '홈(대체)'], ['ti ti-building-bank', '은행'],
            ['ti ti-briefcase', '업무'], ['ti ti-tent', '캠핑'], ['ti ti-building-warehouse', '창고/물류'], ['ti ti-flag', '국가/깃발'],
            ['ti ti-building-store', '매장'], ['ti ti-building-hospital', '병원'], ['ti ti-truck-delivery', '배송'], ['ti ti-route', '경로'],
        ],
        '기타 아이콘' => [
            ['ti ti-sun', '낮/밝게'], ['ti ti-moon', '밤/어둡게'], ['ti ti-diamond', '다이아몬드'], ['ti ti-puzzle', '퍼즐/플러그인'],
            ['ti ti-lamp', '조명'], ['ti ti-box', '박스'], ['ti ti-package', '패키지'], ['ti ti-truck', '운송'],
            ['ti ti-ticket', '티켓'], ['ti ti-trophy', '트로피'], ['ti ti-medal', '메달'],
            ['ti ti-leaf', '친환경'], ['ti ti-flame', '인기/핫'], ['ti ti-droplet', '물방울'], ['ti ti-snowflake', '눈/겨울'],
            ['ti ti-rocket', '시작하기'], ['ti ti-bulb', '아이디어'], ['ti ti-anchor', '고정'], ['ti ti-recycle', '재활용'],
        ],
    ];

    $totalCount = collect($iconGroups)->sum(fn($g) => count($g));
@endphp

<div class="icon-library">
    <div class="icon-library__header">
        <div>
            <h2 class="icon-library__title">아이콘 라이브러리</h2>
            <p class="icon-library__subtitle">총 {{ $totalCount }}개 · 클릭하면 클래스명이 복사됩니다</p>
        </div>
        <div class="icon-library__search">
            <i class="ti ti-search icon-library__search-icon"></i>
            <input type="text" id="iconSearch" class="icon-library__search-input" placeholder="아이콘 이름 또는 클래스명 검색 (예: 삭제, trash)" autocomplete="off">
            <span class="icon-library__search-count" id="searchCount"></span>
        </div>
    </div>

    {{-- 카테고리 빠른 이동 --}}
    <nav class="icon-library__nav" id="categoryNav">
        @foreach($iconGroups as $groupName => $icons)
            <a href="#group-{{ $loop->index }}" class="icon-library__nav-link {{ $loop->first ? 'is-active' : '' }}">{{ $groupName }}</a>
        @endforeach
    </nav>

    @foreach($iconGroups as $groupName => $icons)
        <section class="icon-library__group" id="group-{{ $loop->index }}" data-group-name="{{ $groupName }}">
            <h3 class="icon-library__group-title">{{ $groupName }}</h3>
            <div class="icon-grid">
                @foreach($icons as [$icon, $label])
                    <button type="button" class="icon-card"
                        data-icon="{{ $icon }}"
                        data-label="{{ $label }}"
                        data-svg='<i class="{{ $icon }}"></i>'
                        data-search="{{ $label }} {{ $icon }}">

                        <i class="{{ $icon }} icon-card__glyph"></i>
                        <span class="icon-card__label">{{ $label }}</span>
                        <span class="icon-card__class">{{ $icon }}</span>

                    </button>
                @endforeach
            </div>
        </section>
    @endforeach

    <p class="icon-library__empty" id="emptyState" hidden>
        <i class="ti ti-mood-empty"></i>
        검색 결과가 없습니다
    </p>
</div>

<style>
    .icon-library { max-width: 1280px; margin: 0 auto; padding: 28px 24px 80px; font-feature-settings: "tnum"; }
    .icon-library__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
    .icon-library__title { font-size: 1.5rem; font-weight: 700; margin: 0 0 4px; color: #1c2433; }
    .icon-library__subtitle { margin: 0; font-size: 0.875rem; color: #7b8499; }
    .icon-library__search { position: relative; width: 100%; max-width: 360px; }
    .icon-library__search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa3b6; font-size: 1rem; pointer-events: none; }
    .icon-library__search-input { width: 100%; padding: 10px 44px 10px 38px; border-radius: 10px; border: 1px solid #e2e5ec; background: #fff; font-size: 0.9rem; color: #1c2433; transition: border-color .15s ease, box-shadow .15s ease; }
    .icon-library__search-input:focus { outline: none; border-color: #4263eb; box-shadow: 0 0 0 3px rgba(66, 99, 235, .12); }
    .icon-library__search-count { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: #9aa3b6; }
    .icon-library__nav { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; gap: 6px; padding: 10px 0; margin-bottom: 20px; background: #f8f9fc; border-bottom: 1px solid #eceef3; }
    .icon-library__nav-link { font-size: 0.8125rem; font-weight: 500; color: #5b6478; background: #fff; border: 1px solid #e2e5ec; border-radius: 999px; padding: 6px 14px; text-decoration: none; white-space: nowrap; transition: all .15s ease; }
    .icon-library__nav-link:hover { border-color: #4263eb; color: #4263eb; }
    .icon-library__nav-link.is-active { background: #4263eb; border-color: #4263eb; color: #fff; }
    .icon-library__group { margin-bottom: 36px; scroll-margin-top: 120px; } /* 수정: scroll-margin-top 증가 */
    .icon-library__group-title { display: flex; align-items: center; gap: 8px; font-size: 1.0625rem; font-weight: 700; color: #1c2433; padding-bottom: 10px; margin: 0 0 16px; border-bottom: 2px solid #eceef3; }
    .icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; }
    .icon-card { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 16px 10px 12px; background: #fff; border: 1px solid #eceef3; border-radius: 12px; cursor: pointer; transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease; font-family: inherit; }
    .icon-card:hover { transform: translateY(-2px); border-color: #4263eb; box-shadow: 0 6px 16px rgba(66, 99, 235, .12); }
    .icon-card:active { transform: translateY(0); }
    .icon-card__glyph { font-size: 1.65rem; color: #343c4d; line-height: 1; }
    .icon-card__label { font-size: 0.8125rem; font-weight: 600; color: #1c2433; text-align: center; }
    .icon-card__class { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.6875rem; color: #9aa3b6; text-align: center; word-break: break-all; }
    .icon-card.is-hidden { display: none; }
    .icon-library__empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 60px 0; color: #9aa3b6; font-size: 0.9375rem; }
    .icon-library__empty i { font-size: 2rem; }
    @media (max-width: 576px) { .icon-library { padding: 20px 16px 60px; } .icon-grid { grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); } }
</style>

<script>
    // 기존 함수를 아래와 같이 수정하세요
    function copyToClipboard(text, label) {
        navigator.clipboard.writeText(text).then(() => {
            const alertHtml = `
                <div style="text-align: center;">
                    <i class="${text}" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                    <div style="font-weight: bold; font-size: 1.2rem;">${label}</div>
                    <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">${text} 복사되었습니다!</div>
                </div>
            `;

            salert({
                html: alertHtml,
                timer: 1500,
                cancel: false,
            });
        });
    }

    // 클릭 이벤트 리스너 수정
    document.addEventListener('click', function (e) {
        const card = e.target.closest('.icon-card');
        if (card) {
            // 데이터셋에서 icon(클래스)과 label(한글명)을 모두 넘깁니다
            copyToClipboard(card.dataset.icon, card.dataset.label);
        }
    });

    const searchInput = document.getElementById('iconSearch');
    const emptyState = document.getElementById('emptyState');
    const searchCount = document.getElementById('searchCount');
    const allGroups = Array.from(document.querySelectorAll('.icon-library__group'));

    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        let visibleTotal = 0;
        allGroups.forEach(function (group) {
            let visibleInGroup = 0;
            group.querySelectorAll('.icon-card').forEach(function (card) {
                const match = card.dataset.search.toLowerCase().includes(query);
                card.classList.toggle('is-hidden', !match);
                if (match) { visibleInGroup++; visibleTotal++; }
            });
            group.hidden = query.length > 0 && visibleInGroup === 0;
        });
        emptyState.hidden = !(query.length > 0 && visibleTotal === 0);
        searchCount.textContent = query.length > 0 ? visibleTotal + '개' : '';
    });

    const navLinks = Array.from(document.querySelectorAll('.icon-library__nav-link'));
    navLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                navLinks.forEach(l => l.classList.remove('is-active'));
                const activeLink = document.querySelector('.icon-library__nav-link[href="#' + entry.target.id + '"]');
                if (activeLink) activeLink.classList.add('is-active');
            }
        });
    }, { rootMargin: '-100px 0px -70% 0px' }); // 수정: 스크롤 감지 범위 최적화

    allGroups.forEach(s => observer.observe(s));
</script>
@endsection
