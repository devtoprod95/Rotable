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
    @media (min-width: 768px){
        .w-md-auto{ width:auto !important; }
        .flex-md-grow-0{ flex-grow:0 !important; }
        .checkin-stat .card-body{ min-width:92px; }
        .h1-md{ font-size:2.5rem; }
    }
    @media (max-width: 767.98px){
        /* 모바일: 숫자 적당히, 카드 패딩 축소 */
        .checkin-stat .card-body{ padding:.4rem .25rem !important; }
        /* 리스트 행: 긴 닉네임이어도 오른쪽 액션과 겹치지 않게 줄바꿈 허용 */
        .checkin-row{ flex-wrap:wrap; }
        .checkin-row > div:first-child{ flex:1 1 60%; min-width:0; }
        .checkin-row > div:last-child{ flex:1 1 auto; align-items:flex-start !important; }
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

    {{-- 검색 + 새로고침 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-grow-1">
                        <label for="checkinSearch" class="visually-hidden">참가자 검색 (닉네임 또는 번호표)</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="search" id="checkinSearch" class="form-control form-control-lg"
                                   autocomplete="off"
                                   placeholder="닉네임 또는 번호표로 검색"
                                   aria-describedby="checkinSearchHint">
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-lg flex-shrink-0" id="checkinRefreshBtn">
                        <i class="ti ti-refresh me-1"></i><span class="d-none d-sm-inline">새로고침</span>
                    </button>
                </div>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mt-1">
                    <div id="checkinSearchHint" class="form-hint mb-0">
                        미도착인 사람을 탭하면 <b>번호표 입력창</b>이 떠요. 직접 입력하면 그 번호로, <b>비워두면 자동 배정</b>됩니다.
                    </div>
                    <span class="text-secondary small flex-shrink-0" id="lastUpdated" aria-live="polite"></span>
                </div>
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

        var rosters = [];

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

        // 닉네임 또는 번호표(숫자)로 검색
        function matches(r, kw){
            if(!kw){ return true; }
            if((r.name || '').toLowerCase().indexOf(kw) >= 0){ return true; }
            return r.ticket_no != null && String(r.ticket_no) === kw;
        }

        // 번호표 있는 사람 먼저(번호 순), 미배정은 뒤에 닉네임 순
        function byTicketThenName(a, b){
            var ta = a.ticket_no == null ? Infinity : a.ticket_no;
            var tb = b.ticket_no == null ? Infinity : b.ticket_no;
            if(ta !== tb){ return ta < tb ? -1 : 1; }
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
                ? `<span class="badge ${toneCls} text-white fs-3 me-2">${genderText(r.gender)} ${r.ticket_no}번</span>` : '';
            // 입장등록을 마친 사람은 번호 변경·삭제 버튼을 아예 보이지 않게 한다.
            var ticketBtn = (inState && !r.registered)
                ? `<button type="button" class="btn btn-sm btn-outline-primary btn-checkin-ticket"`
                  + ` data-id="${r.id}" aria-label="${esc(nickname)} 번호표 변경">`
                  + `<i class="ti ti-edit me-1"></i>번호 변경</button>` : '';
            var delBtn = r.registered ? ''
                : `<button type="button" class="btn btn-sm btn-outline-danger btn-checkin-del"`
                  + ` data-id="${r.id}" data-name="${esc(nickname)}"`
                  + ` aria-label="${esc(nickname)} 명단에서 삭제">`
                  + `<i class="ti ti-trash me-1"></i>삭제</button>`;

            return `<div class="list-group-item checkin-row ${stateCls} d-flex align-items-center justify-content-between gap-2"`
                +      ` role="button" tabindex="0" data-id="${r.id}"`
                +      ` aria-pressed="${inState ? 'true' : 'false'}"`
                +      ` aria-label="${esc(nickname)} ${esc(pfLabel)} ${inState ? '참석됨, 탭하면 취소' : '미도착, 탭하면 체크인'}">`
                +   `<div>`
                +     `<div class="fw-bold fs-3">${ticket}${name}</div>`
                +     `<div class="mt-1">${pf}</div>`
                +     when
                +     regWhen
                +   `</div>`
                +   `<div class="text-end d-flex flex-column align-items-end gap-2">`
                +     `${badge}`
                +     regBadge
                +     ticketBtn
                +     delBtn
                +   `</div>`
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
            var kw = ($('#checkinSearch').val() || '').trim().toLowerCase();
            var rows = rosters.filter(function(r){ return matches(r, kw); });
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
        function load(){
            if(loading){ return; }
            loading = true;
            var $btn = $('#checkinRefreshBtn');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $.get(listUrl, function(res){
                rosters = (res && res.data) ? res.data : [];
                render();
                var d = new Date();
                $('#lastUpdated').removeClass('text-danger').addClass('text-secondary')
                    .text('마지막 갱신 ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()));
            }).fail(function(){
                $('#lastUpdated').removeClass('text-secondary').addClass('text-danger')
                    .text('갱신 실패 — 새로고침을 눌러 주세요');
            }).always(function(){
                loading = false;
                $btn.prop('disabled', false).html('<i class="ti ti-refresh me-1"></i><span class="d-none d-sm-inline">새로고침</span>');
            });
        }

        // 수동 새로고침 버튼
        $('#checkinRefreshBtn').on('click', load);

        function showError(xhr, fallback){
            return Swal.fire({
                icon: 'error', text: xhr?.responseJSON?.error?.message ?? fallback,
                confirmButtonText: '확인', confirmButtonColor: '#3085d6'
            });
        }

        // 번호표 입력창(숫자). 빈 값 허용 여부와 안내 문구는 호출하는 쪽에서 정한다.
        function askTicket(opts){
            return Swal.fire({
                title: opts.title,
                html: opts.html || '',
                input: 'number',
                inputLabel: opts.label,
                inputValue: opts.value || '',
                inputPlaceholder: opts.placeholder || '',
                inputAttributes: { min: 1, max: TICKET_MAX, step: 1, inputmode: 'numeric' },
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: opts.confirmText,
                cancelButtonText: '취소',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                inputValidator: function(v){
                    v = (v == null ? '' : String(v)).trim();
                    if(v === ''){ return opts.allowEmpty ? undefined : '번호표를 입력해 주세요.'; }
                    if(!/^\d+$/.test(v) || +v < 1 || +v > TICKET_MAX){ return '1~' + TICKET_MAX + ' 사이 숫자로 입력해 주세요.'; }
                }
            });
        }

        // 서버에 체크인/취소 요청. ticket 이 있으면 수동(1순위), 없으면 자동 배정(2순위).
        function sendCheckIn(id, ticket){
            var $row = $(`.checkin-row[data-id="${id}"]`);
            $row.css('opacity', 0.6);
            $.ajax({
                url: checkInTpl.replace('__RID__', id), type: 'PATCH',
                data: ticket ? { ticket_no: ticket } : {},
                success: function(res){
                    var r = findRoster(id);
                    if(r && res){
                        r.checked_in = !!res.checked_in;
                        r.checked_in_at = res.checked_in_at || null;
                        r.ticket_no = (res.ticket_no == null) ? null : res.ticket_no;
                        // 번호표가 새로 배정/회수되면 입장등록 여부는 서버 기준으로 다시 확인된다.
                        r.registered = false;
                        r.registered_at = null;
                    }
                    render();
                    if(r && res && res.checked_in){
                        var how = { manual: '직접 입력', auto: '자동 배정', existing: '기존 번호 유지' }[res.assigned] || '';
                        Swal.fire({
                            icon: 'success',
                            title: genderText(r.gender) + ' ' + res.ticket_no + '번',
                            html: '<b>' + esc(r.name || '닉네임 없음') + '</b> 님께 번호표를 건네 주세요'
                                + (how ? '<div class="text-secondary small mt-1">(' + how + ')</div>' : ''),
                            confirmButtonText: '확인', confirmButtonColor: '#3085d6',
                            timer: 5000, timerProgressBar: true
                        });
                    }
                },
                error: function(xhr){
                    $row.css('opacity', 1);
                    showError(xhr, '오류가 발생했습니다.');
                }
            });
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
                label: genderText(r.gender) + ' 번호표 — 직접 입력하거나, 비워두면 자동 배정돼요',
                placeholder: '비우면 자동 (예상 ' + hint + '번)',
                confirmText: '체크인',
                allowEmpty: true
            });
            if(!ans.isConfirmed){ return; }
            var manual = (ans.value == null ? '' : String(ans.value)).trim();
            sendCheckIn(id, manual === '' ? null : parseInt(manual, 10));
        }

        // 관리자 번호표 변경 (입장등록 전인 사람만. 입장등록을 마친 사람은 버튼이 숨겨진다.)
        async function changeTicket(id){
            var r = findRoster(id);
            if(!r || !r.ticket_no || r.registered){ return; }
            var ans = await askTicket({
                title: esc(r.name || '닉네임 없음') + ' 번호 변경',
                label: genderText(r.gender) + ' 번호표 (현재 ' + r.ticket_no + '번)',
                value: r.ticket_no,
                confirmText: '변경',
                allowEmpty: false
            });
            if(!ans.isConfirmed){ return; }
            var next = parseInt(String(ans.value).trim(), 10);
            if(next === r.ticket_no){ return; }

            $.ajax({
                url: ticketTpl.replace('__RID__', id), type: 'PATCH', data: { ticket_no: next },
                success: function(res){
                    r.ticket_no = (res && res.ticket_no) ? res.ticket_no : next;
                    render();
                    Swal.fire({ icon: 'success', text: res?.msg || '번호표를 변경했습니다.', timer: 2500, showConfirmButton: false });
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
