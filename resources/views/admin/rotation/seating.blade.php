@extends('layouts.app')

@section('title', '로테이션 진행')

@section('content')
<style>
    /* Choices 드롭다운이 턴 네비게이션 등 다른 요소 위로 오도록 z-index 최상단 처리.
       열린 셀렉트의 카드가 다른 카드/버튼보다 위에 쌓이도록 함께 올린다. */
    .choices { position: relative; }
    .choices.is-open { z-index: 1080; }
    .choices__list--dropdown,
    .choices__list[aria-expanded] {
        z-index: 1080 !important;
    }
    /* 열린 셀렉트를 품은 카드가 형제 카드 위로 오도록 */
    .card:has(.choices.is-open) { position: relative; z-index: 1080; }

    /* 테이블 카드의 사람 칸: 큰 "N호" + 아래 닉네임(줄바꿈 허용, 말줄임 없음) + 년생·직업 */
    .seat-person { display:flex; align-items:flex-start; gap:.45rem; padding:.4rem .5rem; border-radius:10px; margin-bottom:.35rem; }
    .seat-person.is-m { background:#e7f1ff; } .seat-person.is-f { background:#ffeef4; }
    .seat-person .seat-no { flex:0 0 auto; min-width:2.6rem; text-align:center; font-weight:800; font-size:1.1rem; line-height:1.2; border-radius:8px; padding:.15rem .25rem; color:#fff; }
    .seat-person.is-m .seat-no { background:#206bc4; } .seat-person.is-f .seat-no { background:#d6336c; }
    .seat-person .seat-meta { flex:1 1 auto; min-width:0; line-height:1.3; }
    .seat-person .seat-nick { display:block; font-weight:700; font-size:.82rem; color:#343a40; overflow-wrap:anywhere; }
    .seat-person .seat-info { display:block; font-size:.74rem; color:#6c757d; overflow-wrap:anywhere; }
    .seat-col-head { display:flex; align-items:center; justify-content:center; gap:.3rem; margin-bottom:.4rem; font-weight:700; font-size:.85rem; }
    /* PC(992px↑): 칸이 좁아 닉네임이 단어마다 줄바꿈돼 들쭉날쭉해 보이므로 한 줄로 두고 넘치면 말줄임.
       번호(N호)는 왼쪽 배지로 따로 있어 잘리지 않는다. 전체 내용은 마우스를 올리면(title) 보인다. */
    @media (min-width: 992px) {
        .seat-person { align-items:center; padding:.3rem .45rem; margin-bottom:.3rem; }
        .seat-person .seat-no { font-size:1rem; min-width:2.4rem; }
        .seat-person .seat-nick, .seat-person .seat-info { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    }
    .seat-table-no { font-size:1.15rem; font-weight:800; }

    /* 칩 제거(x) 버튼: 클릭 영역을 넓히고 hover 시 배경 피드백 */
    .seat-chips .chip-remove { transition: background-color .12s ease; cursor: pointer; }
    .seat-chips .chip-remove:hover { background-color: rgba(214, 57, 57, 0.15); }
    /* 칩 안 작은 버튼(상세/로그인) */
    .seat-person .seat-actions .btn { display:inline-flex; align-items:center; justify-content:center; width:2rem; height:1.75rem; padding:0; font-size:1.05rem; line-height:1; }
    .seat-person .seat-actions .btn .ti { font-size:1.05rem; }

    /* ── 진행 컨트롤 ──────────────────────────────────────────────
       상단 요약(현재 턴/총 턴 · 상태 · 안내) + 주 액션, 아래에 턴 진행 막대.
       PC(lg↑): 요약 왼쪽 · 액션 오른쪽 / 태블릿·모바일: 액션은 화면 하단에 고정해 스크롤 중에도 바로 누른다. */
    .seating-top { display:flex; gap:1rem; align-items:stretch; }
    .seating-summary { flex:1 1 auto; min-width:0; }
    .seating-summary__head { display:flex; align-items:center; flex-wrap:wrap; gap:.4rem; font-weight:700; font-size:.85rem; color:var(--tblr-secondary, #667382); }
    .seating-summary__round { font-size:2.1rem; font-weight:800; line-height:1.15; margin-top:.15rem; color:var(--tblr-body-color, #1d273b); }
    .seating-summary__round small { font-size:1.05rem; font-weight:700; color:var(--tblr-secondary, #667382); }
    .seating-summary__hint { font-size:.85rem; color:var(--tblr-secondary, #667382); margin-top:.2rem; line-height:1.5; }

    .seating-actionbar { flex:0 0 340px; display:flex; flex-direction:column; justify-content:center; gap:.5rem; }
    .seating-actionbar__main .btn { min-height:48px; font-weight:700; }
    .seating-actionbar__round { display:none; }
    /* 보조 액션이 하나도 안 보이면 줄 자체를 없앤다 */
    .seating-sub-actions { display:flex; flex-wrap:wrap; gap:.4rem; }
    .seating-sub-actions:not(:has(.btn:not(.d-none))) { display:none !important; }
    .seating-sub-actions .btn { flex:1 1 auto; }

    /* 턴 진행 막대: 1~N턴 칸. 지난 턴은 채움, 보는 턴은 강조, 아직 안 온 턴은 흐리게(누를 수 없음) */
    .seating-turns { display:flex; align-items:center; gap:.4rem; margin-top:1rem; }
    .seating-turns__nav { flex:0 0 auto; width:2.4rem; height:2.4rem; padding:0; display:inline-flex; align-items:center; justify-content:center; }
    .seating-turns__list { flex:1 1 auto; display:flex; gap:.35rem; overflow-x:auto; padding:.2rem .1rem; scrollbar-width:thin; }
    .turn-chip { flex:0 0 auto; min-width:2.6rem; height:2.4rem; padding:0 .55rem; border-radius:10px; border:1px solid var(--tblr-border-color, #e6e7e9);
                 background:#fff; font-weight:700; font-size:.9rem; color:var(--tblr-secondary, #667382); cursor:pointer; transition:background-color .12s ease, border-color .12s ease; }
    .turn-chip.is-done { background:#e7f1ff; border-color:#b6d4fe; color:#1e5aa8; }
    .turn-chip.is-current { box-shadow:inset 0 0 0 2px #206bc4; }
    .turn-chip.is-view { background:#206bc4; border-color:#206bc4; color:#fff; }
    .turn-chip.is-future { background:var(--tblr-bg-surface-secondary, #f8fafc); color:#adb5bd; cursor:default; }
    .turn-chip:not(.is-future):not(.is-view):hover { border-color:#206bc4; }
    .seating-note { display:block; font-size:.8rem; color:var(--tblr-secondary, #667382); margin-top:.45rem; }

    /* 0턴 준비 영역: 로테이션 횟수 + 시작 전 점검 */
    .seating-setup { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem 1rem; margin-top:1rem; padding:.75rem 1rem; border-radius:10px;
                     background:var(--tblr-bg-surface-secondary, #f8fafc); border:1px solid var(--tblr-border-color, #e6e7e9); }
    .seating-setup__label { font-weight:700; font-size:.85rem; }
    .seating-setup .input-group { width:9rem; }
    .seating-setup__hint { flex:1 1 16rem; font-size:.8rem; color:var(--tblr-secondary, #667382); }
    /* Tabler .alert 는 flex 라서 안의 제목/목록이 가로로 나란히 놓여 글자가 세로로 쪼개져 보였다. 블록으로 쌓는다. */
    .seating-preflight { display:block; margin-top:.75rem; margin-bottom:0; font-size:.85rem; padding:.7rem .85rem; }
    .seating-preflight > * { width:100%; }
    /* 시작 전 확인: 제목 → 항목별 블록(라벨 + 칩). 긴 쉼표 나열 대신 칩으로 나눠 모바일에서도 한눈에 보이게 한다. */
    .pf-title { font-weight:800; font-size:.95rem; display:flex; align-items:center; gap:.3rem; }
    .pf-section { margin-top:.65rem; padding-top:.6rem; border-top:1px dashed rgba(0,0,0,.15); }
    .pf-label { font-weight:700; line-height:1.4; margin-bottom:.35rem; }
    .pf-label b { color:#d63939; }
    .pf-chips { display:flex; flex-wrap:wrap; gap:.35rem; }
    .pf-chip { display:inline-flex; align-items:center; gap:.25rem; min-height:30px; padding:.15rem .6rem; border-radius:999px; background:#fff; border:1px solid #e0b252; font-weight:700; font-size:.82rem; line-height:1.2; white-space:nowrap; }
    .pf-chip.is-m { border-color:#9ec5fe; background:#e7f1ff; color:#1c5aa6; }
    .pf-chip.is-f { border-color:#f4a6c3; background:#ffeef4; color:#c2255c; }
    .pf-chip small { font-weight:600; opacity:.85; }
    .pf-more { align-self:center; font-size:.8rem; color:var(--tblr-secondary, #667382); font-weight:600; }

    /* 태블릿·모바일(lg 미만): 주 액션을 화면 하단에 고정한다. (하단 여백은 .seating-actionbar-spacer 로 확보) */
    @media (max-width: 991.98px){
        .seating-top { display:block; }
        .seating-summary__round { font-size:1.8rem; }
        .seating-actionbar { position:fixed; left:0; right:0; bottom:0; z-index:1030; flex-direction:row; flex-wrap:wrap; align-items:center; gap:.4rem .5rem;
                             padding:.6rem .75rem calc(.6rem + env(safe-area-inset-bottom)); background:#fff;
                             border-top:1px solid var(--tblr-border-color, #e6e7e9); box-shadow:0 -6px 16px rgba(29,39,59,.08); }
        .seating-actionbar__round { display:flex; flex-direction:column; justify-content:center; flex:0 0 auto; min-width:3.6rem; text-align:center; line-height:1.15; }
        .seating-actionbar__round b { font-size:1.15rem; }
        .seating-actionbar__round small { font-size:.7rem; color:var(--tblr-secondary, #667382); }
        .seating-actionbar__main { flex:1 1 0; min-width:0; }
        .seating-actionbar__main .btn { min-height:46px; }
        .seating-sub-actions { flex:1 1 100%; }
        .seating-sub-actions .btn { flex:1 1 0; font-size:.78rem; white-space:nowrap; }
        .seating-actionbar-spacer { height:calc(130px + env(safe-area-inset-bottom)); }
    }
    @media (min-width: 992px){
        .seating-actionbar-spacer { display:none; }
        /* PC: 버튼이 화면 오른쪽 끝으로 멀어지지 않게, 요약 바로 옆에 붙이고 구분선으로 나눈다. */
        .seating-top { justify-content:flex-start; gap:1.5rem; }
        .seating-summary { flex:0 1 auto; max-width:560px; }
        .seating-actionbar { flex:0 0 320px; padding-left:1.5rem; border-left:1px solid var(--tblr-border-color, #e6e7e9); }
    }

    /* 결과 보기 버튼 줄(.eventform-btn-grid)은 public/css/custom.css 공통 규칙 — 행사 상세와 같은 크기/배치 */
</style>

{{-- 행사 운영 공용 탭바: 행사 선택 + [행사 정보][체크인][로테이션 진행] --}}
<div class="mt-2">
    <x-event-tabs :event-id="$eventId" active="progress" />
</div>

<div class="row row-cards">
    {{-- 결과 보기 버튼 (진행 중 참고용) — 공통 파셜. 행사는 숨은 #eventSelect 값을 따른다. --}}
    @include('admin.rotation._eventResultButtons', ['eventId' => $eventId, 'eventLabel' => '', 'eventSelect' => '#eventSelect'])

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                {{-- 행사 선택은 상단 탭바가 맡는다. 이 화면의 스크립트가 값을 읽으므로 숨겨서 유지한다. --}}
                <select class="d-none" id="eventSelect" aria-hidden="true" tabindex="-1">
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" {{ $eventId == $event->id ? 'selected' : '' }}>{{ $event->label }}</option>
                    @endforeach
                </select>

                {{-- 상단: 진행 요약(현재 턴 / 총 턴 · 상태 · 다음 할 일) + 주 액션
                     PC 는 오른쪽에, 태블릿·모바일은 화면 하단에 고정된다. --}}
                <div class="seating-top">
                    <div class="seating-summary">
                        <div class="seating-summary__head">
                            <span>로테이션 진행</span>
                            <span class="badge d-none" id="eventStatusBadge"></span>
                        </div>
                        <div class="seating-summary__round" id="summaryRound">-</div>
                        <div class="seating-summary__hint" id="actionHint"></div>
                        {{-- 투표 참여 현황: 매칭중(V)·1:1 진행(S)·종료(X)에서만 노출 --}}
                        <div class="seating-summary__hint d-none" id="voteProgress"></div>
                    </div>

                    <div class="seating-actionbar" id="seatingActionBar">
                        {{-- 모바일 하단 바 전용: 지금 몇 턴인지 --}}
                        <div class="seating-actionbar__round" id="actionBarRound"></div>
                        {{-- 주 액션: 상태에 따라 하나만 보인다 --}}
                        <div class="seating-actionbar__main">
                            <button type="button" class="btn btn-primary w-100 d-none" id="rotateBtn">
                                <i class="ti ti-rotate-clockwise me-1"></i>로테이션 진행
                            </button>
                            <button type="button" class="btn btn-success w-100 d-none" id="finishBtn">
                                <i class="ti ti-checkbox me-1"></i>매칭 시작
                            </button>
                            <button type="button" class="btn btn-danger w-100 d-none" id="closeVotingBtn">
                                <i class="ti ti-flag-check me-1"></i>매칭 종료
                            </button>
                            <span class="btn btn-outline-secondary w-100 d-none disabled" id="closedBtn">
                                <i class="ti ti-lock me-1"></i>종료된 행사
                            </span>
                            <button type="button" class="btn btn-outline-primary w-100 d-none" id="pastNotice">
                                <i class="ti ti-player-track-next me-1"></i>최신 턴으로 이동
                            </button>
                        </div>
                        {{-- 보조 액션(되돌리기류) — 노출되는 것만 나란히 --}}
                        <div class="seating-sub-actions">
                            {{-- 로테이션 되돌리기 (실수로 한 번 더 진행했을 때). 진행 중(S)·1턴 이상에서만 노출 --}}
                            <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="rotateBackBtn">
                                <i class="ti ti-arrow-back-up me-1"></i>이전 턴으로 되돌리기
                            </button>
                            {{-- 종료 되돌리기 (실수로 매칭을 종료했을 때). 종료(X) 상태에서만 노출 --}}
                            <button type="button" class="btn btn-outline-dark btn-sm d-none" id="reopenBtn">
                                <i class="ti ti-lock-open me-1"></i>종료 되돌리기
                            </button>
                            {{-- 매칭 시작 취소 (실수로 매칭을 시작했을 때). 매칭중(V)·와인파티에서만 노출. 기존 투표는 유지 --}}
                            <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="cancelVotingBtn">
                                <i class="ti ti-arrow-back-up me-1"></i>매칭 시작 취소
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 턴 진행 막대: 칸을 눌러 그 턴의 배치를 바로 본다. (아직 안 온 턴은 누를 수 없음) --}}
                <div class="seating-turns">
                    <button class="btn btn-outline-secondary seating-turns__nav" type="button" id="roundPrev" title="이전 턴 보기" aria-label="이전 턴 보기">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <div class="seating-turns__list" id="turnStepper" role="group" aria-label="턴 선택"></div>
                    <button class="btn btn-outline-secondary seating-turns__nav" type="button" id="roundNext" title="다음 턴 보기" aria-label="다음 턴 보기">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                    {{-- 보고 있는 턴 값 (스크립트용) --}}
                    <input type="hidden" id="roundInput" value="{{ $roundNo }}">
                </div>
                <small class="seating-note" id="roundHint"></small>

                {{-- 0턴(시작 전)에만: 로테이션 횟수(총 턴 수). 비우면 테이블 수만큼.
                     별도 저장 버튼 없이, 로테이션을 시작할 때 입력값이 함께 저장된다. --}}
                <div class="seating-setup d-none" id="rotationLimitBox">
                    <label class="seating-setup__label mb-0" for="rotationLimitInput">로테이션 횟수</label>
                    <div class="input-group input-group-sm">
                        <input type="number" min="1" class="form-control text-center" id="rotationLimitInput"
                               inputmode="numeric" placeholder="테이블 수">
                        <span class="input-group-text">턴</span>
                    </div>
                    <span class="d-none" id="rotationProgressBadge"></span>
                    <span class="seating-setup__hint" id="rotationLimitHint"></span>
                </div>

                {{-- 0턴(시작 전)에만: 시작 전 점검 — 자리 미배정 회원 · 테이블 성비 --}}
                <div class="alert seating-preflight d-none" id="preflight" role="status"></div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div id="boardArea" class="row row-cards"></div>
    </div>
</div>
{{-- 태블릿·모바일: 하단 고정 액션 바에 마지막 테이블이 가리지 않도록 여백 확보 --}}
<div class="seating-actionbar-spacer" aria-hidden="true"></div>

<script>
    $(document).ready(function(){
        const dataUrl    = "{{ route('admin.rotation.seating.data') }}";
        const storeUrl   = "{{ route('admin.rotation.seating.store') }}";
        const deleteUrl  = "{{ route('admin.rotation.seating.delete') }}";
        const rotateUrl  = "{{ route('admin.rotation.seating.rotate') }}";
        const rotateBackUrl = "{{ route('admin.rotation.seating.rotateBack') }}";
        const rotationLimitUrl = "{{ route('admin.rotation.seating.rotationLimit') }}";
        const finishUrl  = "{{ route('admin.rotation.seating.finish') }}";
        const closeVotingUrl = "{{ route('admin.rotation.seating.closeVoting') }}";
        const cancelVotingUrl = "{{ route('admin.rotation.seating.cancelVoting') }}";
        const reopenUrl  = "{{ route('admin.rotation.seating.reopen') }}";
        const voteProgressUrl = "{{ route('admin.rotation.seating.voteProgress') }}";

        // 서버 enum 기반 상태 라벨 (P/S/V/X). 뱃지 표기에 사용.
        const STATUS_LABEL = @json($statuses);
        // 상태별 Tabler 뱃지 색상 클래스. 종료(X)는 검은 뱃지(bg-dark + 흰 글씨).
        const STATUS_BADGE = { P: 'bg-yellow-lt', S: 'bg-green-lt', V: 'bg-pink-lt', X: 'bg-dark text-white' };
        // 상태 뱃지에 쓰일 수 있는 색상 클래스 전부 (갱신 시 먼저 제거할 목록)
        const STATUS_BADGE_CLASSES = 'bg-yellow-lt bg-green-lt bg-pink-lt bg-secondary-lt bg-secondary bg-dark text-white';

        // 현재 행사 상태 뱃지를 최신 상태로 갱신한다.
        function renderStatusBadge(status){
            let $badge = $('#eventStatusBadge');
            if(!status || !STATUS_LABEL[status]){
                $badge.addClass('d-none').text('');
            } else {
                let cls = STATUS_BADGE[status] || 'bg-secondary';
                $badge.removeClass(STATUS_BADGE_CLASSES).removeClass('d-none')
                      .addClass(cls)
                      .text('현재 상태: ' + STATUS_LABEL[status]);
            }
            // 상단 탭바(event-tabs)의 상태 표시들도 함께 갱신해, 로테이션 진행/매칭 시작·종료로
            // 상태가 바뀔 때 화면의 모든 상태 표기가 어긋나지 않게 한다.
            syncEventTabsStatus(status);
        }

        // 상단 탭바(event-tabs 컴포넌트)의 상태 표기를 현재 상태/턴에 맞춰 갱신한다.
        //  - 가운데 상태 뱃지(#eventTabsStatusBadge)의 색/라벨
        //  - '로테이션 진행' 탭의 턴 뱃지([data-event-tabs-round]) — 종료면 '종료', 아니면 'N턴'/'진행 전'
        //  - 숨은 행사 선택(#eventTabsSelect)의 선택된 옵션 라벨 접두 [상태]
        function syncEventTabsStatus(status){
            if(status && STATUS_LABEL[status]){
                // 탭바 상태 칩: 색은 data-status(CSS), 글자는 안쪽 <b>만 바꾼다. (항목명 '상태'는 유지)
                $('#eventTabsStatusBadge').attr('data-status', status).find('b').text(STATUS_LABEL[status]);
            }

            // 턴 뱃지: 종료(X)면 '종료', 매칭중(V)면 '매칭중'(턴 수 대신 상태 표기),
            // 그 외엔 1턴 이상이면 'N턴', 아니면 '진행 전'
            $('[data-event-tabs-round]').text(
                status === 'X' ? '종료'
                    : (status === 'V' ? '매칭중'
                        : (currentRound > 0 ? (currentRound + '턴') : '진행 전'))
            );

            // 선택된 옵션 라벨의 [상태] 접두를 갱신한다. (형식: "[상태] 제목 · 일시")
            if(status && STATUS_LABEL[status]){
                let $opt = $('#eventTabsSelect option:selected');
                if($opt.length){
                    let text = $opt.text();
                    let replaced = text.replace(/^\s*\[[^\]]*\]/, '[' + STATUS_LABEL[status] + ']');
                    // 원래 접두가 없던 경우엔 앞에 붙인다.
                    $opt.text(replaced === text && !/^\s*\[/.test(text) ? ('[' + STATUS_LABEL[status] + '] ' + text) : replaced);
                }
            }
        }

        // 액션(로테이션 진행/매칭 시작·종료) 응답으로 상태·턴 표기를 즉시 갱신한다.
        // 응답 data 에 담긴 status/round_no 를 신뢰 소스로 사용한다. (뒤이은 loadData 전에 선반영)
        function applyStatusFromResponse(res){
            let d = res && res.data ? res.data : {};
            if(typeof d.round_no !== 'undefined'){ currentRound = d.round_no; }
            renderStatusBadge(d.status || null);
        }

        let members = [];
        let dataXhr = null;      // 진행 중인 요청 추적 (중복 호출 방지)
        let viewRound = null;    // 현재 보고 있는 턴 (null이면 최신 턴으로 자동 설정)
        let currentRound = 0;    // 좌석이 존재하는 최신 턴 (메타 기준)
        let isClosed = false;    // 행사가 종료(X)되었는지 — 종료 시 회원 추가 비활성
        let isOneToOne = false;  // 1:1 로테이션 행사 여부 — 마지막 턴 '매칭 종료' 버튼/문구 분기

        // 성별 색상 구분: 남성=파랑(blue), 여성=핑크(pink)
        function escHtml(v){ return $('<div>').text(v == null ? '' : v).html(); }
        function genderBadgeClass(g){ return g === 'F' ? 'bg-pink-lt' : 'bg-blue-lt'; }
        function genderLabel(g){ return g === 'F' ? '여' : '남'; }

        // assignedIds: 현재 턴에 이미 배정된 회원 id Set (중복 배정 방지용)
        function memberOptions(assignedIds){
            let html = '<option value="">+ 회원 추가</option>';
            members.forEach(function(m){
                if(assignedIds && assignedIds.has(m.id)) return; // 이미 배정된 회원은 제외
                // 동명이인 구분을 위해 직업/출생연도를 함께 표기한다.
                let info = [];
                if(m.birth_year){ info.push(String(m.birth_year).slice(-2) + '년생'); }
                if(m.job){ info.push(m.job); }
                let suffix = info.length ? ` · ${info.join(' · ')}` : '';
                html += `<option value="${m.id}">[${genderLabel(m.gender)}] ${m.label || (m.name + "호")}${suffix}</option>`;
            });
            return html;
        }

        function loadData(){
            // 이전 요청이 진행 중이면 취소하여 중복 렌더 방지
            if(dataXhr){ dataXhr.abort(); }
            // viewRound가 아직 없으면(최초) -1을 보내 서버가 최신 턴을 선택하게 한다.
            let reqRound = (viewRound === null) ? -1 : viewRound;
            dataXhr = $.get(dataUrl, { eventId: $('#eventSelect').val(), roundNo: reqRound }, function(res){
                members = res?.members || [];
                renderActions(res?.board?.meta || {});
                renderBoard(res?.board || {});
            }).always(function(){ dataXhr = null; });
            if(window.EventResultButtons){ EventResultButtons.refresh(); }
            return dataXhr;
        }

        // 투표 참여 현황 표시/갱신.
        //  - 매칭이 열려 있는 동안(와인파티 V · 1:1 S)은 10초마다 갱신하고, 종료(X)에서는 최종값을 한 번만 보여준다.
        //  - 그 외 상태(대기/로테이션 진행 중)에서는 숨기고 갱신을 멈춘다.
        let voteTimer = null;

        function renderVoteProgress(p){
            let total = p.total || 0;
            let pct   = total > 0 ? Math.round(p.voted / total * 100) : 0;
            $('#voteProgress').html(
                '<i class="ti ti-chart-bar me-1"></i>투표 참여 <b>' + p.voted + '/' + total + '명</b> (' + pct + '%)'
                + ' · 남 ' + p.male.voted + '/' + p.male.total + ' · 여 ' + p.female.voted + '/' + p.female.total
                + '<div class="progress progress-sm mt-1"><div class="progress-bar bg-pink" style="width:' + pct + '%"></div></div>'
            ).removeClass('d-none');
        }

        function refreshVoteProgress(){
            let eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            // 주기 조회라 전역 "처리 중..." 로딩 팝업(ajaxSetup beforeSend/complete)은 끈다.
            $.ajax({
                url: voteProgressUrl, type: 'GET', data: { eventId: eventId },
                beforeSend: $.noop, complete: $.noop,
            }).done(renderVoteProgress);
        }

        function syncVoteProgress(meta){
            let isOpen = meta.event_status === 'V' || (meta.is_one_to_one && meta.event_status === 'S');
            let show   = isOpen || !!meta.is_closed;

            if(voteTimer){ clearInterval(voteTimer); voteTimer = null; }

            if(!show){
                $('#voteProgress').addClass('d-none').empty();
                return;
            }

            refreshVoteProgress();
            if(isOpen){
                voteTimer = setInterval(function(){
                    if(!document.hidden){ refreshVoteProgress(); }
                }, 10000);
            }
        }

        // 메타 기반 버튼/네비게이션 제어:
        //  - 턴 네비게이션(◀▶)으로 과거 턴 조회 가능
        //  - [로테이션 진행]/[종료]는 "최신 턴을 보고 있을 때만" 노출
        //  - 과거 턴을 볼 때는 진행/종료 버튼 숨김
        function renderActions(meta){
            syncVoteProgress(meta);
            let $rotate = $('#rotateBtn').addClass('d-none');
            let $rotateBack = $('#rotateBackBtn').addClass('d-none');
            let $finish = $('#finishBtn').addClass('d-none');
            let $closeVoting = $('#closeVotingBtn').addClass('d-none');
            let $closed = $('#closedBtn').addClass('d-none');
            let $reopen = $('#reopenBtn').addClass('d-none');
            let $cancelVoting = $('#cancelVotingBtn').addClass('d-none');
            let $hint   = $('#actionHint');
            let $roundHint = $('#roundHint');
            let $pastNotice = $('#pastNotice').addClass('d-none');

            currentRound = (typeof meta.current_round !== 'undefined') ? meta.current_round : 0;

            // 종료(X) 여부: 회원 추가 비활성화 판단에 사용.
            isClosed = !!meta.is_closed;
            // 1:1 로테이션 여부: 마지막 턴 '매칭 종료' 버튼/문구 분기에 사용.
            isOneToOne = !!meta.is_one_to_one;

            // 현재 행사 상태 뱃지 최신화 (버튼 클릭 → loadData 재호출 시마다 갱신됨)
            renderStatusBadge(meta.event_status);

            // 조회 가능한 최소 턴: 1턴까지만 (0턴은 세팅용이라 조회하지 않음).
            // 단, 아직 0턴만 있는 세팅 단계(current_round=0)에서는 0턴을 본다.
            let minRound = Math.min(1, currentRound);

            // 최초 로딩이면 보는 턴을 최신 턴으로 맞춘다.
            if(viewRound === null){ viewRound = currentRound; }
            // 보는 턴이 [minRound, currentRound] 범위를 벗어나지 않도록 보정
            if(viewRound > currentRound){ viewRound = currentRound; }
            if(viewRound < minRound){ viewRound = minRound; }

            $('#roundInput').val(viewRound);

            // 네비게이션 버튼 활성/비활성 (1턴 아래로는 못 내려감)
            $('#roundPrev').prop('disabled', viewRound <= minRound);
            $('#roundNext').prop('disabled', viewRound >= currentRound);

            let isLatest = (viewRound === currentRound);
            let isBaseView = (viewRound <= 1);
            if(isBaseView){
                $roundHint.html(currentRound === 0
                    ? '<i class="ti ti-armchair me-1"></i>기준 자리를 배치하는 단계예요. 아래 테이블에서 회원을 추가·삭제할 수 있어요.'
                    : '<i class="ti ti-pencil me-1"></i>1턴(기준) 화면이에요. 여기서 자리를 옮기면 <b>모든 턴에 바로 반영</b>돼요.');
            } else {
                $roundHint.html('<i class="ti ti-eye me-1"></i>' + viewRound + '턴 조회 화면이에요. 자리를 옮기려면 <b>1턴</b>으로 이동하세요.');
            }

            renderTurns(meta);
            renderRotationLimit(meta);

            // 생성된 테이블이 없으면 액션 버튼을 노출하지 않는다.
            if(!meta.table_count){
                $hint.text('');
                return;
            }

            // 투표중/종료는 로테이션이 끝난 뒤의 "행사 전체 상태"이므로,
            // 어느 턴을 보고 있든 상태 기준으로 먼저 처리한다.
            if(meta.is_closed){
                $closed.removeClass('d-none');
                $reopen.removeClass('d-none');
                // 되돌리면 복귀할 상태를 타입별로 안내한다. (와인파티→매칭중 / 1:1→진행)
                var backLabel = meta.is_one_to_one ? '진행' : '매칭중';
                $hint.html('매칭이 마감된 행사예요. 실수로 종료했다면 <b>종료 되돌리기</b>로 <b>' + backLabel + '</b> 상태로 돌아갈 수 있어요.');
                return;
            }
            if(meta.is_voting){
                $closeVoting.removeClass('d-none');
                // 매칭중에서 진행(S)으로 되돌리기 — 와인파티 전용(1:1은 V 단계가 없음). 투표가 삭제됨을 안내.
                if(!meta.is_one_to_one){
                    $cancelVoting.removeClass('d-none');
                }
                $hint.html('회원들이 상대를 선택하고 있어요. 행사 다음 날 <b>오전 9시</b>에 자동 종료돼요.'
                    + (!meta.is_one_to_one ? '<br>실수로 시작했다면 <b>매칭 시작 취소</b>로 되돌릴 수 있어요. (기존 투표는 유지)' : ''));
                return;
            }

            // 과거 턴을 보고 있으면 진행/종료 버튼 대신 안내 문구를 표시한다. (label/힌트 라인은 왼쪽과 정렬 유지)
            if(!isLatest){
                $pastNotice.removeClass('d-none');
                $hint.html('지금 <b>' + viewRound + '턴</b>을 보고 있어요. 로테이션 진행은 최신 턴(' + currentRound + '턴)에서 할 수 있어요.');
                return;
            }

            if(meta.is_last){
                if(meta.is_one_to_one){
                    // 1:1: 매칭 선택을 홈에서 받으므로 '매칭 시작(V)' 없이 바로 '매칭 종료(X)'.
                    $closeVoting.removeClass('d-none');
                    $hint.text('마지막 턴이에요. 매칭을 종료하면 결과가 공개돼요.');
                } else {
                    $finish.removeClass('d-none');
                    $hint.text('마지막 턴이에요. 이제 매칭을 시작할 수 있어요.');
                }
            } else {
                $rotate.removeClass('d-none');
                // 0턴(아직 로테이션 시작 전)이면 '시작', 그 이후면 '진행'.
                if(viewRound === 0){
                    $rotate.html('<i class="ti ti-player-play me-1"></i>로테이션 시작');
                    $hint.text('기준 자리 배치를 확인한 뒤 로테이션을 시작하세요.');
                } else {
                    $rotate.html('<i class="ti ti-rotate-clockwise me-1"></i>로테이션 진행');
                    $hint.text('대화 시간이 끝나면 진행하세요. 남자만 다음 테이블로 한 칸 이동해요.');
                }
            }

            // 로테이션 되돌리기: 진행이 1턴 이상 됐고(= 되돌릴 게 있음) 최신 턴을 볼 때만 노출.
            // (투표중/종료는 위에서 이미 return 되므로 여기까지 오지 않는다)
            if(currentRound >= 1){
                $rotateBack.removeClass('d-none');
            }
        }

        // 진행 요약(현재 턴 / 총 턴)과 턴 진행 막대를 그린다.
        //  - 칸: 1~총 턴. 지난 턴(≤ 현재)은 채움, 보고 있는 턴은 강조, 아직 안 온 턴은 흐리게(누를 수 없음).
        //  - 아직 시작 전(0턴)이면 '준비' 칸 하나를 앞에 둔다.
        function renderTurns(meta){
            let max    = meta.max_round || meta.table_count || 0;
            let status = meta.event_status;

            let roundText = currentRound === 0 ? '시작 전' : (currentRound + '턴');
            $('#summaryRound').html(max
                ? (currentRound === 0 ? '시작 전 <small>· 총 ' + max + '턴</small>' : currentRound + '<small> / ' + max + '턴</small>')
                : '<small>테이블을 먼저 만들어 주세요</small>');
            $('#actionBarRound').html(max
                ? '<b>' + (currentRound === 0 ? '0' : currentRound) + '/' + max + '</b><small>' + (status === 'V' ? '매칭중' : (status === 'X' ? '종료' : roundText)) + '</small>'
                : '');

            let $list = $('#turnStepper').empty();
            if(!max){ return; }
            if(currentRound === 0){
                $list.append('<button type="button" class="turn-chip is-view is-current" data-round="0" aria-current="true">준비</button>');
            }
            for(let n = 1; n <= max; n++){
                let cls = ['turn-chip'];
                if(n > currentRound){ cls.push('is-future'); }
                else { cls.push('is-done'); }
                if(n === currentRound){ cls.push('is-current'); }
                if(n === viewRound){ cls.push('is-view'); }
                let future = n > currentRound;
                $list.append('<button type="button" class="' + cls.join(' ') + '" data-round="' + n + '"'
                    + (future ? ' disabled aria-disabled="true"' : '')
                    + (n === viewRound ? ' aria-current="true"' : '')
                    + ' title="' + n + '턴' + (n === currentRound ? ' (현재)' : '') + '">' + n + '</button>');
            }
            // 보고 있는 턴 칸이 보이도록 가로 스크롤을 맞춘다.
            let el = $list.find('.is-view')[0];
            if(el){ $list[0].scrollLeft = Math.max(0, el.offsetLeft - $list[0].clientWidth / 2 + el.clientWidth / 2); }
        }

        // 턴 칸을 누르면 그 턴의 배치를 본다.
        $('#turnStepper').on('click', '.turn-chip:not([disabled])', function(){
            let n = parseInt($(this).data('round'), 10);
            if(isNaN(n) || n === viewRound){ return; }
            viewRound = n;
            loadData();
        });

        // 과거 턴을 보고 있을 때: 최신 턴으로 바로 이동
        $('#pastNotice').on('click', function(){
            viewRound = currentRound;
            loadData();
        });

        // 로테이션 횟수 박스: 로테이션 시작 전(0턴·대기)에만 보이고 수정 가능.
        // 서버에 저장된 로테이션 횟수(없으면 null). 로테이션 시작 때 입력값과 비교해 자동 저장 여부를 정한다.
        var savedRotationLimit = null;

        function renderRotationLimit(meta){
            savedRotationLimit = meta.rotation_limit ? Number(meta.rotation_limit) : null;
            var $box = $('#rotationLimitBox');
            var tables = meta.table_count || 0;
            var max = meta.max_round || tables;
            var cur = meta.current_round || 0;
            var locked = cur > 0 || meta.event_status !== 'P';

            // 시작 전(0턴·대기)에만 보인다. 시작 후 총 턴 수는 위 진행 요약에 표시된다.
            if(!tables || locked){ $box.addClass('d-none'); return; }
            $box.removeClass('d-none');

            $('#rotationProgressBadge').text(cur + ' / ' + max + '턴');
            var $in = $('#rotationLimitInput');
            // 입력 중에는 덮어쓰지 않는다. (주기적 갱신 시 값이 튀지 않게)
            if(!$in.is(':focus')){ $in.val(meta.rotation_limit ? meta.rotation_limit : ''); }
            $in.attr({ min: 1, max: tables, placeholder: tables + ' (테이블 수)' }).prop('disabled', locked);

            $('#rotationLimitHint').html(locked
                ? '<i class="ti ti-lock me-1"></i>로테이션이 시작되어 횟수가 <b>' + max + '턴</b>으로 고정됐어요. 바꾸려면 0턴까지 되돌린 뒤 수정하세요.'
                : (meta.rotation_limit
                    ? '<b>' + max + '턴</b>에서 종료 (테이블 ' + tables + '개 중 ' + max + '개만 이동) · 비우면 테이블 수만큼'
                    : '비우면 테이블 수만큼(<b>' + tables + '턴</b>) · 1~' + tables + '턴으로 줄일 수 있어요')
                    + ' · <b>시작할 때 함께 저장</b>돼요.');
        }

        // 로테이션 횟수 입력칸: Enter 로 폼이 전송되거나 다른 동작이 일어나지 않게 막는다. (저장은 로테이션 시작 때)
        $('#rotationLimitInput').on('keydown', function(e){
            if(e.key === 'Enter'){ e.preventDefault(); $(this).blur(); }
        });

        function renderBoard(data){
            // 이전 Choices 인스턴스를 먼저 정리(원본 select 복원)한 뒤 보드를 비운다.
            destroySeatChoices();
            let $area = $('#boardArea').empty();
            $('#preflight').addClass('d-none').empty();
            if(!data.tables || data.tables.length === 0){
                $area.html('<div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">생성된 테이블이 없습니다. 행사 관리 &gt; 상세에서 테이블을 먼저 추가해주세요.</div></div></div>');
                return;
            }

            // 과거 턴에서도 인원 추가/삭제는 항상 가능하게 둔다. (로테이션 진행 버튼만 최신 턴 제한)
            // 현재 턴에 이미 배정된 회원 id 수집 (다른 테이블 셀렉트에서 제외)
            let assignedIds = new Set();
            data.tables.forEach(function(t){
                t.members.forEach(function(m){ assignedIds.add(m.member_id); });
            });

            renderPreflight(data.tables, assignedIds);

            // 좌석(기준자리) 편집은 "기준 뷰"(1턴 이하)에서만 허용한다.
            // 2턴 이상에서 보이는 자리는 공식으로 계산된 '이동 결과'이므로,
            // 그 자리에 추가하면 홈 테이블이 잘못 지정된다. → 편집 비활성.
            let isBaseView = !isClosed && (viewRound === null || viewRound <= 1);

            data.tables.forEach(function(t){
                // 성별로 분리
                var males   = t.members.filter(function(m){ return m.gender === 'M'; });
                var females = t.members.filter(function(m){ return m.gender === 'F'; });
                var maleN = males.length, femaleN = females.length;

                // 사람 1명 칸: 큰 "N호" · 닉네임(플랫폼) · 년생·직업. 라벨은 말줄임하지 않고 줄바꿈한다.
                function chip(m){
                    let info = [];
                    if(m.birth_year){ info.push(String(m.birth_year).slice(-2) + '년생'); }
                    if(m.job){ info.push(m.job); }
                    // 라벨 `닉네임(플랫폼)_N호` → 닉네임 부분만 (번호는 왼쪽 큰 숫자로 따로 보여준다)
                    let label = String(m.label || '');
                    let nick  = label.replace(/_?\s*\d+호$/, '').trim();
                    if(nick === label || nick === ''){ nick = ''; }
                    let removeBtn = isBaseView
                        ? `<a href="#" class="chip-remove d-inline-flex align-items-center justify-content-center text-danger flex-shrink-0"
                                   data-seating-id="${m.seating_id}" title="제거" aria-label="${escHtml(m.name)}호 자리에서 빼기"
                                   style="width:1.6rem;height:1.6rem;border-radius:50%;text-decoration:none;"><i class="ti ti-x" style="font-size:1rem;line-height:1;"></i></a>`
                        : '';
                    let tip = [m.label || (m.name + '호')].concat(info).join(' · ');
                    return `<div class="seat-person ${m.gender === 'F' ? 'is-f' : 'is-m'}" title="${escHtml(tip)}">
                                <span class="seat-no">${escHtml(m.name)}<small style="font-size:.7em;font-weight:700;">호</small></span>
                                <span class="seat-meta">
                                    ${nick ? `<span class="seat-nick">${escHtml(nick)}</span>` : ''}
                                    ${info.length ? `<span class="seat-info">${escHtml(info.join(' · '))}</span>` : ''}
                                    <span class="seat-actions d-flex gap-1 mt-1">
                                        <button type="button" class="btn btn-outline-azure btn-seat-detail" data-member-id="${m.member_id}" title="상세보기" aria-label="${escHtml(m.name)}호 상세보기"><i class="ti ti-id"></i></button>
                                        <button type="button" class="btn btn-outline-orange btn-member-login" data-member-id="${m.member_id}" title="회원으로 로그인" aria-label="${escHtml(m.name)}호 회원으로 로그인"><i class="ti ti-login-2"></i></button>
                                    </span>
                                </span>
                                ${removeBtn}
                            </div>`;
                }

                // 한 컬럼(남 또는 여) HTML — 헤더(성별·인원) + 칩 세로 나열
                function genderColumn(label, badgeCls, list){
                    var sorted = list.slice().sort(function(a, b){ return (parseInt(a.name, 10) || 0) - (parseInt(b.name, 10) || 0); });
                    var chipsHtml = sorted.length
                        ? sorted.map(chip).join('')
                        : '<div class="text-muted text-center" style="font-size:0.85em;">없음</div>';
                    return `
                        <div class="col-6">
                            <div class="seat-col-head"><span class="badge ${badgeCls}">${label}</span><span>${list.length}명</span></div>
                            <div class="seat-chips">${chipsHtml}</div>
                        </div>`;
                }

                // 기준 뷰에서만 '회원 추가' 셀렉트 노출. 아니면 안내 문구.
                let adder = isBaseView
                    ? `<select class="form-select form-select-sm seat-add mt-2" data-table-id="${t.id}">${memberOptions(assignedIds)}</select>`
                    : `<div class="text-muted small mt-2"><i class="ti ti-info-circle me-1"></i>좌석 편집은 1턴(기준) 화면에서만 가능합니다.</div>`;

                // 성비 뱃지(헤더)
                let ratioBadges = `<span class="badge bg-blue-lt ms-1">남 ${maleN}</span>`
                                + `<span class="badge bg-pink-lt ms-1">여 ${femaleN}</span>`;

                let card = `
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex align-items-center flex-wrap gap-1">
                                <span class="seat-table-no">${t.table_no}번 테이블</span>
                                <span class="text-muted ms-1">${t.members.length}명</span>
                                <span class="ms-auto">${ratioBadges}</span>
                            </div>
                            <div class="card-body py-2">
                                <div class="row g-2">
                                    ${genderColumn('남', 'bg-blue-lt', males)}
                                    ${genderColumn('여', 'bg-pink-lt', females)}
                                </div>
                                ${adder}
                            </div>
                        </div>
                    </div>`;
                $area.append(card);
            });

            // 각 테이블의 '회원 추가' 셀렉트를 Choices(검색형)로 변환.
            // 보드는 매 렌더마다 재생성되므로 여기서 매번 초기화한다.
            initSeatChoices();
        }

        // 시작 전 점검(0턴·종료 전만): 자리 미배정 회원과 성비가 맞지 않는 테이블을 알려준다. (시작을 막지는 않는다)
        //  - 1:1: 테이블마다 남 1 · 여 1 이어야 한다.
        //  - 와인파티: 남녀 수가 다르면 확인용으로 알린다. (3:2 등은 운영상 가능)
        function renderPreflight(tables, assignedIds){
            let $box = $('#preflight');
            if(currentRound !== 0 || isClosed){ $box.addClass('d-none').empty(); return; }

            let unassigned = members.filter(function(m){ return !assignedIds.has(m.id); });
            let uneven = [];
            tables.forEach(function(t){
                let m = t.members.filter(function(x){ return x.gender === 'M'; }).length;
                let f = t.members.length - m;
                let bad = isOneToOne ? (m !== 1 || f !== 1) : (m !== f);
                if(bad){ uneven.push({ no: t.table_no, m: m, f: f }); }
            });

            if(!unassigned.length && !uneven.length){
                $box.removeClass('d-none alert-warning').addClass('alert-success')
                    .html('<i class="ti ti-circle-check me-1"></i>모든 회원이 자리에 배정됐어요. 로테이션을 시작할 수 있어요.');
                return;
            }

            let sections = [];
            if(unassigned.length){
                let MAX = 12;
                let chips = unassigned.slice(0, MAX).map(function(m){
                    return '<span class="pf-chip ' + (m.gender === 'F' ? 'is-f' : 'is-m') + '">' + escHtml(m.name) + '호 <small>' + genderLabel(m.gender) + '</small></span>';
                }).join('');
                if(unassigned.length > MAX){ chips += '<span class="pf-more">외 ' + (unassigned.length - MAX) + '명</span>'; }
                sections.push('<div class="pf-section"><div class="pf-label">자리에 배정되지 않은 회원 <b>' + unassigned.length + '명</b></div><div class="pf-chips">' + chips + '</div></div>');
            }
            if(uneven.length){
                let chips = uneven.map(function(u){
                    return '<span class="pf-chip">' + u.no + '번 <small>남 ' + u.m + ' · 여 ' + u.f + '</small></span>';
                }).join('');
                sections.push('<div class="pf-section"><div class="pf-label">' + (isOneToOne ? '남 1 · 여 1이 아닌 테이블' : '남녀 수가 다른 테이블') + ' <b>' + uneven.length + '개</b></div><div class="pf-chips">' + chips + '</div></div>');
            }
            $box.removeClass('d-none alert-success').addClass('alert-warning')
                .html('<div class="pf-title"><i class="ti ti-alert-triangle"></i>시작 전 확인해 주세요</div>' + sections.join(''));
        }

        // 회원 추가 셀렉트(.seat-add)에 Choices 적용. (단일 선택 + 검색)
        // 보드가 매 렌더마다 재생성되므로, 이전 인스턴스를 먼저 destroy 하여
        // option 이 중복 누적되는 것을 방지한다.
        let seatChoices = [];
        function destroySeatChoices(){
            seatChoices.forEach(function(c){
                try { c.destroy(); } catch (e) { /* noop */ }
            });
            seatChoices = [];
        }
        function initSeatChoices(){
            destroySeatChoices();
            document.querySelectorAll('#boardArea .seat-add').forEach(function(el){
                var c = new Choices(el, {
                    searchEnabled: true,
                    searchFields: ['label'],
                    searchFloor: 1,
                    searchResultLimit: -1,
                    shouldSort: false,
                    itemSelectText: '',
                    searchPlaceholderValue: '🔍 이름으로 검색',
                    noResultsText: '검색 결과가 없습니다.',
                    noChoicesText: '추가할 회원이 없습니다.',
                    fuseOptions: { threshold: 0.3, ignoreLocation: true },
                });
                // 종료된 행사면 선택 자체를 막는다.
                if(isClosed){ try { c.disable(); } catch(e){} }
                seatChoices.push(c);
            });
        }

        // 회원 추가 (넣을 때마다 즉시 저장)
        $('#boardArea').on('change', '.seat-add', function(){
            let memberId = $(this).val();
            let tableId  = $(this).data('table-id');
            if(!memberId) return;

            $.ajax({
                url: storeUrl,
                type: 'POST',
                data: {
                    event_id: $('#eventSelect').val(),
                    rotation_table_id: tableId,
                    member_id: memberId
                },
                success: function(res){
                    if(res?.status === 200){ loadData(); }
                    else { alert(res?.msg); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 회원 상세보기: 모달용 데이터를 불러와 회원 상세정보 모달을 연다.
        var memberShowTpl = {!! json_encode(route('admin.rotation.event.members.show', ['id' => $eventId ?: 0, 'memberId' => '__MID__']), JSON_UNESCAPED_SLASHES) !!};
        $('#boardArea').on('click', '.btn-seat-detail', function(){
            var memberId = $(this).data('member-id');
            if(!memberId){ return; }
            $.get(memberShowTpl.replace('__MID__', memberId), function(res){
                if(res && res.data){ MemberDetailModal.open(res.data); }
            }).fail(function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '회원 정보를 불러오지 못했습니다.'); });
        });

        // 회원 제거 (즉시 삭제)
        $('#boardArea').on('click', '.chip-remove', function(e){
            e.preventDefault();
            let seatingId = $(this).data('seating-id');
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: { id: seatingId },
                success: function(res){
                    if(res?.status === 200){ loadData(); }
                    else { alert(res?.msg); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 로테이션 진행: 현재 턴 기준 다음 턴 자동 생성 (여자 고정 / 남자 이동)
        $('#rotateBtn').on('click', async function(){
            // 로테이션 시작(0턴) 때 횟수 입력값이 저장된 값과 다르면, 시작하면서 먼저 저장한다.
            //  빈 값은 "테이블 수만큼"(null)으로 본다.
            let $limitIn     = $('#rotationLimitInput');
            let limitRaw     = String($limitIn.val() || '').trim();
            let limitValue   = limitRaw === '' ? null : Number(limitRaw);
            let pendingLimit = (viewRound === 0 && !$limitIn.prop('disabled') && limitValue !== savedRotationLimit)
                ? limitRaw
                : null;
            if(pendingLimit !== null && pendingLimit !== '' && !/^\d+$/.test(pendingLimit)){
                alert('로테이션 횟수는 숫자로 입력해 주세요.');
                return;
            }

            let msg = (viewRound === 0)
                ? '로테이션을 시작하시겠습니까?'
                : '다음 턴으로 로테이션을 진행하시겠습니까?';
            let html = pendingLimit !== null
                ? '로테이션 횟수를 <b>' + (pendingLimit === '' ? '테이블 수만큼' : pendingLimit + '턴') + '</b>으로 저장하고<br>로테이션을 시작하시겠습니까?'
                : null;
            // 확인창을 띄우기 전 턴을 기억해, 그 사이 다른 곳에서 진행되었거나 연타되면 서버가 거부하게 한다.
            let expectedRound = currentRound;
            if(!(await salert(html !== null ? {html: html} : {text: msg}))) return;

            function startRotate(){
                $.ajax({
                    url: rotateUrl,
                    type: 'POST',
                    data: { event_id: $('#eventSelect').val(), expected_round: expectedRound },
                    success: function(res){
                        // 응답(권한 있는 최신 값)으로 상태/턴 표기를 먼저 즉시 갱신한다.
                        // (뒤이은 loadData 조회가 끝나기 전에도 상단 탭바·카드 뱃지가 어긋나지 않게)
                        applyStatusFromResponse(res);
                        // 진행 후 최신 턴을 보도록 viewRound를 리셋하고, 보드 갱신이 끝난 뒤 알림.
                        viewRound = null;
                        $.when(loadData()).always(function(){ alert(res?.msg); });
                    },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
                });
            }

            if(pendingLimit === null){
                startRotate();
                return;
            }

            // 횟수 저장 → 시작을 이어서 실행한다. 저장 실패(범위 초과 등)면 시작하지 않는다.
            // 시작 요청을 저장 요청의 success 안에서 보내야 전역 로딩('처리 중...')이 닫히지 않고 한 번만 보인다.
            // (전역 로딩은 진행 중 요청 수로 관리되고, complete 는 success 뒤에 호출된다)
            $.ajax({
                url: rotationLimitUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val(), rotation_limit: pendingLimit },
                success: function(){
                    savedRotationLimit = limitValue;
                    $limitIn.blur();
                    startRotate();
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '로테이션 횟수 저장에 실패해 시작하지 않았습니다.'); }
            });
        });

        // 로테이션 되돌리기: 현재 턴을 한 단계 이전으로 복구 (실수로 한 번 더 진행했을 때)
        $('#rotateBackBtn').on('click', async function(){
            if(!(await salert({html: '로테이션을 <b>이전 턴으로 되돌리시겠습니까?</b><br>실수로 한 번 더 진행한 경우에만 사용하세요.'}))) return;

            $.ajax({
                url: rotateBackUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 투표 시작: 행사 상태를 투표중(V)으로
        $('#finishBtn').on('click', async function(){
            if(!(await salert({html: '매칭을 시작하시겠습니까?<br>회원들이 서로에게 투표(선택)할 수 있게 됩니다.'}))) return;

            $.ajax({
                url: finishUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 투표 마감(수동): 행사 상태를 종료(X)로
        $('#closeVotingBtn').on('click', async function(){
            // 1:1은 자동 마감이 없고 홈에서 선택을 받으므로 문구를 분기한다.
            let confirmHtml = isOneToOne
                ? '매칭을 종료하시겠습니까?<br>종료 시점의 선택으로 매칭 결과가 확정·공개되며,<br>회원은 더 이상 선택을 수정할 수 없습니다.'
                : '그대로 두면 <b>행사 다음 날 오전 9시</b>에 자동으로 마감돼요.<br>지금 바로 매칭을 종료하시겠습니까?<br>종료 후에는 회원이 선택을 수정할 수 없습니다.';
            if(!(await salert({html: confirmHtml}))) return;

            $.ajax({
                url: closeVotingUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 종료 되돌리기(reopen): 종료(X)된 행사를 종료 직전 상태로 강제 복구.
        // 와인파티 → 매칭중(V), 1:1 → 진행(S). 참가자 화면도 실시간으로 재개된다.
        $('#reopenBtn').on('click', async function(){
            var backLabel = isOneToOne ? '진행(매칭 선택 재개)' : '매칭중(매칭 선택 재개)';
            if(!(await salert({html: '종료를 되돌려 <b>' + backLabel + '</b> 상태로 복구하시겠습니까?<br>참가자 화면에서 매칭 선택이 다시 열립니다.'}))) return;

            $.ajax({
                url: reopenUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 매칭 시작 취소(cancelVoting): 매칭중(V)→진행(S). 기존 투표(매칭 데이터)는 그대로 유지한다.
        $('#cancelVotingBtn').on('click', async function(){
            if(!(await salert({html: '매칭 시작을 취소하고 <b>진행</b> 상태로 되돌리시겠습니까?<br>기존 투표(매칭 데이터)는 그대로 유지되며, 다시 매칭을 시작하면 이어서 선택할 수 있어요.'}))) return;

            $.ajax({
                url: cancelVotingUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    applyStatusFromResponse(res);
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 선택된 행사 id를 URL 쿼리파라미터에 반영 (공유 가능 URL)
        function syncUrl(){
            let eventId = $('#eventSelect').val();
            let url = new URL(window.location.href);
            if(eventId){ url.searchParams.set('eventId', eventId); }
            else { url.searchParams.delete('eventId'); }
            window.history.replaceState({}, '', url);
        }

        // 턴 네비게이션: 이전/다음 턴 보기 (1턴 ~ 최신 턴 범위, 0턴은 세팅용이라 제외)
        $('#roundPrev').on('click', function(){
            if(viewRound === null){ viewRound = currentRound; }
            let minRound = Math.min(1, currentRound);
            if(viewRound > minRound){ viewRound -= 1; loadData(); }
        });
        $('#roundNext').on('click', function(){
            if(viewRound === null){ viewRound = currentRound; }
            if(viewRound < currentRound){ viewRound += 1; loadData(); }
        });

        // #eventSelect 는 화면에서는 숨겨져 있다. (행사 전환은 상단 탭바가 페이지 이동으로 처리)
        // 다만 값을 스크립트에서 바꿀 수 있는 경로를 남겨 두기 위해 change 핸들러는 유지한다.
        $('#eventSelect').change(function(){ viewRound = null; syncUrl(); loadData(); });

        // 최초 로딩: URL에 eventId가 있으면 그 행사를 선택
        (function(){
            let paramEventId = new URL(window.location.href).searchParams.get('eventId');
            if(paramEventId && $('#eventSelect option[value="' + paramEventId + '"]').length){
                $('#eventSelect').val(paramEventId);
            }
            syncUrl();
            loadData();
        })();
    });
</script>

@if ($currentEvent)
    @include('admin.rotation._memberDetailModal', ['event' => $currentEvent, 'isSuperAdmin' => $isSuperAdmin])
@endif
@endsection
