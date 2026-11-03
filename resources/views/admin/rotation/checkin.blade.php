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
    .checkin-stat{ min-width:96px; }
</style>

<div class="row row-cards mt-2">
    {{-- 상단: 행사명 + 현황 + 뒤로 --}}
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
                <div class="d-flex gap-2 flex-wrap" aria-live="polite">
                    <div class="card checkin-stat"><div class="card-body p-2 text-center">
                        <div class="text-secondary small">전체</div>
                        <div class="h1 mb-0" id="statTotal">0</div>
                    </div></div>
                    <div class="card checkin-stat"><div class="card-body p-2 text-center">
                        <div class="text-success small">참석</div>
                        <div class="h1 mb-0 text-success" id="statIn">0</div>
                    </div></div>
                    <div class="card checkin-stat"><div class="card-body p-2 text-center">
                        <div class="text-secondary small">미도착</div>
                        <div class="h1 mb-0 text-secondary" id="statOut">0</div>
                    </div></div>
                </div>
                <a href="{{ route('admin.rotation.event.detail', $event->id) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> 행사 상세
                </a>
            </div>
        </div>
    </div>

    {{-- 명단 추가 (한 명씩 입력) --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-user-plus me-1"></i> 사전 명단 추가</h3>
                <span class="badge bg-red-lt"><i class="ti ti-lock me-1"></i> 명단에 없는 번호는 입장 불가</span>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label for="addName" class="form-label mb-1">이름</label>
                        <input type="text" id="addName" class="form-control" placeholder="예: 홍길동" maxlength="50">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="addPhone" class="form-label mb-1">전화번호</label>
                        <input type="tel" id="addPhone" class="form-control" inputmode="numeric"
                               placeholder="01012345678" autocomplete="off">
                    </div>
                    <div class="col-8 col-md-3">
                        <label class="form-label mb-1 d-block">성별</label>
                        <div class="btn-group w-100" role="group" aria-label="성별 선택">
                            <input type="radio" class="btn-check" name="addGender" id="addGenderM" value="M" autocomplete="off" checked>
                            <label class="btn btn-outline-blue" for="addGenderM">남</label>
                            <input type="radio" class="btn-check" name="addGender" id="addGenderF" value="F" autocomplete="off">
                            <label class="btn btn-outline-pink" for="addGenderF">여</label>
                        </div>
                    </div>
                    <div class="col-4 col-md-3">
                        <button type="button" class="btn btn-primary w-100" id="checkinAddBtn">
                            <i class="ti ti-plus me-1"></i> 추가
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 검색 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body py-2">
                <label for="checkinSearch" class="visually-hidden">참가자 검색 (이름 또는 번호 뒤 4자리)</label>
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="search" id="checkinSearch" class="form-control form-control-lg"
                           inputmode="numeric" autocomplete="off"
                           placeholder="이름 또는 번호 뒤 4자리로 검색"
                           aria-describedby="checkinSearchHint">
                </div>
                <div id="checkinSearchHint" class="form-hint mt-1">
                    탭 한 번으로 <b>참석/미도착</b>이 토글됩니다. 번호 뒤 4자리만 입력해도 빠르게 찾을 수 있어요.
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
                    <div class="card-header bg-blue-lt d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">남</h3>
                        <div>
                            <span class="badge bg-blue text-white">참석 <span id="maleIn">0</span></span>
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
                    <div class="card-header bg-pink-lt d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">여</h3>
                        <div>
                            <span class="badge bg-pink text-white">참석 <span id="femaleIn">0</span></span>
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

        var rosters = [];

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }

        function renderStats(){
            var total = rosters.length;
            var inCnt = rosters.filter(function(r){ return r.checked_in; }).length;
            $('#statTotal').text(total);
            $('#statIn').text(inCnt);
            $('#statOut').text(total - inCnt);
        }

        function matches(r, kw){
            if(!kw){ return true; }
            var name = (r.name || '').toLowerCase();
            var phone = (r.phone || '');
            return name.indexOf(kw) >= 0 || phone.indexOf(kw) >= 0;
        }

        // 리스트 한 행 (list-group-item). 탭하면 참석 토글, 우측에 삭제.
        function row(r){
            var inState = r.checked_in;
            var stateCls = inState ? 'checkin-in' : 'checkin-out';
            var badge = inState
                ? '<span class="badge bg-success text-white"><i class="ti ti-check me-1"></i>참석</span>'
                : '<span class="badge bg-secondary-lt">미도착</span>';
            var when = (inState && r.checked_in_at)
                ? `<div class="text-secondary small">${esc(r.checked_in_at)} 참석</div>` : '';
            var name = r.name ? esc(r.name) : '<span class="text-secondary">이름없음</span>';

            return `<div class="list-group-item checkin-row ${stateCls} d-flex align-items-center justify-content-between gap-2"`
                +      ` role="button" tabindex="0" data-id="${r.id}"`
                +      ` aria-pressed="${inState ? 'true' : 'false'}"`
                +      ` aria-label="${esc(r.name || '이름없음')} ${esc(r.phone)} ${inState ? '참석됨, 탭하면 미도착으로' : '미도착, 탭하면 참석으로'}">`
                +   `<div>`
                +     `<div class="fw-bold fs-3">${name}</div>`
                +     `<div class="text-secondary">${esc(r.phone)}</div>`
                +     when
                +   `</div>`
                +   `<div class="text-end d-flex flex-column align-items-end gap-2">`
                +     `${badge}`
                +     `<button type="button" class="btn btn-sm btn-outline-danger btn-checkin-del"`
                +           ` data-id="${r.id}" data-name="${esc(r.name || '이름없음')}"`
                +           ` aria-label="${esc(r.name || '이름없음')} 명단에서 삭제">`
                +       `<i class="ti ti-trash me-1"></i>삭제</button>`
                +   `</div>`
                + `</div>`;
        }

        function fillColumn(listSel, emptySel, inSel, totalSel, rows){
            var inCnt = rows.filter(function(r){ return r.checked_in; }).length;
            $(inSel).text(inCnt);
            $(totalSel).text(rows.length);
            if(rows.length === 0){
                $(listSel).empty();
                $(emptySel).show();
            } else {
                $(emptySel).hide();
                $(listSel).html(rows.map(row).join(''));
            }
        }

        function render(){
            var kw = ($('#checkinSearch').val() || '').trim().toLowerCase();
            var rows = rosters.filter(function(r){ return matches(r, kw); });
            renderStats();

            var males   = rows.filter(function(r){ return r.gender !== 'F'; }); // 성별은 M/F 필수, 그 외는 남으로 귀속
            var females = rows.filter(function(r){ return r.gender === 'F'; });

            fillColumn('#maleList',   '#maleEmpty',   '#maleIn',   '#maleTotal',   males);
            fillColumn('#femaleList', '#femaleEmpty', '#femaleIn', '#femaleTotal', females);
        }

        function load(){
            $.get(listUrl, function(res){
                rosters = (res && res.data) ? res.data : [];
                render();
            });
        }

        function toggle(id){
            var $row = $(`.checkin-row[data-id="${id}"]`);
            $row.css('opacity', 0.6);
            $.ajax({
                url: checkInTpl.replace('__RID__', id), type: 'PATCH',
                success: function(res){
                    if(res && res.status === 200){
                        var r = rosters.find(function(x){ return String(x.id) === String(id); });
                        if(r){
                            r.checked_in = (typeof res.checked_in !== 'undefined') ? res.checked_in : !r.checked_in;
                            r.checked_in_at = res.checked_in_at || null;
                        }
                        render();
                    } else {
                        alert(res?.msg || '처리에 실패했습니다.');
                        $row.css('opacity', 1);
                    }
                },
                error: function(xhr){
                    alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.');
                    $row.css('opacity', 1);
                }
            });
        }

        // 탭/클릭 토글 (두 리스트 + 미지정 공통, 이벤트 위임)
        $(document).on('click', '.checkin-row', function(){
            toggle($(this).data('id'));
        });
        // 키보드 접근성: Enter/Space 로 토글
        $(document).on('keydown', '.checkin-row', function(e){
            if(e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32){
                e.preventDefault();
                toggle($(this).data('id'));
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
                    // 이미 입장등록한 참가자는 서버가 거부(메시지 포함). 거부/부분삭제 반영 위해 재동기화.
                    alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.');
                    load();
                }
            });
        }
        $(document).on('click', '.btn-checkin-del', async function(e){
            e.stopPropagation(); // 행 참석 토글로 번지지 않게
            var id = $(this).data('id');
            var nm = $(this).data('name') || '';
            if(await salert({text: `${nm} 님을 명단에서 삭제하시겠습니까?`})){
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

        // 사전 명단 1명 추가 (이름/번호/성별)
        $('#checkinAddBtn').on('click', async function(){
            var name   = ($('#addName').val() || '').trim();
            var phone  = ($('#addPhone').val() || '').trim();
            var gender = $('input[name="addGender"]:checked').val() || 'M';
            if(!phone){ await salert({text: '전화번호를 입력해주세요.', cancel: false}); return; }
            $.ajax({
                url: addUrl, type: 'POST', data: { name: name, phone: phone, gender: gender },
                success: function(res){
                    alert(res?.msg);
                    if(res?.status === 200){
                        $('#addName').val('');
                        $('#addPhone').val('');
                        $('#addGenderM').prop('checked', true); // 기본값(남)으로 복귀
                        $('#addName').focus();
                        load();
                    }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        load();
    });
</script>
@endsection
