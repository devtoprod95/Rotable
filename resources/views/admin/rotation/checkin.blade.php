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
    .checkin-stat{ margin:0; }

    /* 현황 통계: 모바일은 4등분 그리드로 꽉 차게, md↑ 에선 콤팩트하게 우측 정렬 */
    /* 참여자 행: 1줄(번호·닉네임 + 상태) / 2줄(테이블·플랫폼·등록 뱃지) / 3줄(시각) / 4줄(액션 버튼) */
    .checkin-row{ display:flex; flex-wrap:wrap; align-items:center; gap:.4rem .5rem; }
    .checkin-row .ck-main{ flex:1 1 0; min-width:0; display:flex; align-items:center; flex-wrap:wrap; gap:.25rem .4rem; }
    .checkin-row .ck-name{ font-weight:700; font-size:1.05rem; overflow-wrap:anywhere; }
    .checkin-row .ck-status{ flex:0 0 auto; }
    .checkin-row .ck-meta{ flex:1 0 100%; display:flex; flex-wrap:wrap; align-items:center; gap:.3rem; }
    .checkin-row .ck-times{ flex:1 0 100%; display:flex; flex-wrap:wrap; gap:0 .75rem; }
    .checkin-row .ck-actions{ flex:1 0 100%; display:flex; gap:.5rem; }
    @media (min-width: 768px){
        .w-md-auto{ width:auto !important; }
        .flex-md-grow-0{ flex-grow:0 !important; }
        .checkin-stat .card-body{ min-width:92px; }
        .h1-md{ font-size:2.5rem; }
    }
    @media (max-width: 767.98px){
        /* 모바일: 숫자 적당히, 카드 패딩 축소 */
        .checkin-stat .card-body{ padding:.4rem .25rem !important; }
        .checkin-row{ padding-left:.75rem; padding-right:.75rem; }
        .checkin-row .ck-actions .btn{ flex:1 1 0; min-height:40px; }
        .checkin-row .fs-3{ font-size:1.05rem !important; }
    }
</style>

<div class="mt-2">
    <x-event-tabs :event-id="$event->id" active="checkin" />
</div>

<div class="row row-cards">
    {{-- 상단: 행사명 + 현황 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="text-secondary small">체크인</div>
                    <h2 class="mb-0">{{ $event->title }}</h2>
                    <div class="text-secondary small mt-1">
                        {{ $event->event_time ? $event->event_time->format('Y-m-d H:i') : '' }}
                    </div>
                </div>
                {{-- 현황 통계: 모바일에선 4등분 그리드로 꽉 차게, 데스크톱에선 카드 나열 --}}
                <div class="row g-2 w-100 w-md-auto flex-grow-1 flex-md-grow-0" aria-live="polite">
                    <div class="col-3"><div class="card checkin-stat"><div class="card-body p-2 text-center">
                        <div class="text-secondary small">전체</div>
                        <div class="h2 h1-md mb-0" id="statTotal">0</div>
                    </div></div></div>
                    <div class="col-3"><div class="card checkin-stat"><div class="card-body p-2 text-center">
                        <div class="text-success small">참석</div>
                        <div class="h2 h1-md mb-0 text-success" id="statIn">0</div>
                    </div></div></div>
                    <div class="col-3"><div class="card checkin-stat"><div class="card-body p-2 text-center">
                        <div class="text-secondary small">미도착</div>
                        <div class="h2 h1-md mb-0 text-secondary" id="statOut">0</div>
                    </div></div></div>
                    <div class="col-3"><div class="card checkin-stat"><div class="card-body p-2 text-center">
                        <div class="text-teal small">입장등록</div>
                        <div class="h2 h1-md mb-0 text-teal" id="statReg">0</div>
                    </div></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 명단 추가 (한 명씩 입력) --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-user-plus me-1"></i> 체크인 명단 추가</h3>
                <span class="badge bg-azure-lt"><i class="ti ti-info-circle me-1"></i> 전화번호 없이 닉네임·플랫폼·성별만 관리해요</span>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="addName" class="form-label mb-1">닉네임</label>
                        <input type="text" id="addName" class="form-control" placeholder="플랫폼에 등록된 닉네임" maxlength="50" autocomplete="off">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 d-block">플랫폼</label>
                        <div class="btn-group w-100" role="group" aria-label="플랫폼 선택">
                            @foreach ($platformOptions as $opt)
                                <input type="radio" class="btn-check" name="addPlatform" id="addPlatform{{ ucfirst($opt['id']) }}"
                                       value="{{ $opt['id'] }}" autocomplete="off" {{ $loop->first ? 'checked' : '' }}>
                                <label class="btn btn-outline-orange" for="addPlatform{{ ucfirst($opt['id']) }}">{{ $opt['label'] }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 d-block">성별</label>
                        <div class="btn-group w-100" role="group" aria-label="성별 선택">
                            <input type="radio" class="btn-check" name="addGender" id="addGenderM" value="M" autocomplete="off" checked>
                            <label class="btn btn-outline-blue" for="addGenderM">남</label>
                            <input type="radio" class="btn-check" name="addGender" id="addGenderF" value="F" autocomplete="off">
                            <label class="btn btn-outline-pink" for="addGenderF">여</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="button" class="btn btn-primary w-100" id="checkinAddBtn">
                            <i class="ti ti-plus me-1"></i> 추가
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 테이블별 성비 (입장등록 좌석 기준) + 새로고침 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2 d-flex align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-layout-grid me-1"></i> 테이블별 성비</h3>
                <span class="text-secondary small">입장등록한 참가자 기준 · 자리 안내에 참고하세요</span>
            </div>
            <div class="card-body py-2">
                {{-- 새로고침 영역 (성비/명단 최신화) — 모바일: 한 줄에 하나씩 / PC(md↑): 가로 나열 --}}
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center flex-md-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-outline-primary" id="checkinRefreshBtn">
                        <i class="ti ti-refresh me-1"></i><span>새로고침</span>
                    </button>
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
                    @isset($devMode)
                        @if ($devMode)
                            <button type="button" class="btn btn-outline-azure" id="checkinSyncTablesBtn"
                                    title="이미 입장등록된 좌석(테이블)을 체크인 명단에 역동기화합니다 (기존 데이터 보정용)">
                                <i class="ti ti-refresh-dot me-1"></i><span>기존 테이블 동기화</span>
                            </button>
                        @endif
                    @endisset
                    <span class="text-secondary small ms-md-auto" id="lastUpdated" aria-live="polite"></span>
                </div>
                <div id="checkinSearchHint" class="form-hint mb-2">
                    미도착인 사람을 탭하면 <b>번호표 입력창</b>이 떠요. 직접 입력하면 그 번호로, <b>비워두면 자동 배정</b>됩니다.
                </div>

                {{-- 성비 그리드 (입장등록 좌석 없으면 안내) --}}
                <div class="row g-2" id="tableRatioBody"></div>
                <div class="text-secondary small text-center py-2" id="tableRatioEmpty">
                    이 회차에 생성된 테이블이 없어요. 행사 상세에서 테이블을 먼저 추가해 주세요.
                </div>
            </div>
        </div>
    </div>

    {{-- 남 / 여 구분 리스트 --}}
    <div class="col-12">
        <div class="row row-cards">
            {{-- 남 --}}
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header bg-blue-lt d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h3 class="card-title mb-0">남</h3>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-blue text-white">참석 <span id="maleIn">0</span></span>
                            <span class="badge bg-teal text-white">등록 <span id="maleReg">0</span></span>
                            <span class="badge bg-secondary-lt">전체 <span id="maleTotal">0</span></span>
                        </div>
                    </div>
                    <div class="list-group list-group-flush" id="maleList" aria-label="남성 참가자 체크인 목록"></div>
                    <div class="text-center text-secondary py-4" id="maleEmpty">참가자가 없습니다.</div>
                </div>
            </div>
            {{-- 여 --}}
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header bg-pink-lt d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h3 class="card-title mb-0">여</h3>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-pink text-white">참석 <span id="femaleIn">0</span></span>
                            <span class="badge bg-teal text-white">등록 <span id="femaleReg">0</span></span>
                            <span class="badge bg-secondary-lt">전체 <span id="femaleTotal">0</span></span>
                        </div>
                    </div>
                    <div class="list-group list-group-flush" id="femaleList" aria-label="여성 참가자 체크인 목록"></div>
                    <div class="text-center text-secondary py-4" id="femaleEmpty">참가자가 없습니다.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var listUrl   = "{{ route('admin.rotation.event.rosters.list', ['id' => $event->id]) }}";
        var checkInTpl = "{{ route('admin.rotation.event.rosters.checkIn', ['id' => $event->id, 'rosterId' => '__RID__']) }}";
        var deleteUrl = "{{ route('admin.rotation.event.rosters.delete', ['id' => $event->id]) }}";
        var addUrl    = "{{ route('admin.rotation.event.rosters.add', ['id' => $event->id]) }}";
        var ticketTpl = "{{ route('admin.rotation.event.rosters.ticket', ['id' => $event->id, 'rosterId' => '__RID__']) }}";
        var devSeedUrl = "{{ route('admin.rotation.event.rosters.devSeed', ['id' => $event->id]) }}";
        var devRosterUrl = "{{ route('admin.rotation.event.rosters.devSeedRosters', ['id' => $event->id]) }}";
        var devCheckInUrl = "{{ route('admin.rotation.event.rosters.devCheckIn', ['id' => $event->id]) }}";
        var syncTablesUrl = "{{ route('admin.rotation.event.rosters.syncTables', ['id' => $event->id]) }}";

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
            $('#statTotal').text(total);
            $('#statIn').text(inCnt);
            $('#statOut').text(total - inCnt);
            $('#statReg').text(regCnt);
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

        // 리스트 한 행 (list-group-item). 탭하면 체크인(번호표 입력/자동) 또는 취소, 우측에 번호 변경·삭제.
        function row(r){
            var inState = r.checked_in;
            var stateCls = inState ? 'checkin-in' : 'checkin-out';
            var badge = inState
                ? '<span class="badge bg-success text-white"><i class="ti ti-check me-1"></i>참석</span>'
                : '<span class="badge bg-secondary-lt">미도착</span>';
            // 입장등록 여부: 체크인(번호표 배정)된 사람만 의미가 있다. 가입했으면 완료, 아직이면 대기.
            var regBadge = '';
            if(inState){
                regBadge = r.registered
                    ? '<span class="badge bg-teal text-white"><i class="ti ti-user-check me-1"></i>입장등록 완료</span>'
                    : '<span class="badge bg-yellow-lt"><i class="ti ti-hourglass me-1"></i>입장등록 대기</span>';
            }
            var when = (inState && r.checked_in_at)
                ? `<div class="text-secondary small">${esc(r.checked_in_at)} 참석</div>` : '';
            var regWhen = (r.registered && r.registered_at)
                ? `<div class="text-secondary small">${esc(r.registered_at)} 입장등록</div>` : '';
            var nickname = r.name || '닉네임 없음';
            var name = r.name ? esc(r.name) : '<span class="text-secondary">닉네임 없음</span>';
            var pfLabel = PLATFORM_LABEL[r.platform] || r.platform || '';
            var pf = pfLabel
                ? `<span class="badge ${PLATFORM_BADGE[r.platform] || 'bg-secondary-lt'}">${esc(pfLabel)}</span>` : '';
            var toneCls = r.gender === 'F' ? 'bg-pink' : 'bg-blue';
            var ticket = r.ticket_no
                ? `<span class="badge ${toneCls} text-white fs-3">${genderText(r.gender)} ${r.ticket_no}번</span>` : '';
            // 체크인된 사람의 지정 테이블 (미지정이면 경고 뱃지 → 참가자 입장등록이 막히므로 눈에 띄게)
            var tableBadge = '';
            if(inState){
                tableBadge = r.rotation_table_id
                    ? `<span class="badge bg-azure-lt"><i class="ti ti-armchair me-1"></i>${tableNoById(r.rotation_table_id)}번 테이블</span>`
                    : `<span class="badge bg-red-lt"><i class="ti ti-alert-triangle me-1"></i>테이블 미지정</span>`;
            }
            // 입장등록을 마친 사람은 번호 변경 버튼을 보이지 않게 한다. (삭제는 아래에서 체크인 여부로 판단)
            var ticketBtn = (inState && !r.registered)
                ? `<button type="button" class="btn btn-sm btn-outline-primary btn-checkin-ticket"`
                  + ` data-id="${r.id}" aria-label="${esc(nickname)} 번호표 변경">`
                  + `<i class="ti ti-edit me-1"></i>번호·테이블 변경</button>` : '';
            // 체크인 이후(참석·입장등록)는 삭제 불가 → 미도착일 때만 삭제 버튼 노출
            var delBtn = inState ? ''
                : `<button type="button" class="btn btn-sm btn-outline-danger btn-checkin-del"`
                  + ` data-id="${r.id}" data-name="${esc(nickname)}"`
                  + ` aria-label="${esc(nickname)} 명단에서 삭제">`
                  + `<i class="ti ti-trash me-1"></i>삭제</button>`;

            var times = (when || regWhen) ? `<div class="ck-times">${when}${regWhen}</div>` : '';
            var actions = (ticketBtn || delBtn) ? `<div class="ck-actions">${ticketBtn}${delBtn}</div>` : '';

            return `<div class="list-group-item checkin-row ${stateCls}"`
                +      ` role="button" tabindex="0" data-id="${r.id}"`
                +      ` aria-pressed="${inState ? 'true' : 'false'}"`
                +      ` aria-label="${esc(nickname)} ${esc(pfLabel)} ${inState ? '참석됨, 탭하면 취소' : '미도착, 탭하면 체크인'}">`
                +   `<div class="ck-main">${ticket}<span class="ck-name">${name}</span></div>`
                +   `<div class="ck-status">${badge}</div>`
                +   `<div class="ck-meta">${tableBadge}${pf}${regBadge}</div>`
                +   times
                +   actions
                + `</div>`;
        }

        function fillColumn(listSel, emptySel, inSel, totalSel, regSel, rows){
            var inCnt = rows.filter(function(r){ return r.checked_in; }).length;
            var regCnt = rows.filter(function(r){ return r.registered; }).length;
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
        // 테이블별 성비 요약 렌더. (입장등록 좌석 기준)
        function renderTableRatios(ratios){
            tableRatios = ratios || [];
            var $body  = $('#tableRatioBody');
            var $empty = $('#tableRatioEmpty');
            // 테이블 자체가 없을 때만 안내 문구. (테이블이 있으면 입장등록이 없어도 남0·여0 으로 전부 표시)
            if(!ratios.length){
                $body.empty();
                $empty.show();
                return;
            }
            $empty.hide();
            var cards = ratios.map(function(r){
                var m = r.male || 0, f = r.female || 0, total = r.total || 0;
                // 성비 상태로 헤더 색을 바꿔 한눈에: 비어있음(회색) / 균형(초록) / 불균형(주황)
                var balanced = (m === f);
                var headBg = total === 0 ? '#94a3b8' : (balanced ? '#16a34a' : '#f59e0b');
                return ''
                  // 모바일 3열 / 태블릿 4열 / 데스크톱 6열 → 12개 테이블도 깔끔하게 수용
                  + '<div class="col-4 col-md-3 col-lg-2">'
                  +   '<div style="border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(20,30,60,.12);border:1px solid #e5e7eb;">'
                  +     '<div style="background:' + headBg + ';color:#fff;padding:4px 6px;text-align:center;font-weight:800;font-size:0.9rem;white-space:nowrap;">'
                  +       r.table_no + '번'
                  +     '</div>'
                  +     '<div style="display:flex;">'
                  +       '<div style="flex:1;background:#eff6ff;padding:6px 2px;text-align:center;border-right:1px solid #e5e7eb;">'
                  +         '<div style="font-size:0.68rem;color:#2563eb;font-weight:700;">남</div>'
                  +         '<div style="font-size:1.3rem;font-weight:800;color:#2563eb;line-height:1.1;">' + m + '</div>'
                  +       '</div>'
                  +       '<div style="flex:1;background:#fdf2f8;padding:6px 2px;text-align:center;">'
                  +         '<div style="font-size:0.68rem;color:#db2777;font-weight:700;">여</div>'
                  +         '<div style="font-size:1.3rem;font-weight:800;color:#db2777;line-height:1.1;">' + f + '</div>'
                  +       '</div>'
                  +     '</div>'
                  +   '</div>'
                  + '</div>';
            }).join('');
            $body.html(cards);
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
                showCancelButton: true, reverseButtons: true, focusConfirm: false,
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
                focusConfirm: false,
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: opts.confirmText,
                cancelButtonText: '취소',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                didOpen: function(){
                    var el = document.getElementById('swal-ticket');
                    if(el){ el.focus(); }
                },
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
            if(!name){ await salert({text: '닉네임을 입력해주세요.', cancel: false}); return; }
            $.ajax({
                url: addUrl, type: 'POST', data: { name: name, platform: platform, gender: gender },
                success: function(res){
                    alert(res?.msg);
                    if(res?.status === 200){
                        // 연속 입력 편의: 닉네임만 비우고 플랫폼/성별 선택은 유지한다.
                        $('#addName').val('').focus();
                        load();
                    }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 닉네임 입력창에서 Enter 로 바로 추가
        $('#addName').on('keydown', function(e){
            if(e.key === 'Enter'){ e.preventDefault(); $('#checkinAddBtn').trigger('click'); }
        });

        load();
    });
</script>
@endsection
