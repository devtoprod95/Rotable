@extends('layouts.app')

@section('title', '체크인 — ' . $event->title)

@section('content')
<style>
    /* 현장(모바일) 사용 최적화 — 큰 탭 영역, 또렷한 상태색 */
    .checkin-row{ transition: background .12s ease; cursor:pointer; padding-top:.85rem; padding-bottom:.85rem; }
    .checkin-row:active{ background:#f1f5f9; }
    .checkin-row:focus-visible{ outline:3px solid #3b82f6; outline-offset:-3px; }
    .checkin-in{ background:#ecfdf5 !important; border-left:4px solid #34d399; }
    .checkin-out{ background:#ffffff; border-left:4px solid transparent; }

    /* 상단 현황 */
    .ck-head .ck-updated{ white-space:nowrap; }
    .ck-stats{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.5rem; }
    @media (min-width: 768px){ .ck-stats{ grid-template-columns:repeat(4, minmax(0, 1fr)); } }
    .ck-stat-tile{ border-radius:12px; padding:.6rem .75rem; background:#f8f9fa; border-left:4px solid #adb5bd; }
    .ck-stat-tile .ck-stat-name{ font-size:.8rem; font-weight:700; color:#495057; }
    .ck-stat-tile .ck-stat-num{ font-size:1.9rem; font-weight:800; line-height:1.15; }
    .ck-stat-tile .ck-stat-desc{ font-size:.75rem; color:#868e96; }
    .ck-stat-tile.is-total{ border-left-color:#495057; }
    .ck-stat-tile.is-in{ border-left-color:#2fb344; background:#ebf9ee; } .ck-stat-tile.is-in .ck-stat-num{ color:#2fb344; }
    .ck-stat-tile.is-out{ border-left-color:#adb5bd; } .ck-stat-tile.is-out .ck-stat-num{ color:#6c757d; }
    .ck-stat-tile.is-reg{ border-left-color:#0ca678; background:#e6f7f2; } .ck-stat-tile.is-reg .ck-stat-num{ color:#0ca678; }
    .ck-head-actions{ display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
    .ck-head-actions .btn{ width:100%; min-height:44px; display:inline-flex; align-items:center; justify-content:center; white-space:nowrap; }
    @media (min-width: 768px){
        .ck-head-actions{ grid-template-columns:repeat(2, 1fr); }
    }

    /* 참여자 카드: ① 번호표 · 닉네임/플랫폼/테이블 · 상태  ② 항목명:값 정보  ③ 버튼 */
    .checkin-row{ display:block; padding:.85rem 1rem; }
    .checkin-row .ck-top{ display:flex; align-items:center; gap:.75rem; }
    .checkin-row .ck-ticket{ flex:0 0 auto; width:60px; min-height:52px; border-radius:12px; display:flex; flex-direction:column;
        align-items:center; justify-content:center; line-height:1.1; text-align:center; }
    .checkin-row .ck-ticket .ck-ticket-g{ font-size:.72rem; font-weight:700; opacity:.85; }
    .checkin-row .ck-ticket .ck-ticket-no{ font-size:1.35rem; font-weight:800; }
    .checkin-row .ck-ticket.is-m{ background:#206bc4; color:#fff; }
    .checkin-row .ck-ticket.is-f{ background:#d6336c; color:#fff; }
    .checkin-row .ck-ticket.is-none{ background:#f1f3f5; color:#868e96; font-size:.75rem; font-weight:700; }
    .checkin-row .ck-ident{ flex:1 1 auto; min-width:0; }
    .checkin-row .ck-name{ font-weight:800; font-size:1.08rem; overflow-wrap:anywhere; line-height:1.3; }
    .checkin-row .ck-sub{ display:flex; flex-wrap:wrap; gap:.25rem; margin-top:.2rem; }
    .checkin-row .ck-status{ flex:0 0 auto; text-align:right; }
    .checkin-row .ck-pill{ display:inline-flex; align-items:center; gap:.25rem; border-radius:999px; padding:.3rem .65rem; font-size:.8rem; font-weight:700; white-space:nowrap; }
    .checkin-row .ck-pill.is-out{ background:#f1f3f5; color:#495057; }
    .checkin-row .ck-pill.is-wait{ background:#fff3bf; color:#8a6d00; }
    .checkin-row .ck-pill.is-done{ background:#0ca678; color:#fff; }
    .checkin-row .ck-info{ margin:.6rem 0 0; padding:.5rem .65rem; background:rgba(0,0,0,.025); border-radius:10px; }
    .checkin-row .ck-info > div{ display:flex; gap:.5rem; font-size:.86rem; line-height:1.55; }
    .checkin-row .ck-info dt{ flex:0 0 58px; color:#868e96; font-weight:700; margin:0; }
    .checkin-row .ck-info dd{ flex:1 1 auto; margin:0; color:#343a40; overflow-wrap:anywhere; }
    .checkin-row .ck-actions{ display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.6rem; }
    .checkin-row .ck-actions .btn{ min-height:38px; }
    .checkin-row .ck-actions .btn-checkin-del{ margin-left:auto; }
    /* 남/여 목록 머리: 현황 숫자를 항목명과 함께 3칸으로 */
    .ck-col-stats{ display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.4rem; width:100%; }
    .ck-col-stats > div{ background:rgba(255,255,255,.75); border-radius:10px; padding:.35rem .25rem; text-align:center; }
    .ck-col-stats .ck-stat-label{ font-size:.72rem; color:#495057; font-weight:700; }
    .ck-col-stats .ck-stat-val{ font-size:1.15rem; font-weight:800; line-height:1.2; }

    @media (max-width: 767.98px){
        /* 모바일: 숫자 적당히, 카드 패딩 축소, 버튼은 2열 같은 폭(체크인/취소는 한 줄 전체) */
        .checkin-row{ padding-left:.75rem; padding-right:.75rem; }
        .checkin-row .ck-actions{ display:grid; grid-template-columns:1fr 1fr; }
        .checkin-row .ck-actions .btn{ width:100%; min-height:42px; margin-left:0; }
        .checkin-row .ck-actions .btn-checkin-toggle{ grid-column:1 / -1; }
        .checkin-row .ck-actions.is-odd .btn:last-child{ grid-column:1 / -1; }
    }
</style>

<div class="mt-2">
    <x-event-tabs :event-id="$event->id" active="checkin" />
</div>

<div class="row row-cards">
    {{-- 상단: 현황 · 바로가기 버튼 (행사명·타입·일시는 위 탭바에 있으므로 여기서는 보여주지 않는다) --}}
    <div class="col-12">
        <div class="card ck-head">
            {{-- 영역 이름 + 마지막 갱신 시각 (다른 카드와 같은 헤더 형식) --}}
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-chart-bar me-1"></i> 체크인 현황</h3>
                <span class="text-secondary small ck-updated" id="lastUpdated" aria-live="polite"></span>
            </div>
            <div class="card-body">
                {{-- 현황: 항목명 · 큰 숫자 · 보조 설명 (모바일 2×2 / PC 4칸) --}}
                <div class="ck-stats" aria-live="polite">
                    <div class="ck-stat-tile is-total">
                        <div class="ck-stat-name">신청 명단</div>
                        <div class="ck-stat-num" id="statTotal">0</div>
                        <div class="ck-stat-desc">체크인 명단 전체</div>
                    </div>
                    <div class="ck-stat-tile is-in">
                        <div class="ck-stat-name">참석</div>
                        <div class="ck-stat-num" id="statIn">0</div>
                        <div class="ck-stat-desc">참석률 <span id="statInRate">0</span>%</div>
                    </div>
                    <div class="ck-stat-tile is-out">
                        <div class="ck-stat-name">미도착</div>
                        <div class="ck-stat-num" id="statOut">0</div>
                        <div class="ck-stat-desc">아직 체크인 전</div>
                    </div>
                    <div class="ck-stat-tile is-reg">
                        <div class="ck-stat-name">입장등록</div>
                        <div class="ck-stat-num" id="statReg">0</div>
                        <div class="ck-stat-desc">참석 중 <span id="statRegWait">0</span>명 대기</div>
                    </div>
                </div>
                <div class="progress progress-sm mt-2" role="progressbar" aria-label="참석 진행률">
                    <div class="progress-bar bg-success" id="statInBar" style="width:0%"></div>
                </div>

                {{-- 바로가기 버튼: 같은 크기 2칸 (입장 QR 은 위 탭바로 옮김) --}}
                <div class="ck-head-actions mt-3">
                    <button type="button" class="btn btn-outline-pink" id="rosterImageBtn">
                        <i class="ti ti-photo-down me-1"></i> 참가자 명단 이미지
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="checkinRefreshBtn">
                        <i class="ti ti-refresh me-1"></i><span>새로고침</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 명단 추가 (한 명씩 입력) --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2" role="button" tabindex="0"
                 data-bs-toggle="collapse" data-bs-target="#addRosterCollapse" aria-expanded="true" aria-controls="addRosterCollapse" style="cursor:pointer;">
                <h3 class="card-title mb-0"><i class="ti ti-user-plus me-1"></i> 체크인 명단 추가 <span class="text-secondary small fw-normal ms-1">탭하여 접기/펼치기</span></h3>
                <span class="badge bg-azure-lt"><i class="ti ti-info-circle me-1"></i> 전화번호 없이 닉네임·플랫폼·성별만 관리해요</span>
            </div>
            <div class="collapse show" id="addRosterCollapse">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="addName" class="form-label mb-1">닉네임</label>
                        <input type="text" id="addName" class="form-control" placeholder="플랫폼에 등록된 닉네임" maxlength="50" autocomplete="off">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label mb-1 d-block">플랫폼</label>
                        <div class="btn-group w-100" role="group" aria-label="플랫폼 선택">
                            @foreach ($platformOptions as $opt)
                                <input type="radio" class="btn-check" name="addPlatform" id="addPlatform{{ ucfirst($opt['id']) }}"
                                       value="{{ $opt['id'] }}" autocomplete="off" {{ $loop->first ? 'checked' : '' }}>
                                <label class="btn btn-outline-orange" for="addPlatform{{ ucfirst($opt['id']) }}">{{ $opt['label'] }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label mb-1 d-block">성별</label>
                        <div class="btn-group w-100" role="group" aria-label="성별 선택">
                            <input type="radio" class="btn-check" name="addGender" id="addGenderM" value="M" autocomplete="off" checked>
                            <label class="btn btn-outline-blue" for="addGenderM">남</label>
                            <input type="radio" class="btn-check" name="addGender" id="addGenderF" value="F" autocomplete="off">
                            <label class="btn btn-outline-pink" for="addGenderF">여</label>
                        </div>
                    </div>
                </div>
                {{-- 상세페이지 명단 자동 채움용 프로필 (필수 입력) --}}
                <div class="row g-2 align-items-end mt-1">
                    <div class="col-4 col-md-2">
                        <label for="addBirthYear" class="form-label mb-1 required">년생</label>
                        <input type="text" id="addBirthYear" class="form-control" placeholder="97" maxlength="4" inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                    </div>
                    <div class="col-4 col-md-2">
                        <label for="addHeight" class="form-label mb-1 required">키 <span class="text-secondary small">(cm)</span></label>
                        <input type="text" id="addHeight" class="form-control" placeholder="175" maxlength="3" inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                    </div>
                    <div class="col-4 col-md-3">
                        <label for="addJob" class="form-label mb-1 required">직업</label>
                        <input type="text" id="addJob" class="form-control" placeholder="예: 개발자" maxlength="50" autocomplete="off">
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="addMerit" class="form-label mb-1 required">장점</label>
                        <input type="text" id="addMerit" class="form-control" placeholder="예: 잘 웃고 배려심이 깊어요" maxlength="100" autocomplete="off">
                    </div>
                    <div class="col-12 text-secondary small">
                        <i class="ti ti-info-circle me-1"></i>필수 입력 · 상세페이지 참여자 명단을 자동으로 채울 때 사용돼요. (년생은 2자리로 입력 가능)
                    </div>
                </div>
                {{-- 추가 버튼은 모든 입력(닉네임·플랫폼·성별·년생·키·직업) 아래에 둔다 --}}
                <div class="row g-2 mt-2">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary w-100" id="checkinAddBtn">
                            <i class="ti ti-plus me-1"></i> 추가
                        </button>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div id="checkinSearchHint" class="rounded border bg-white px-3 py-2" style="font-size:.88rem; line-height:1.7; color:#495057;">
            <div><i class="ti ti-hand-finger me-1"></i>카드나 <b>체크인</b> 버튼을 누르면 번호표 입력창이 떠요.</div>
            <div><i class="ti ti-point me-1"></i>번호를 입력하면 그 번호로, <b>비워두면 자동 배정</b>돼요.</div>
            <div><i class="ti ti-point me-1"></i>상태: <b>미도착</b> → <b>입장등록 대기</b>(체크인 완료) → <b>입장등록 완료</b></div>
        </div>
    </div>

    {{-- 남 / 여 구분 리스트 --}}
    <div class="col-12">
        <div class="row row-cards">
            {{-- 남 --}}
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header bg-blue-lt flex-column align-items-stretch gap-2">
                        <h3 class="card-title mb-0">👨 남 <span class="text-secondary fw-normal fs-5">· 명단 <span id="maleTotal">0</span>명</span></h3>
                        <div class="ck-col-stats">
                            <div><div class="ck-stat-label">참석</div><div class="ck-stat-val text-blue"><span id="maleIn">0</span></div></div>
                            <div><div class="ck-stat-label">입장등록</div><div class="ck-stat-val text-teal"><span id="maleReg">0</span></div></div>
                            <div><div class="ck-stat-label">미도착</div><div class="ck-stat-val text-secondary"><span id="maleOut">0</span></div></div>
                        </div>
                    </div>
                    <div class="list-group list-group-flush" id="maleList" aria-label="남성 참가자 체크인 목록"></div>
                    <div class="text-center text-secondary py-4" id="maleEmpty">참가자가 없습니다.</div>
                </div>
            </div>
            {{-- 여 --}}
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header bg-pink-lt flex-column align-items-stretch gap-2">
                        <h3 class="card-title mb-0">👩 여 <span class="text-secondary fw-normal fs-5">· 명단 <span id="femaleTotal">0</span>명</span></h3>
                        <div class="ck-col-stats">
                            <div><div class="ck-stat-label">참석</div><div class="ck-stat-val text-pink"><span id="femaleIn">0</span></div></div>
                            <div><div class="ck-stat-label">입장등록</div><div class="ck-stat-val text-teal"><span id="femaleReg">0</span></div></div>
                            <div><div class="ck-stat-label">미도착</div><div class="ck-stat-val text-secondary"><span id="femaleOut">0</span></div></div>
                        </div>
                    </div>
                    <div class="list-group list-group-flush" id="femaleList" aria-label="여성 참가자 체크인 목록"></div>
                    <div class="text-center text-secondary py-4" id="femaleEmpty">참가자가 없습니다.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- 개발 도구 (더미/보정) — 운영 화면에서는 접어 둔다 --}}
    @if (! empty($dummyMode) || ! empty($devMode))
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2" role="button" tabindex="0" data-bs-toggle="collapse" data-bs-target="#devToolsCollapse"
                 aria-expanded="false" aria-controls="devToolsCollapse" style="cursor:pointer;">
                <h3 class="card-title mb-0"><i class="ti ti-tool me-1"></i> 개발 도구 <span class="text-secondary small fw-normal ms-1">탭하여 펼치기</span></h3>
            </div>
            <div class="collapse" id="devToolsCollapse">
                <div class="card-body d-flex flex-column flex-md-row flex-md-wrap gap-2">
                    {{-- 더미 데이터 도구: 최고관리자 + 운영(production) 환경이 아닐 때만 --}}
                    @if (! empty($dummyMode))
                        <button type="button" class="btn btn-outline-warning" id="checkinDevRosterBtn"
                                title="체크인 명단에 더미 신청자를 추가합니다 (개발용)">
                            <i class="ti ti-user-plus me-1"></i><span>더미 명단 추가</span>
                        </button>
                        <button type="button" class="btn btn-outline-warning" id="checkinDevCheckInBtn"
                                title="미도착인 사람 전원을 번호표·테이블 자동으로 체크인합니다 (개발용)">
                            <i class="ti ti-checks me-1"></i><span>더미 체크인</span>
                        </button>
                        <button type="button" class="btn btn-outline-warning" id="checkinDevSeedBtn"
                                title="참석(체크인)한 사람 기준으로 입장등록 더미 데이터를 생성합니다 (개발용)">
                            <i class="ti ti-flask me-1"></i><span>더미 입장등록</span>
                        </button>
                    @endif
                    @if (! empty($devMode))
                        <button type="button" class="btn btn-outline-azure" id="checkinSyncTablesBtn"
                                title="이미 입장등록된 좌석(테이블)을 체크인 명단에 역동기화합니다 (기존 데이터 보정용)">
                            <i class="ti ti-refresh-dot me-1"></i><span>기존 테이블 동기화</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    $(document).ready(function(){
        var listUrl   = "{{ route('admin.rotation.event.rosters.list', ['id' => $event->id]) }}";
        var checkInTpl = "{{ route('admin.rotation.event.rosters.checkIn', ['id' => $event->id, 'rosterId' => '__RID__']) }}";
        var deleteUrl = "{{ route('admin.rotation.event.rosters.delete', ['id' => $event->id]) }}";
        var addUrl    = "{{ route('admin.rotation.event.rosters.add', ['id' => $event->id]) }}";
        var updateTpl = "{{ route('admin.rotation.event.rosters.update', ['id' => $event->id, 'rosterId' => '__RID__']) }}";
        var ticketTpl = "{{ route('admin.rotation.event.rosters.ticket', ['id' => $event->id, 'rosterId' => '__RID__']) }}";
        var devSeedUrl = "{{ route('admin.rotation.event.rosters.devSeed', ['id' => $event->id]) }}";
        var devRosterUrl = "{{ route('admin.rotation.event.rosters.devSeedRosters', ['id' => $event->id]) }}";
        var devCheckInUrl = "{{ route('admin.rotation.event.rosters.devCheckIn', ['id' => $event->id]) }}";
        var syncTablesUrl = "{{ route('admin.rotation.event.rosters.syncTables', ['id' => $event->id]) }}";

        // 체크인 명단 추가 영역: 기본은 펼침. 사용자가 접으면 그 상태를 기억해 새로고침 후에도 접어 둔다.
        (function(){
            var KEY = 'rotable.checkin.addRosterCollapsed';
            var el = document.getElementById('addRosterCollapse');
            if(!el){ return; }
            var collapsed = false;
            try { collapsed = localStorage.getItem(KEY) === '1'; } catch(e) { /* 저장소 사용 불가 시 기본(펼침) */ }
            if(collapsed){
                el.classList.remove('show');
                $('[data-bs-target="#addRosterCollapse"]').attr('aria-expanded', 'false');
            }
            function save(v){ try { localStorage.setItem(KEY, v ? '1' : '0'); } catch(e) {} }
            el.addEventListener('hidden.bs.collapse', function(e){ if(e.target === el){ save(true); } });
            el.addEventListener('shown.bs.collapse', function(e){ if(e.target === el){ save(false); } });
        })();

        var rosters = [];
        var tableRatios = [];  // 테이블별 성비 (load 때 갱신, 번호표 모달에서도 재사용)
        // 이 행사의 테이블 목록 (체크인 때 테이블 지정용)
        var TABLES = @json($tables->map(fn($t) => ['id' => $t->id, 'table_no' => $t->table_no])->values());
        // 1:1 소개팅 여부 — true 면 테이블당 같은 성별 1명 제약(이미 찬 테이블은 드롭다운에서 숨김)
        var IS_ONE_TO_ONE = @json($event->isOneToOne());

        // 플랫폼 표시 라벨/색상 (문토/프립)
        var PLATFORM_LABEL = @json(collect($platformOptions)->pluck('label', 'id'));
        var PLATFORM_BADGE = { munto: 'bg-orange-lt', frip: 'bg-cyan-lt' };
        var TICKET_MAX = {{ \App\Services\Rotation\RosterService::TICKET_MAX }};

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }
        function genderText(g){ return g === 'F' ? '여' : '남'; }
        function findRoster(id){ return rosters.find(function(x){ return String(x.id) === String(id); }); }

        // 자동 배정 시 받게 될 번호(예상): 그 성별에서 쓰이지 않은 가장 작은 번호.
        // 서버가 최종 결정하며(이미 입장등록한 번호도 피함), 실제 배정 결과는 처리 후 알려준다.
        function nextTicketHint(gender){
            var used = {};
            rosters.forEach(function(x){
                if((x.gender === 'F') === (gender === 'F') && x.ticket_no){ used[x.ticket_no] = true; }
            });
            var n = 1;
            while(used[n]){ n++; }
            return n;
        }

        function renderStats(){
            var total = rosters.length;
            var inCnt = rosters.filter(function(r){ return r.checked_in; }).length;
            var regCnt = rosters.filter(function(r){ return r.registered; }).length;
            var rate = total > 0 ? Math.round(inCnt * 100 / total) : 0;
            $('#statTotal').text(total);
            $('#statIn').text(inCnt);
            $('#statOut').text(total - inCnt);
            $('#statReg').text(regCnt);
            $('#statInRate').text(rate);
            $('#statInBar').css('width', rate + '%');
            // 입장등록 대기 = 참석했지만 아직 입장등록 전
            $('#statRegWait').text(rosters.filter(function(r){ return r.checked_in && !r.registered; }).length);
        }

        // 정렬: ① 미도착(아직 체크인 안 한 사람) 먼저 → ② 번호표 있는 사람 먼저(번호 순) → ③ 닉네임 순
        function byTicketThenName(a, b){
            // ① 미도착 우선 (checked_in=false 가 위로)
            var ar = a.checked_in ? 1 : 0;
            var br = b.checked_in ? 1 : 0;
            if(ar !== br){ return ar - br; }
            // ② 번호표 순 (미배정은 뒤)
            var ta = a.ticket_no == null ? Infinity : a.ticket_no;
            var tb = b.ticket_no == null ? Infinity : b.ticket_no;
            if(ta !== tb){ return ta < tb ? -1 : 1; }
            // ③ 닉네임 순
            return (a.name || '').localeCompare(b.name || '', 'ko');
        }

        // 참여자 카드 1장. 행 전체를 탭하면 체크인(번호표 입력/자동) 또는 취소, 아래 버튼으로 프로필/번호·테이블/삭제.
        function hhmm(v){ return v ? String(v).slice(-5) : ''; }
        function row(r){
            var inState = r.checked_in;
            var stateCls = inState ? 'checkin-in' : 'checkin-out';
            var nickname = r.name || '닉네임 없음';
            var name = r.name ? esc(r.name) : '<span class="text-secondary">닉네임 없음</span>';
            var pfLabel = PLATFORM_LABEL[r.platform] || r.platform || '';
            var pf = pfLabel
                ? `<span class="badge ${PLATFORM_BADGE[r.platform] || 'bg-secondary-lt'}">${esc(pfLabel)}</span>` : '';

            // ① 번호표 박스 (성별 색)
            var ticket = r.ticket_no
                ? `<div class="ck-ticket ${r.gender === 'F' ? 'is-f' : 'is-m'}" aria-label="${genderText(r.gender)} ${r.ticket_no}번">`
                  + `<span class="ck-ticket-g">${genderText(r.gender)}</span><span class="ck-ticket-no">${r.ticket_no}</span></div>`
                : `<div class="ck-ticket is-none">번호<br>미배정</div>`;

            // 테이블 (체크인한 사람만. 미지정이면 입장등록이 막히므로 경고)
            var tableBadge = '';
            if(inState){
                tableBadge = r.rotation_table_id
                    ? `<span class="badge bg-azure-lt"><i class="ti ti-armchair me-1"></i>${tableNoById(r.rotation_table_id)}번 테이블</span>`
                    : `<span class="badge bg-red-lt"><i class="ti ti-alert-triangle me-1"></i>테이블 미지정</span>`;
            }

            // 상태: 미도착 → 참석(입장등록 대기) → 입장등록 완료
            var status = !inState
                ? '<span class="ck-pill is-out"><i class="ti ti-clock"></i>미도착</span>'
                : (r.registered
                    ? '<span class="ck-pill is-done"><i class="ti ti-user-check"></i>입장등록 완료</span>'
                    : '<span class="ck-pill is-wait"><i class="ti ti-hourglass"></i>입장등록 대기</span>');

            // ② 정보: 항목명 : 값
            var prof = [];
            if(r.birth_year){ prof.push(String(r.birth_year).slice(-2) + '년생'); }
            if(r.height){ prof.push(r.height + 'cm'); }
            if(r.job){ prof.push(esc(r.job)); }
            var info = `<div><dt>프로필</dt><dd>${prof.length ? prof.join(' · ') : '<span class="text-danger">미입력</span>'}</dd></div>`;
            if(r.merit){ info += `<div><dt>장점</dt><dd>${esc(r.merit)}</dd></div>`; }
            var times = [];
            if(inState && r.checked_in_at){ times.push('체크인 ' + esc(hhmm(r.checked_in_at))); }
            if(r.registered && r.registered_at){ times.push('입장등록 ' + esc(hhmm(r.registered_at))); }
            if(times.length){ info += `<div><dt>시각</dt><dd>${times.join(' · ')}</dd></div>`; }

            // ③ 버튼: 체크인/취소(주요) · 프로필 · 번호·테이블(체크인 후, 입장등록 전) · 삭제(미도착일 때만)
            // 입장등록을 마친 사람은 체크인 취소가 막혀 있으므로(서버 거부) 버튼을 보이지 않는다.
            var toggleBtn = (inState && r.registered) ? '' : inState
                ? `<button type="button" class="btn btn-sm btn-outline-warning btn-checkin-toggle" data-id="${r.id}"`
                  + ` aria-label="${esc(nickname)} 체크인 취소"><i class="ti ti-arrow-back-up me-1"></i>체크인 취소</button>`
                : `<button type="button" class="btn btn-sm btn-success btn-checkin-toggle" data-id="${r.id}"`
                  + ` aria-label="${esc(nickname)} 체크인"><i class="ti ti-check me-1"></i>체크인</button>`;
            var profBtn = `<button type="button" class="btn btn-sm btn-outline-secondary btn-checkin-profile"`
                  + ` data-id="${r.id}" aria-label="${esc(nickname)} 프로필 입력·수정"><i class="ti ti-id me-1"></i>프로필</button>`;
            var ticketBtn = (inState && !r.registered)
                ? `<button type="button" class="btn btn-sm btn-outline-primary btn-checkin-ticket"`
                  + ` data-id="${r.id}" aria-label="${esc(nickname)} 번호표 변경"><i class="ti ti-edit me-1"></i>번호·테이블</button>` : '';
            var delBtn = inState ? ''
                : `<button type="button" class="btn btn-sm btn-outline-danger btn-checkin-del"`
                  + ` data-id="${r.id}" data-name="${esc(nickname)}" aria-label="${esc(nickname)} 명단에서 삭제">`
                  + `<i class="ti ti-trash me-1"></i>삭제</button>`;

            // 모바일 2열 버튼 줄: 보조 버튼(프로필·번호·테이블·삭제)이 홀수면 마지막 버튼을 한 줄 전체로 (혼자 반쪽 차지 방지)
            var secondaryCount = [profBtn, ticketBtn, delBtn].filter(function(b){ return b; }).length;

            return `<div class="list-group-item checkin-row ${stateCls}"`
                +      ` role="button" tabindex="0" data-id="${r.id}"`
                +      ` aria-pressed="${inState ? 'true' : 'false'}"`
                +      ` aria-label="${esc(nickname)} ${esc(pfLabel)} ${inState ? '참석됨, 탭하면 취소' : '미도착, 탭하면 체크인'}">`
                +   `<div class="ck-top">${ticket}`
                +     `<div class="ck-ident"><div class="ck-name">${name}</div><div class="ck-sub">${pf}${tableBadge}</div></div>`
                +     `<div class="ck-status">${status}</div>`
                +   `</div>`
                +   `<dl class="ck-info">${info}</dl>`
                +   `<div class="ck-actions${secondaryCount % 2 === 1 ? ' is-odd' : ''}">${toggleBtn}${profBtn}${ticketBtn}${delBtn}</div>`
                + `</div>`;
        }

        function fillColumn(listSel, emptySel, inSel, totalSel, regSel, rows){
            var inCnt = rows.filter(function(r){ return r.checked_in; }).length;
            var regCnt = rows.filter(function(r){ return r.registered; }).length;
            // 미도착 칸: "#maleIn" → "#maleOut"
            $(inSel.replace(/In$/, 'Out')).text(rows.length - inCnt);
            $(inSel).text(inCnt);
            $(regSel).text(regCnt);
            $(totalSel).text(rows.length);
            if(rows.length === 0){
                $(listSel).empty();
                $(emptySel).show();
            } else {
                $(emptySel).hide();
                $(listSel).html(rows.slice().sort(byTicketThenName).map(row).join(''));
            }
        }

        function render(){
            var rows = rosters;
            renderStats();

            var males   = rows.filter(function(r){ return r.gender !== 'F'; }); // 성별은 M/F 필수, 그 외는 남으로 귀속
            var females = rows.filter(function(r){ return r.gender === 'F'; });

            fillColumn('#maleList',   '#maleEmpty',   '#maleIn',   '#maleTotal',   '#maleReg',   males);
            fillColumn('#femaleList', '#femaleEmpty', '#femaleIn', '#femaleTotal', '#femaleReg', females);
        }

        // 목록 조회. 동시에 여러 번 요청되지 않도록 진행 중이면 건너뛴다.
        // 처음 열 때와 [새로고침] 버튼에서 쓰고, 마지막 갱신 시각과 실패 여부를 화면에 표시한다.
        var loading = false;
        function pad(n){ return (n < 10 ? '0' : '') + n; }
        // 테이블별 성비 보관. (번호표 모달의 ratioModalHtml 에서 사용 — 화면에는 따로 그리지 않는다)
        function renderTableRatios(ratios){
            tableRatios = ratios || [];
        }

        // 번호표 모달 등에 끼워 넣을 "테이블별 성비" 요약 HTML. (테이블이 없으면 빈 문자열)
        function ratioModalHtml(){
            if(!tableRatios.length){ return ''; }
            var cells = tableRatios.map(function(r){
                var m = r.male || 0, f = r.female || 0, total = r.total || 0;
                var balanced = (m === f);
                var headBg = total === 0 ? '#94a3b8' : (balanced ? '#16a34a' : '#f59e0b');
                return '<div style="border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(20,30,60,.12);min-width:92px;">'
                     +   '<div style="background:' + headBg + ';color:#fff;font-weight:800;font-size:0.82rem;padding:3px 6px;">' + r.table_no + '번</div>'
                     +   '<div style="display:flex;">'
                     +     '<div style="flex:1;background:#eff6ff;padding:5px 2px;"><span style="color:#2563eb;font-weight:800;font-size:1.05rem;">' + m + '</span><div style="font-size:0.65rem;color:#2563eb;">남</div></div>'
                     +     '<div style="flex:1;background:#fdf2f8;padding:5px 2px;"><span style="color:#db2777;font-weight:800;font-size:1.05rem;">' + f + '</span><div style="font-size:0.65rem;color:#db2777;">여</div></div>'
                     +   '</div>'
                     + '</div>';
            }).join('');
            return '<div style="margin-top:14px;">'
                 +   '<div style="font-size:0.82rem;color:#64748b;margin-bottom:8px;">📊 현재 테이블별 성비 (자리 안내 참고)</div>'
                 +   '<div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">' + cells + '</div>'
                 + '</div>';
        }

        function load(){
            if(loading){ return; }
            loading = true;
            var $btn = $('#checkinRefreshBtn');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $.get(listUrl, function(res){
                rosters = (res && res.data) ? res.data : [];
                render();
                renderTableRatios((res && res.table_ratios) ? res.table_ratios : []);
                var d = new Date();
                $('#lastUpdated').removeClass('text-danger').addClass('text-secondary')
                    .text('마지막 갱신 ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()));
            }).fail(function(){
                $('#lastUpdated').removeClass('text-secondary').addClass('text-danger')
                    .text('갱신 실패 — 새로고침을 눌러 주세요');
            }).always(function(){
                loading = false;
                $btn.prop('disabled', false).html('<i class="ti ti-refresh me-1"></i><span>새로고침</span>');
            });
        }

        // 수동 새로고침 버튼
        $('#checkinRefreshBtn').on('click', load);

        // [개발용] 더미 입장등록: 참석(체크인)한 사람 기준으로 회원·좌석을 생성한다.
        $('#checkinDevSeedBtn').on('click', async function(){
            var inCnt = rosters.filter(function(r){ return r.checked_in; }).length;
            if(inCnt === 0){
                await salert({ text: '참석(체크인)한 사람이 없습니다. 먼저 체크인을 진행해 주세요.', cancel: false });
                return;
            }
            var ok = await Swal.fire({
                icon: 'question',
                title: '더미 입장등록 생성',
                html: '현재 <b>참석(체크인) ' + inCnt + '명</b> 기준으로<br>입장등록(회원) 더미 데이터를 생성할까요?'
                    + '<div class="text-secondary small mt-1">이미 입장등록된 번호는 건너뜁니다. (개발용)</div>',
                showCancelButton: true, reverseButtons: true,
                confirmButtonText: '생성', cancelButtonText: '취소',
                confirmButtonColor: '#3085d6', cancelButtonColor: '#d33'
            });
            if(!ok.isConfirmed){ return; }

            var $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $.ajax({
                url: devSeedUrl, type: 'POST',
                success: function(res){
                    Swal.fire({
                        icon: 'success',
                        title: '완료',
                        html: '생성 <b>' + (res?.created ?? 0) + '건</b>, 건너뜀 ' + (res?.skipped ?? 0) + '건',
                        confirmButtonText: '확인', confirmButtonColor: '#3085d6'
                    });
                    load();
                },
                error: function(xhr){ showError(xhr, '더미 입장등록 생성에 실패했습니다.'); }
            }).always(function(){
                $btn.prop('disabled', false).html('<i class="ti ti-flask me-1"></i><span>더미 입장등록</span>');
            });
        });

        // [개발용] 더미 명단 추가: 남/여 인원을 입력받아 체크인 명단에 더미 신청자를 만든다.
        //  기본값은 테이블 수 기준 (1:1 = 테이블당 1명, 와인 = 테이블당 3명)
        $('#checkinDevRosterBtn').on('click', async function(){
            var per = IS_ONE_TO_ONE ? 1 : 3;
            var def = Math.max(1, TABLES.length * per);
            var numInput = function(id, label, color){
                return '<div style="flex:1;text-align:left;">'
                     +   '<label for="' + id + '" style="font-size:0.85rem;font-weight:700;color:' + color + ';display:block;margin-bottom:4px;">' + label + '</label>'
                     +   '<input id="' + id + '" type="number" class="swal2-input" value="' + def + '" min="0" max="100" inputmode="numeric" style="width:100%;margin:0;box-sizing:border-box;">'
                     + '</div>';
            };
            var ans = await Swal.fire({
                title: '더미 명단 추가',
                html: '<div style="display:flex;gap:10px;">' + numInput('swal-dev-m', '👨 남성', '#1d4ed8') + numInput('swal-dev-f', '👩 여성', '#be185d') + '</div>'
                    + '<div class="text-secondary small mt-2">닉네임은 더미남N / 더미여N 으로 만들어져요. (개발용)</div>',
                showCancelButton: true, reverseButtons: true, focusConfirm: true,
                confirmButtonText: '추가', cancelButtonText: '취소',
                confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                preConfirm: function(){
                    var m = parseInt(document.getElementById('swal-dev-m').value || '0', 10);
                    var f = parseInt(document.getElementById('swal-dev-f').value || '0', 10);
                    if(isNaN(m) || isNaN(f) || m < 0 || f < 0 || m > 100 || f > 100){
                        Swal.showValidationMessage('0~100 사이 숫자로 입력해 주세요.');
                        return false;
                    }
                    if(m + f === 0){
                        Swal.showValidationMessage('1명 이상 입력해 주세요.');
                        return false;
                    }
                    return { male: m, female: f };
                }
            });
            if(!ans.isConfirmed || !ans.value){ return; }

            var $btn = $(this);
            var original = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $.ajax({
                url: devRosterUrl, type: 'POST', data: ans.value,
                success: function(res){
                    Swal.fire({ icon: 'success', text: res?.msg || '추가했습니다.', timer: 2000, showConfirmButton: false });
                    load();
                },
                error: function(xhr){ showError(xhr, '더미 명단 추가에 실패했습니다.'); }
            }).always(function(){
                $btn.prop('disabled', false).html(original);
            });
        });

        // [개발용] 더미 체크인: 미도착 전원을 번호표·테이블 자동으로 체크인한다.
        $('#checkinDevCheckInBtn').on('click', async function(){
            var outCnt = rosters.filter(function(r){ return !r.checked_in; }).length;
            if(outCnt === 0){
                await salert({ text: '미도착인 사람이 없습니다.', cancel: false });
                return;
            }
            if(!TABLES.length){
                await salert({ text: '테이블이 없습니다. 행사 상세에서 테이블을 먼저 추가해 주세요.', cancel: false });
                return;
            }
            var ok = await Swal.fire({
                icon: 'question',
                title: '더미 체크인',
                html: '미도착 <b>' + outCnt + '명</b>을 번호표·테이블 자동으로 체크인할까요?'
                    + '<div class="text-secondary small mt-1">' + (IS_ONE_TO_ONE ? '1:1: 1번 테이블부터 차례대로' : '와인파티: 성비가 고르게') + ' 배치돼요. (개발용)</div>',
                showCancelButton: true, reverseButtons: true,
                confirmButtonText: '체크인', cancelButtonText: '취소',
                confirmButtonColor: '#3085d6', cancelButtonColor: '#d33'
            });
            if(!ok.isConfirmed){ return; }

            var $btn = $(this);
            var original = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $.ajax({
                url: devCheckInUrl, type: 'POST',
                success: function(res){
                    Swal.fire({ icon: 'success', text: res?.msg || '체크인했습니다.', confirmButtonText: '확인', confirmButtonColor: '#3085d6' });
                    load();
                },
                error: function(xhr){ showError(xhr, '더미 체크인에 실패했습니다.'); }
            }).always(function(){
                $btn.prop('disabled', false).html(original);
            });
        });

        // [기존 데이터 보정] 좌석(rotation_seats) 테이블을 체크인 명단(event_rosters)에 역동기화
        $('#checkinSyncTablesBtn').on('click', async function(){
            var ok = await Swal.fire({
                icon: 'question',
                title: '기존 테이블 동기화',
                html: '이미 <b>입장등록된 좌석</b>의 테이블 번호를<br>체크인 명단에 채워 넣을까요?'
                    + '<div class="text-secondary small mt-1">명단에 이미 테이블이 지정된 경우는 건너뜁니다.</div>',
                showCancelButton: true, reverseButtons: true,
                confirmButtonText: '동기화', cancelButtonText: '취소',
                confirmButtonColor: '#3085d6', cancelButtonColor: '#d33'
            });
            if(!ok.isConfirmed){ return; }

            var $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $.ajax({
                url: syncTablesUrl, type: 'POST',
                success: function(res){
                    Swal.fire({
                        icon: 'success',
                        title: '동기화 완료',
                        html: '반영 <b>' + (res?.updated ?? 0) + '건</b>, 건너뜀 ' + (res?.skipped ?? 0) + '건',
                        confirmButtonText: '확인', confirmButtonColor: '#3085d6'
                    });
                    load();
                },
                error: function(xhr){ showError(xhr, '테이블 동기화에 실패했습니다.'); }
            }).always(function(){
                $btn.prop('disabled', false).html('<i class="ti ti-refresh-dot me-1"></i><span>기존 테이블 동기화</span>');
            });
        });

        function showError(xhr, fallback){
            return Swal.fire({
                icon: 'error', text: xhr?.responseJSON?.error?.message ?? fallback,
                confirmButtonText: '확인', confirmButtonColor: '#3085d6'
            });
        }

        // 테이블 선택 드롭다운 HTML (현재 성비를 옵션에 함께 표기)
        //  - 1:1 소개팅은 테이블당 같은 성별 1명 제약 → 체크인하는 사람의 성별이 이미 찬 테이블은 숨긴다.
        function tableSelectHtml(selectedId, gender){
            if(!TABLES.length){ return ''; }
            var ratioByNo = {};
            tableRatios.forEach(function(r){ ratioByNo[r.table_no] = r; });
            var opts = ['<option value="">— 테이블을 선택하세요 —</option>'];
            TABLES.forEach(function(t){
                var r = ratioByNo[t.table_no];
                var isSelected = (String(selectedId||'') === String(t.id));
                // 1:1: 같은 성별이 이미 앉은 테이블은 숨긴다. (단, 지금 이 사람이 이미 지정된 자리는 유지)
                if(IS_ONE_TO_ONE && !isSelected && r){
                    var sameGenderCount = (gender === 'F') ? (r.female||0) : (r.male||0);
                    if(sameGenderCount >= 1){ return; }
                }
                var ratioText = r ? ' (남' + (r.male||0) + '·여' + (r.female||0) + ')' : '';
                var sel = isSelected ? ' selected' : '';
                opts.push('<option value="' + t.id + '"' + sel + '>' + t.table_no + '번 테이블' + ratioText + '</option>');
            });
            return '<div style="margin-top:12px;text-align:left;">'
                 +   '<label style="font-size:0.85rem;color:#475569;font-weight:700;display:block;margin-bottom:4px;">앉을 테이블</label>'
                 +   '<select id="swal-table-select" class="swal2-select" style="width:100%;margin:0;">' + opts.join('') + '</select>'
                 + '</div>';
        }

        // 체크인 모달에서 미리 선택해 둘 테이블 (관리자가 바꿀 수 있음)
        //  - 1:1 : 같은 성별이 아직 없는 테이블 중 번호가 가장 작은 곳 (차례대로 채움)
        //  - 와인 : 같은 성별 인원이 가장 적은 테이블 (동률이면 전체 인원 적은 곳 → 번호 작은 곳) → 성비가 고르게 맞춰짐
        function defaultTableId(gender){
            if(!TABLES.length){ return ''; }
            var ratioByNo = {};
            tableRatios.forEach(function(r){ ratioByNo[r.table_no] = r; });
            var isF = (gender === 'F');
            var list = TABLES.map(function(t){
                var r = ratioByNo[t.table_no] || {};
                return { id: t.id, no: +t.table_no, same: (isF ? r.female : r.male) || 0, total: r.total || 0 };
            });
            if(IS_ONE_TO_ONE){
                list = list.filter(function(x){ return x.same === 0; });
                list.sort(function(a, b){ return a.no - b.no; });
            } else {
                list.sort(function(a, b){ return (a.same - b.same) || (a.total - b.total) || (a.no - b.no); });
            }
            return list.length ? list[0].id : '';
        }

        // 체크인 모달 상단에 보여줄 성별 뱃지
        function genderBadgeHtml(gender){
            if(!gender){ return ''; }
            var isF = (gender === 'F');
            return '<div style="margin-bottom:6px;">'
                 +   '<span style="display:inline-block;background:' + (isF ? '#fce7f3' : '#dbeafe') + ';color:' + (isF ? '#be185d' : '#1d4ed8') + ';'
                 +     'font-weight:800;font-size:1rem;padding:5px 14px;border-radius:999px;">'
                 +     (isF ? '👩 여성' : '👨 남성')
                 +   '</span>'
                 + '</div>';
        }

        // 번호표 입력창(숫자) + 테이블 선택. 빈 값 허용 여부와 안내 문구는 호출하는 쪽에서 정한다.
        //  결과값(isConfirmed 시 .value): { ticket: string, tableId: string }
        function askTicket(opts){
            return Swal.fire({
                title: opts.title,
                html: genderBadgeHtml(opts.gender)
                    + (opts.html || '')
                    + '<div style="margin-top:8px;text-align:left;">'
                    +   '<label style="font-size:0.85rem;color:#475569;font-weight:700;display:block;margin-bottom:4px;">'
                    +     '번호표' + (opts.hint ? ' <span style="font-weight:400;color:#64748b;">· ' + opts.hint + '</span>' : '')
                    +   '</label>'
                    +   '<input id="swal-ticket" type="number" class="swal2-input" placeholder="' + (opts.placeholder || '번호 입력') + '" '
                    +     'value="' + (opts.value || '') + '" min="1" max="' + TICKET_MAX + '" step="1" inputmode="numeric" '
                    +     'style="width:100%;margin:0;box-sizing:border-box;">'
                    + '</div>'
                    + tableSelectHtml(opts.tableId, opts.gender)
                    + ratioModalHtml(),
                focusConfirm: true,
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: opts.confirmText,
                cancelButtonText: '취소',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                preConfirm: function(){
                    var ticket = (document.getElementById('swal-ticket')?.value || '').trim();
                    var tableEl = document.getElementById('swal-table-select');
                    var tableId = tableEl ? (tableEl.value || '') : '';
                    if(ticket === '' && !opts.allowEmpty){
                        Swal.showValidationMessage('번호표를 입력해 주세요.');
                        return false;
                    }
                    if(ticket !== '' && (!/^\d+$/.test(ticket) || +ticket < 1 || +ticket > TICKET_MAX)){
                        Swal.showValidationMessage('1~' + TICKET_MAX + ' 사이 숫자로 입력해 주세요.');
                        return false;
                    }
                    // 체크인 시에는 테이블 지정이 필수.
                    if(opts.requireTable && tableId === ''){
                        Swal.showValidationMessage('앉을 테이블을 선택해 주세요.');
                        return false;
                    }
                    return { ticket: ticket, tableId: tableId };
                }
            });
        }

        // 서버에 체크인/취소 요청. ticket 이 있으면 수동(1순위), 없으면 자동 배정(2순위). tableId 선택 지정.
        function sendCheckIn(id, ticket, tableId){
            var $row = $(`.checkin-row[data-id="${id}"]`);
            $row.css('opacity', 0.6);
            var payload = {};
            if(ticket){ payload.ticket_no = ticket; }
            if(tableId){ payload.rotation_table_id = tableId; }
            $.ajax({
                url: checkInTpl.replace('__RID__', id), type: 'PATCH',
                data: payload,
                success: function(res){
                    var r = findRoster(id);
                    if(r && res){
                        r.checked_in = !!res.checked_in;
                        r.checked_in_at = res.checked_in_at || null;
                        r.ticket_no = (res.ticket_no == null) ? null : res.ticket_no;
                        r.rotation_table_id = (res.rotation_table_id == null) ? null : res.rotation_table_id;
                        // 번호표가 새로 배정/회수되면 입장등록 여부는 서버 기준으로 다시 확인된다.
                        r.registered = false;
                        r.registered_at = null;
                    }
                    render();
                    load();  // 성비(테이블별) 즉시 갱신
                    if(r && res && res.checked_in){
                        var how = { manual: '직접 입력', auto: '자동 배정', existing: '기존 번호 유지' }[res.assigned] || '';
                        var tableNo = r.rotation_table_id ? tableNoById(r.rotation_table_id) : null;
                        var nm = esc(r.name || '닉네임 없음');
                        Swal.fire({
                            icon: 'success',
                            title: nm + ' 님 안내',
                            html:
                                '<div style="font-size:1.05rem;line-height:1.7;margin-top:4px;">'
                              +   '<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">'
                              +     '<span style="background:#dbeafe;color:#1d4ed8;font-weight:800;font-size:1.35rem;padding:8px 16px;border-radius:12px;">🎫 ' + res.ticket_no + '번 번호표</span>'
                              +     (tableNo ? '<span style="background:#e0f2fe;color:#0369a1;font-weight:800;font-size:1.35rem;padding:8px 16px;border-radius:12px;">🪑 ' + tableNo + '번 테이블</span>' : '')
                              +   '</div>'
                              +   '<div style="margin-top:12px;font-weight:800;font-size:1.15rem;color:#be185d;">위 자리로 안내해 주세요!</div>'
                              +   (how ? '<div class="text-secondary small mt-2">(번호표 ' + how + ')</div>' : '')
                              + '</div>',
                            confirmButtonText: '확인했어요', confirmButtonColor: '#3085d6',
                            allowOutsideClick: false
                        });
                    }
                },
                error: function(xhr){
                    $row.css('opacity', 1);
                    showError(xhr, '오류가 발생했습니다.');
                }
            });
        }

        // 테이블 id → 번호
        function tableNoById(id){
            var t = TABLES.find(function(x){ return String(x.id) === String(id); });
            return t ? t.table_no : '';
        }

        // 행 탭: 미도착이면 번호표 입력(비우면 자동) 후 체크인, 참석이면 확인 후 취소(번호표 회수)
        async function toggle(id){
            var r = findRoster(id);
            if(!r){ return; }
            var nick = r.name || '닉네임 없음';

            if(r.checked_in){
                // 이미 그 번호표로 입장등록했다면 서버가 취소를 거부한다. 먼저 안내해 헛걸음을 줄인다.
                if(r.registered){
                    Swal.fire({
                        icon: 'info',
                        title: '체크인을 취소할 수 없어요',
                        html: '<b>' + esc(nick) + '</b> 님은 이미 번호표(' + genderText(r.gender) + ' ' + (r.ticket_no || '-') + '번)로 입장등록했어요.',
                        confirmButtonText: '확인', confirmButtonColor: '#3085d6'
                    });
                    return;
                }
                var cancel = await Swal.fire({
                    icon: 'warning',
                    title: '체크인 취소',
                    html: '<b>' + esc(nick) + '</b> 님의 참석을 취소하고<br>번호표(' + genderText(r.gender) + ' ' + (r.ticket_no || '-') + '번)를 회수할까요?',
                    showCancelButton: true, reverseButtons: true,
                    confirmButtonText: '취소하기', cancelButtonText: '닫기',
                    confirmButtonColor: '#d33', cancelButtonColor: '#6c757d'
                });
                if(cancel.isConfirmed){ sendCheckIn(id, null); }
                return;
            }

            var hint = nextTicketHint(r.gender);
            var ans = await askTicket({
                title: esc(nick) + ' 체크인',
                hint: '비우면 자동 배정 <b style="color:#2563eb;">(예상 ' + hint + '번)</b>',
                placeholder: '비우면 자동',
                confirmText: '체크인',
                allowEmpty: true,
                requireTable: true,
                gender: r.gender,
                tableId: r.rotation_table_id || defaultTableId(r.gender)
            });
            if(!ans.isConfirmed || !ans.value){ return; }
            var manual = (ans.value.ticket == null ? '' : String(ans.value.ticket)).trim();
            var tableId = ans.value.tableId || null;
            sendCheckIn(id, manual === '' ? null : parseInt(manual, 10), tableId);
        }

        // 관리자 번호표 변경 (입장등록 전인 사람만. 입장등록을 마친 사람은 버튼이 숨겨진다.)
        async function changeTicket(id){
            var r = findRoster(id);
            if(!r || !r.ticket_no || r.registered){ return; }
            var ans = await askTicket({
                title: esc(r.name || '닉네임 없음') + ' 번호 · 테이블 변경',
                hint: genderText(r.gender) + ' · 현재 ' + r.ticket_no + '번',
                value: r.ticket_no,
                gender: r.gender,
                tableId: r.rotation_table_id || '',
                confirmText: '변경',
                allowEmpty: false
            });
            if(!ans.isConfirmed || !ans.value){ return; }
            var next = parseInt(String(ans.value.ticket || '').trim(), 10);
            if(!next){ return; }
            var nextTable = ans.value.tableId || '';
            // 번호·테이블 둘 다 그대로면 알려만 준다. (예전엔 아무 반응 없이 닫혀 '작동 안 함'처럼 보였음)
            if(next === +r.ticket_no && String(nextTable) === String(r.rotation_table_id || '')){
                Swal.fire({ icon: 'info', text: '변경된 내용이 없어요.', timer: 1800, showConfirmButton: false });
                return;
            }

            var payload = { ticket_no: next };
            if(nextTable){ payload.rotation_table_id = nextTable; }

            $.ajax({
                url: ticketTpl.replace('__RID__', id), type: 'PATCH', data: payload,
                success: function(res){
                    r.ticket_no = (res && res.ticket_no) ? res.ticket_no : next;
                    if(res && res.rotation_table_id !== undefined){ r.rotation_table_id = res.rotation_table_id; }
                    render();
                    load();  // 테이블별 성비 갱신
                    Swal.fire({ icon: 'success', text: res?.msg || '변경했습니다.', timer: 2500, showConfirmButton: false });
                },
                error: function(xhr){ showError(xhr, '번호표를 변경하지 못했습니다.'); }
            });
        }

        // 탭/클릭 토글 (두 리스트 + 미지정 공통, 이벤트 위임)
        $(document).on('click', '.checkin-row', function(){
            toggle($(this).data('id'));
        });
        // 키보드 접근성: Enter/Space 로 토글
        $(document).on('keydown', '.checkin-row', function(e){
            if(e.target !== this){ return; } // 안쪽 버튼의 키 입력은 무시
            if(e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32){
                e.preventDefault();
                toggle($(this).data('id'));
            }
        });

        // 번호 변경 버튼 (행 토글로 전파되지 않도록 stopPropagation)
        $(document).on('click', '.btn-checkin-ticket', function(e){
            e.stopPropagation();
            changeTicket($(this).data('id'));
        });
        $(document).on('keydown', '.btn-checkin-ticket', function(e){
            if(e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32){
                e.stopPropagation();
            }
        });

        // 명단 삭제 (행 토글로 전파되지 않도록 stopPropagation)
        function removeRoster(id){
            $.ajax({
                url: deleteUrl, type: 'DELETE', data: { ids: [id] },
                success: function(res){
                    if(res && res.status === 200){
                        rosters = rosters.filter(function(x){ return String(x.id) !== String(id); });
                        render();
                    } else {
                        alert(res?.msg || '삭제에 실패했습니다.');
                        load();
                    }
                },
                error: function(xhr){
                    alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.');
                    load();
                }
            });
        }
        $(document).on('click', '.btn-checkin-del', async function(e){
            e.stopPropagation(); // 행 참석 토글로 번지지 않게
            var id = $(this).data('id');
            var nm = $(this).data('name') || '';
            if(await salert({text: `'${nm}' 을(를) 명단에서 삭제하시겠습니까?`})){
                removeRoster(id);
            }
        });
        // 삭제 버튼에서 Enter/Space 가 행 토글로 번지지 않게
        $(document).on('keydown', '.btn-checkin-del', function(e){
            if(e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32){
                e.stopPropagation();
            }
        });

        // 검색 (즉시 필터)
        $('#checkinSearch').on('input', render);

        // 명단 1명 추가 (닉네임/플랫폼/성별)
        $('#checkinAddBtn').on('click', async function(){
            var name     = ($('#addName').val() || '').trim();
            var platform = $('input[name="addPlatform"]:checked').val();
            var gender   = $('input[name="addGender"]:checked').val() || 'M';
            var height    = ($('#addHeight').val() || '').trim();
            var birthYear = ($('#addBirthYear').val() || '').trim();
            var job       = ($('#addJob').val() || '').trim();
            var merit     = ($('#addMerit').val() || '').trim();
            if(!name){ await salert({text: '닉네임을 입력해주세요.', cancel: false}); return; }
            if(!birthYear){ await salert({text: '년생을 입력해주세요. (예: 97)', cancel: false}); $('#addBirthYear').focus(); return; }
            if(!height){ await salert({text: '키를 입력해주세요.', cancel: false}); $('#addHeight').focus(); return; }
            if(!job){ await salert({text: '직업을 입력해주세요.', cancel: false}); $('#addJob').focus(); return; }
            if(!merit){ await salert({text: '장점을 입력해주세요.', cancel: false}); $('#addMerit').focus(); return; }
            $.ajax({
                url: addUrl, type: 'POST',
                data: { name: name, platform: platform, gender: gender, height: height, birth_year: birthYear, job: job, merit: merit },
                success: function(res){
                    alert(res?.msg);
                    if(res?.status === 200){
                        // 연속 입력 편의: 닉네임·프로필만 비우고 플랫폼/성별 선택은 유지한다.
                        $('#addHeight, #addBirthYear, #addJob, #addMerit').val('');
                        $('#addName').val('').focus();
                        load();
                    }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 키·년생은 텍스트 입력칸(한글 입력기 전환 문제 방지)이므로 숫자 외 문자는 바로 걸러낸다.
        $(document).on('input', '#addHeight, #addBirthYear, #swal-p-height, #swal-p-birth', function(){
            var cleaned = this.value.replace(/\D/g, '');
            if(cleaned !== this.value){ this.value = cleaned; }
        });

        // 닉네임·프로필 입력창에서 Enter 로 바로 추가
        $('#addName, #addHeight, #addBirthYear, #addJob, #addMerit').on('keydown', function(e){
            if(e.key === 'Enter'){ e.preventDefault(); $('#checkinAddBtn').trigger('click'); }
        });

        // 모바일 체크인/취소 버튼 — 행 클릭과 같은 동작. 행 토글로 중복 번지지 않게 전파를 막는다.
        $(document).on('click', '.btn-checkin-toggle', function(e){
            e.stopPropagation();
            toggle($(this).data('id'));
        });
        $(document).on('keydown', '.btn-checkin-toggle', function(e){
            if(e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32){
                e.stopPropagation();
            }
        });

        // 프로필(키·년생·직업) 입력/수정
        $(document).on('click', '.btn-checkin-profile', async function(e){
            e.stopPropagation(); // 행 참석 토글로 번지지 않게
            var id = $(this).data('id');
            var r  = rosters.find(function(x){ return x.id === id; });
            if(!r){ return; }
            var field = function(fid, label, type, val, ph, extra){
                return '<div style="margin-top:8px;text-align:left;">'
                    + '<label for="' + fid + '" style="font-size:0.85rem;color:#475569;font-weight:700;display:block;margin-bottom:4px;">' + label + '</label>'
                    + '<input id="' + fid + '" type="' + type + '" class="swal2-input" value="' + esc(val) + '" placeholder="' + ph + '" ' + (extra || '')
                    + ' style="width:100%;margin:0;box-sizing:border-box;"></div>';
            };
            var res = await Swal.fire({
                title: (r.name ? esc(r.name) : '닉네임 없음') + ' 프로필',
                html: field('swal-p-height', '키 (cm) *', 'text', r.height || '', '175', 'maxlength="3" inputmode="numeric" pattern="[0-9]*"')
                    + field('swal-p-birth', '년생 (2자리 가능) *', 'text', r.birth_year ? String(r.birth_year).slice(-2) : '', '97', 'maxlength="4" inputmode="numeric" pattern="[0-9]*"')
                    + field('swal-p-job', '직업 *', 'text', r.job || '', '예: 개발자', 'maxlength="50"')
                    + field('swal-p-merit', '장점 *', 'text', r.merit || '', '예: 잘 웃고 배려심이 깊어요', 'maxlength="100"'),
                focusConfirm: true,
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: '저장',
                cancelButtonText: '취소',
                preConfirm: function(){
                    var v = {
                        height:     (document.getElementById('swal-p-height').value || '').trim(),
                        birth_year: (document.getElementById('swal-p-birth').value || '').trim(),
                        job:        (document.getElementById('swal-p-job').value || '').trim(),
                        merit:      (document.getElementById('swal-p-merit').value || '').trim()
                    };
                    if(!v.height || !v.birth_year || !v.job || !v.merit){
                        Swal.showValidationMessage('키, 년생, 직업, 장점을 모두 입력해 주세요.');
                        return false;
                    }
                    return v;
                }
            });
            if(!res.isConfirmed){ return; }
            $.ajax({
                url: updateTpl.replace('__RID__', id), type: 'PUT', data: res.value,
                success: function(out){
                    if(out?.status !== 200){ alert(out?.msg ?? '저장하지 못했습니다.'); }
                    load();
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });
        $(document).on('keydown', '.btn-checkin-profile', function(e){
            if(e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32){
                e.stopPropagation();
            }
        });

        /* =====================================================================
         * 참가자 명단 이미지 — 상세페이지의 "참가자 명단만"(2번 장)과 완전히 같은 이미지를 받는다.
         *  - 상세페이지 제작 화면을 숨김 iframe 으로 불러 이 행사의 체크인 명단을 반영(나이 어린 순 → 키 큰 순)하고,
         *    2번 장(참가자 명단)만 자동으로 다운로드한다. 같은 코드를 쓰므로 디자인·분포도가 항상 동일하다.
         * =================================================================== */
        @php
            // 상세페이지 유형: 1:1(one) → solo, 와인파티(wine) → wine
            $rosterImgType = $event->isOneToOne() ? 'solo' : 'wine';
        @endphp
        var ROSTER_IMG_URL  = {!! json_encode(route('admin.rotation.detail.index'), JSON_UNESCAPED_SLASHES) !!};
        var ROSTER_IMG_TYPE = {!! json_encode($rosterImgType) !!};
        var ROSTER_IMG_EVENTS_URL = {!! json_encode(route('admin.rotation.detail.rosterSource.events'), JSON_UNESCAPED_SLASHES) !!};

        $('#rosterImageBtn').on('click', async function(){
            var arrived = rosters.filter(function(r){ return r.checked_in; }).length;

            // 함께 넣을 행사(2열) 후보 — 체크인 명단을 불러올 수 있는 행사 목록(현재 행사 제외).
            var otherEvents = [];
            try { otherEvents = ((await $.getJSON(ROSTER_IMG_EVENTS_URL)).items || []).filter(function(ev){ return ev.id !== {{ $event->id }}; }); } catch(e){ otherEvents = []; }
            var eventOpts = '<option value="">없음 (이 행사만, 1열)</option>' + otherEvents.map(function(ev){
                return '<option value="' + ev.id + '">' + esc(ev.title) + ' · ' + esc(ev.date) + '</option>';
            }).join('');

            var pick = await Swal.fire({
                title: '참가자 명단 이미지',
                html: '<div style="text-align:left;font-size:.85rem;color:#475569;margin-bottom:6px;">상세페이지의 <b>참가자 명단</b>(2번 장) 이미지를 바로 받아요.</div>'
                    + '<div style="text-align:left;font-size:.85rem;color:#475569;font-weight:700;margin:8px 0 4px;">대상</div>'
                    + '<select id="swal-ri-scope" class="swal2-select" style="width:100%;margin:0;">'
                    +   '<option value="all">전체 신청자 (' + rosters.length + '명)</option>'
                    +   '<option value="in">참석(체크인)한 사람만 (' + arrived + '명)</option>'
                    + '</select>'
                    + '<div style="text-align:left;font-size:.85rem;color:#475569;font-weight:700;margin:12px 0 4px;">함께 넣을 행사 (2열)</div>'
                    + '<select id="swal-ri-event2" class="swal2-select" style="width:100%;margin:0;">' + eventOpts + '</select>'
                    + '<div style="text-align:left;font-size:.85rem;color:#475569;font-weight:700;margin:12px 0 4px;">나이 표기</div>'
                    + '<select id="swal-ri-age" class="swal2-select" style="width:100%;margin:0;">'
                    +   '<option value="decade" selected>30대 (기본)</option>'
                    +   '<option value="half">30대 초반 / 중반 / 후반</option>'
                    +   '<option value="year">97년생</option>'
                    + '</select>'
                    + '<label style="display:flex;align-items:center;gap:8px;margin-top:14px;font-size:.9rem;font-weight:700;color:#374151;cursor:pointer;">'
                    +   '<input type="checkbox" id="swal-ri-recruit" checked style="width:18px;height:18px;"> 모집중 자리 넣기 (남/여 줄 수 맞춤, 최대 2자리)</label>',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: '이미지 다운로드',
                cancelButtonText: '취소',
                preConfirm: function(){
                    return {
                        scope: document.getElementById('swal-ri-scope').value,
                        event2: document.getElementById('swal-ri-event2').value,
                        ageType: document.getElementById('swal-ri-age').value,
                        recruit: document.getElementById('swal-ri-recruit').checked
                    };
                }
            });
            if(!pick.isConfirmed){ return; }

            var qs = '?type=' + ROSTER_IMG_TYPE
                + '&rosterEvent={{ $event->id }}'
                + '&scope=' + encodeURIComponent(pick.value.scope)
                + '&ageType=' + encodeURIComponent(pick.value.ageType)
                + '&recruit=' + (pick.value.recruit ? '1' : '0')
                + (pick.value.event2 ? '&rosterEvent2=' + encodeURIComponent(pick.value.event2) : '');

            // 화면 이동 없이 처리: 상세페이지 제작 화면을 "숨김 iframe"으로 불러 명단 반영·이미지 생성·다운로드를 맡기고,
            // 끝나면 iframe 이 postMessage 로 알려준다. (같은 도메인이라 다운로드가 그대로 동작)
            var $btn = $('#rosterImageBtn');
            var original = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>명단 이미지 만드는 중…');

            // 진행 상태 오버레이 — 단계 문구는 숨김 iframe 이 알려주는 대로 갱신한다.
            var $ov = $('#rosterImageOverlay');
            $('#rosterImageOverlayIcon').html('<div class="spinner-border text-pink" style="width:2.5rem;height:2.5rem;" role="status" aria-hidden="true"></div>');
            $('#rosterImageOverlayText').text('참가자 명단 불러오는 중…');
            $('#rosterImageOverlaySub').text('잠시만 기다려 주세요 (고화질 처리 중, 몇 초 걸려요)');
            $ov.css('display', 'flex');

            var frame = document.createElement('iframe');
            frame.style.cssText = 'position:fixed; left:-10000px; top:0; width:1280px; height:800px; border:0; opacity:0; pointer-events:none;';
            frame.setAttribute('aria-hidden', 'true');
            frame.tabIndex = -1;

            var timer = null;
            var finish = function(ok, msg){
                clearTimeout(timer);
                window.removeEventListener('message', onMessage);
                if (frame.parentNode) { frame.parentNode.removeChild(frame); }
                $btn.prop('disabled', false).html(original);
                if (ok) {
                    // 완료 표시를 잠깐 보여준 뒤 닫는다.
                    $('#rosterImageOverlayIcon').html('<div style="font-size:2.5rem;line-height:1;">✅</div>');
                    $('#rosterImageOverlayText').text('다운로드가 완료됐어요');
                    $('#rosterImageOverlaySub').text('다운로드 폴더에서 이미지를 확인해 주세요');
                    setTimeout(function(){ $ov.hide(); }, 1500);
                } else {
                    $ov.hide();
                    salert({text: msg || '이미지를 만들지 못했습니다.', cancel: false});
                }
            };
            var onMessage = function(ev){
                if (ev.origin !== window.location.origin) { return; }
                var d = ev.data || {};
                if (d.source !== 'rotable-roster-download') { return; }
                if (d.progress) { $('#rosterImageOverlayText').text(d.text || '이미지 생성 중…'); return; }
                // 파일 저장이 시작될 시간을 잠깐 준 뒤 정리한다.
                if (d.ok) { $('#rosterImageOverlayText').text('파일 저장 중…'); }
                setTimeout(function(){ finish(!!d.ok, d.msg); }, d.ok ? 1500 : 0);
            };
            window.addEventListener('message', onMessage);
            timer = setTimeout(function(){ finish(false, '시간이 오래 걸리고 있어요. 잠시 후 다시 시도해 주세요.'); }, 120000);

            frame.src = ROSTER_IMG_URL + qs;
            document.body.appendChild(frame);
        });

        load();
    });
</script>

{{-- 참가자 명단 이미지 생성 중 로딩 화면 (숨김 iframe 이 진행 문구를 알려준다) --}}
<div id="rosterImageOverlay"
     style="display:none; position:fixed; inset:0; z-index:2000; background:rgba(17,24,39,0.55); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border-radius:14px; padding:26px 34px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.25); max-width:100%;">
        <div id="rosterImageOverlayIcon"></div>
        <div id="rosterImageOverlayText" style="margin-top:14px; font-size:16px; font-weight:700; color:#374151;"></div>
        <div id="rosterImageOverlaySub" style="margin-top:4px; font-size:13px; color:#9ca3af;"></div>
    </div>
</div>

@endsection
