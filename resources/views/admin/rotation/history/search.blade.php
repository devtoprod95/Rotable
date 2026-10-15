@extends('layouts.app')

@section('title', '만남 이력 기본 조회')

@section('content')
<div class="row row-cards mt-2">

    {{-- 검색 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-user-search me-1"></i> 만남 이력 기본 조회</h3>
            </div>
            <div class="card-body">
                <small class="form-hint text-muted d-block mb-3">
                    전화번호를 입력하면, 그 사람이 <b>같은 행사에 함께 참여했던 모든 상대</b>를 보여줍니다. (전화번호 정확히 일치)
                </small>
                <div class="d-flex gap-2 align-items-end flex-wrap">
                    <div style="min-width: 260px;">
                        <label class="form-label">전화번호</label>
                        <input type="text" class="form-control" id="phoneInput" placeholder="예: 01012345678"
                               onkeydown="if(event.keyCode===13){ event.preventDefault(); $('#btnSearch').click(); }">
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary px-4" id="btnSearch">
                            <i class="ti ti-search me-1"></i> 검색
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 검색 대상(나) 요약 --}}
    <div class="col-12 d-none" id="meCard">
        <div class="card">
            <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                <span class="avatar avatar-lg" id="meAvatar">?</span>
                <div id="meInfo"></div>
                <div id="meEventsBadges" class="d-flex align-items-center gap-2 flex-wrap"></div>
            </div>
        </div>
    </div>

    {{-- 결과 --}}
    <div class="col-12 d-none" id="resultCard">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2 flex-wrap">
                <h3 class="card-title mb-0 me-2">함께 참여한 상대</h3>
                <select class="form-select form-select-sm" id="genderFilter" style="width: 110px;">
                    <option value="opposite" selected>이성만</option>
                    <option value="same">동성만</option>
                </select>
                <select class="form-select form-select-sm" id="eventFilter" style="min-width: 220px; width: auto;"></select>
                <span class="badge bg-secondary-lt" id="partnerCount"></span>
                <div class="d-flex gap-2 ms-auto">
                    {{-- 선택된 행사에서 "본인"의 매칭 조회 (내가 투표/매칭한 상대) --}}
                    <button type="button" class="btn btn-sm btn-outline-pink d-none" id="meVotedBtn">
                        <i class="ti ti-heart me-1"></i> 내 매칭 조회
                    </button>
                    {{-- 선택된 행사에서 "본인"이 관리자에게 보낸 요청 (있을 때만) --}}
                    <button type="button" class="btn btn-sm btn-outline-yellow d-none" id="myRequestBtn">
                        <i class="ti ti-hand-stop me-1"></i> 보낸 요청 <span id="myRequestCount">0</span>건
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>상대</th>
                            <th class="text-center" style="width:150px;">방문 횟수</th>
                            <th class="text-center" style="width:140px;">관계</th>
                            <th class="text-center" style="width:120px;">매칭 조회</th>
                            <th class="text-center" style="width:130px;">메시지</th>
                            <th class="text-center" style="width:120px;">만남 이력</th>
                        </tr>
                    </thead>
                    <tbody id="partnerTbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 안내(빈 상태) --}}
    <div class="col-12 d-none" id="emptyCard">
        <div class="card">
            <div class="card-body text-center text-muted py-5" id="emptyText">검색 결과가 없습니다.</div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var searchUrl = "{{ route('admin.rotation.history.search') }}";
        var pageUrl   = "{{ route('admin.rotation.history.index') }}";
        var detailBase = "{{ route('admin.rotation.history.detail') }}";

        var lastRes = null;   // 마지막 검색 응답(행사 필터링용)

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }

        function genderBadge(g){
            if(g === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(g === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

        function specLine(p){
            var s = [];
            if(p.age){ s.push(p.age + '세'); }
            else if(p.birth_year){ s.push(p.birth_year + '년생'); }
            if(p.job){ s.push(esc(p.job)); }
            if(p.height){ s.push(p.height + 'cm'); }
            if(p.mbti){ s.push(esc(p.mbti)); }
            if(p.region){ s.push(esc(p.region)); }
            if(p.is_smoker === true){ s.push('흡연'); }
            else if(p.is_smoker === false){ s.push('비흡연'); }
            return s.join(' · ');
        }

        function eventCell(e){
            if(!e){ return '-'; }
            var t = e.event_time ? new Date(e.event_time).toLocaleString('ko-KR') : '-';
            return `<div class="fw-bold">${esc(e.title) || '-'}</div>`
                 + `<div class="mt-1"><span class="badge bg-azure-lt">${esc(e.type_label) || ''}</span> `
                 + `<span class="badge bg-secondary-lt">${esc(e.status_label) || ''}</span></div>`
                 + `<div class="text-muted" style="font-size:0.85em;">${t}</div>`;
        }

        function personCell(p){
            if(!p){ return '-'; }
            var line = specLine(p);
            return `<div><span class="fw-bold">${esc(p.name) || '-'}</span> ${genderBadge(p.gender)}</div>`
                 + (line ? `<div class="text-muted" style="font-size:0.85em;">${line}</div>` : '')
                 + (p.phone ? `<div class="text-muted" style="font-size:0.85em;">${esc(p.phone)}</div>` : '');
        }

        // 방문 횟수 컬럼 셀: 행사 유형별 누적 방문 횟수. (예: 와인파티 3회 · 1:1 2회)
        var VISIT_TYPE_LABEL = @json(\App\Models\EventRound::typeOptions());
        // 유형별 뱃지 색상 (eventForm 과 동일 규칙)
        var VISIT_TYPE_BADGE = {
            '{{ \App\Models\EventRound::TYPE_WINE }}': 'bg-red-lt',
            '{{ \App\Models\EventRound::TYPE_ONE }}': 'bg-purple-lt',
        };
        function visitCell(counts){
            if(!counts || typeof counts !== 'object'){ return '<span class="text-muted">-</span>'; }
            var badges = Object.keys(VISIT_TYPE_LABEL)
                .filter(function(k){ return counts[k] && counts[k] > 0; })
                .map(function(k){
                    var cls = VISIT_TYPE_BADGE[k] || 'bg-azure-lt';
                    return `<span class="badge ${cls} mb-1">${esc(VISIT_TYPE_LABEL[k])} ${counts[k]}회</span>`;
                });
            return badges.length ? `<div class="d-flex flex-column align-items-center">${badges.join('')}</div>` : '<span class="text-muted">-</span>';
        }

        // 본인 요약: 참여 행사 횟수를 유형별 뱃지로 (예: 와인파티 3회 · 1:1 2회)
        function eventsBadges(countsByType, total){
            var badges = Object.keys(VISIT_TYPE_LABEL)
                .filter(function(k){ return countsByType && countsByType[k] && countsByType[k] > 0; })
                .map(function(k){
                    var cls = VISIT_TYPE_BADGE[k] || 'bg-azure-lt';
                    return `<span class="badge ${cls} fs-4 py-2">${esc(VISIT_TYPE_LABEL[k])} ${countsByType[k]}회</span>`;
                });
            if(!badges.length){
                return `<span class="badge bg-blue-lt fs-4 py-2">참여 행사 ${total || 0}회</span>`;
            }
            return badges.join('');
        }

        function relationCell(row){
            if(row.is_mutual){
                return '<span class="badge bg-pink-lt"><i class="ti ti-heart-filled text-pink me-1"></i>상호 매칭</span>';
            }
            if(row.i_voted && !row.they_voted){
                return '<span class="badge bg-blue-lt">내가 투표</span>';
            }
            if(!row.i_voted && row.they_voted){
                return '<span class="badge bg-orange-lt">상대가 투표</span>';
            }
            return '<span class="text-muted">투표 없음</span>';
        }

        // 행사 필터 selectbox 채우기 (전체 옵션 없음 — 기본은 최신 행사)
        function fillEventFilter(events){
            var $sel = $('#eventFilter').empty();
            (events || []).forEach(function(e){
                var t = e.event_time ? new Date(e.event_time).toLocaleDateString('ko-KR') : '';
                var label = (e.title || '행사') + (t ? ' (' + t + ')' : '');
                $sel.append(`<option value="${e.id}">${esc(label)}</option>`);
            });
            // events 는 최근순 → 첫 옵션(최신 행사)이 기본 선택.
            if((events || []).length){ $sel.val(String(events[0].id)); }
        }

        // 선택된 행사 + 성별 기준으로 상대 테이블만 다시 그린다.
        function renderPartners(){
            if(!lastRes){ return; }
            var evtId = ($('#eventFilter').val() || '').trim();
            var genderMode = $('#genderFilter').val() || 'opposite';

            var partners = (lastRes.partners || []).filter(function(r){
                if(evtId && String(r.event_id) !== evtId){ return false; }
                if(genderMode === 'opposite' && !r.is_opposite){ return false; }
                if(genderMode === 'same' && r.is_opposite){ return false; }
                return true;
            });

            $('#partnerCount').text('총 ' + partners.length + '명');

            // 선택된 행사에서 "본인"이 보낸 요청 버튼 토글.
            updateMyRequestBtn(evtId);

            if(!partners.length){
                $('#partnerTbody').html('<tr><td colspan="7" class="text-center text-muted py-4">조건에 맞는 상대가 없습니다.</td></tr>');
                return;
            }

            var myPhone = ($('#phoneInput').val() || '').trim();
            var html = partners.map(function(r){
                var p = r.partner || {};
                var detailUrl = detailBase + '?phoneA=' + encodeURIComponent(myPhone) + '&phoneB=' + encodeURIComponent(p.phone || '');
                var label = esc(p.name || '');

                // 나와 그 상대가 주고받은 메시지가 있을 때만 버튼 노출. (양방향 대화)
                var msgCell = (r.message_count > 0)
                    ? `<button type="button" class="btn btn-sm btn-outline-primary btn-ptn-msg" `
                      + `data-event-id="${r.event_id}" data-my-id="${r.my_member_id}" data-member-id="${r.partner_member_id}" data-label="${label}">`
                      + `<i class="ti ti-messages me-1"></i>${r.message_count}건</button>`
                    : '<span class="text-muted">-</span>';

                // 그 상대가 이 행사에서 투표/매칭한 상대 보기.
                var votedCell = `<button type="button" class="btn btn-sm btn-outline-pink btn-ptn-voted" `
                      + `data-event-id="${r.event_id}" data-member-id="${r.partner_member_id}" data-label="${label}">`
                      + `<i class="ti ti-heart me-1"></i>매칭 조회</button>`;

                return `<tr>`
                     + `<td>${personCell(p)}</td>`
                     + `<td class="text-center align-middle">${visitCell(r.visit_counts)}</td>`
                     + `<td class="text-center align-middle">${relationCell(r)}</td>`
                     + `<td class="text-center align-middle">${votedCell}</td>`
                     + `<td class="text-center align-middle">${msgCell}</td>`
                     + `<td class="text-center align-middle"><a href="${detailUrl}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">상세이력<i class="ti ti-external-link ms-1"></i></a></td>`
                     + `</tr>`;
            }).join('');
            $('#partnerTbody').html(html);
        }

        // 선택된 행사 기준으로 "내가 보낸 요청" 버튼을 갱신한다. (요청 0건이면 숨김)
        function updateMyRequestBtn(evtId){
            if(!lastRes){ $('#myRequestBtn').addClass('d-none'); return; }
            var evt = (lastRes.events || []).find(function(e){ return String(e.id) === String(evtId); });
            if(evt && (evt.my_request_count || 0) > 0 && evt.my_member_id){
                $('#myRequestCount').text(evt.my_request_count);
                $('#myRequestBtn')
                    .data('event-id', evt.id)
                    .data('member-id', evt.my_member_id)
                    .removeClass('d-none');
            } else {
                $('#myRequestBtn').addClass('d-none');
            }

            // 선택된 행사에서 "본인"의 매칭 조회 버튼도 갱신. (그 행사의 내 member id)
            if(evt && evt.my_member_id){
                $('#meVotedBtn').data('event-id', evt.id).data('member-id', evt.my_member_id).removeClass('d-none');
            } else {
                $('#meVotedBtn').addClass('d-none');
            }
        }

        function render(res){
            $('#meCard, #resultCard, #emptyCard').addClass('d-none');
            lastRes = res;

            if(!res || !res.found){
                $('#emptyText').text('해당 전화번호로 참여한 이력이 없습니다.');
                $('#emptyCard').removeClass('d-none');
                return;
            }

            // 나 요약
            var me = res.me || {};
            $('#meAvatar').text((me.name || '?').slice(0,1));
            $('#meInfo').html(personCell(me));
            $('#meEventsBadges').html(eventsBadges(res.events_count_by_type, res.events_count));
            $('#meCard').removeClass('d-none');

            if(!(res.partners || []).length){
                $('#partnerCount').text('총 0명');
                fillEventFilter(res.events);
                $('#partnerTbody').html('');
                $('#emptyText').text('함께 참여한 상대가 없습니다.');
                $('#emptyCard').removeClass('d-none');
                return;
            }

            fillEventFilter(res.events);
            renderPartners();
            $('#resultCard').removeClass('d-none');
        }

        // 실제 검색 수행 (pushUrl=true 면 주소창에 ?phone= 를 남겨 뒤로가기 지원)
        function doSearch(phone, pushUrl){
            phone = (phone || '').trim();
            if(!phone){ salert({text:'전화번호를 입력해주세요.', cancel:false}); return; }

            $('#phoneInput').val(phone);
            $('#partnerTbody').html('');

            if(pushUrl){
                history.pushState({ phone: phone }, '', pageUrl + '?phone=' + encodeURIComponent(phone));
            }

            $.get(searchUrl, { phone: phone }, render).fail(function(xhr){
                salert({text: xhr?.responseJSON?.error?.message ?? '검색 중 오류가 발생했습니다.', cancel:false});
            });
        }

        $('#btnSearch').on('click', function(){ doSearch($('#phoneInput').val(), true); });
        $('#eventFilter').on('change', renderPartners);
        $('#genderFilter').on('change', renderPartners);

        // 상대와 나눈 "대화 메시지" 모달 — 선택된 행사에서 나↔상대 양방향 전체.
        $(document).on('click', '.btn-ptn-msg', function(){
            var eventId  = $(this).data('event-id');
            var myId     = $(this).data('my-id');
            var memberId = $(this).data('member-id');
            var label    = $(this).data('label') || '';
            var meName   = (lastRes && lastRes.me && lastRes.me.name) ? lastRes.me.name : '';
            MessagesModal.open(eventId, meName + ' ↔ ' + label, { convA: myId, convB: memberId });
        });

        // 상대의 "매칭 조회" 모달 — 그 상대가 이 행사에서 투표/매칭한 상대.
        $(document).on('click', '.btn-ptn-voted', function(){
            var eventId  = $(this).data('event-id');
            var memberId = $(this).data('member-id');
            var label    = $(this).data('label') || '';
            VotedModal.open(eventId, memberId, label + ' 님이 선택/매칭한 상대');
        });

        // 본인의 "매칭 조회" 모달 — 내가 선택된 행사에서 투표/매칭한 상대.
        $('#meVotedBtn').on('click', function(){
            var eventId  = $(this).data('event-id');
            var memberId = $(this).data('member-id');
            var meName   = (lastRes && lastRes.me && lastRes.me.name) ? lastRes.me.name : '';
            VotedModal.open(eventId, memberId, meName + ' 님이 선택/매칭한 상대');
        });

        // 선택된 행사에서 "본인"이 관리자에게 보낸 요청 모달.
        $('#myRequestBtn').on('click', function(){
            var eventId  = $(this).data('event-id');
            var memberId = $(this).data('member-id');
            var meName   = (lastRes && lastRes.me && lastRes.me.name) ? lastRes.me.name : '';
            RequestsModal.open(eventId, meName + ' 님이 보낸 요청', { memberId: memberId });
        });

        // 뒤로/앞으로 가기: 주소의 phone 으로 다시 조회(주소는 이미 바뀌어 있으므로 pushUrl=false)
        window.addEventListener('popstate', function(){
            var p = new URLSearchParams(location.search).get('phone') || '';
            if(p){ doSearch(p, false); }
            else { $('#meCard, #resultCard, #emptyCard').addClass('d-none'); $('#phoneInput').val(''); }
        });

        // 최초 진입: ?phone= 이 있으면 자동 조회 (주소는 그대로 두되 history 상태만 보정)
        (function(){
            var p = new URLSearchParams(location.search).get('phone') || '';
            if(p){
                history.replaceState({ phone: p }, '', pageUrl + '?phone=' + encodeURIComponent(p));
                doSearch(p, false);
            }
        })();
    });
</script>

@include('admin.rotation._messagesModal')
@include('admin.rotation._requestsModal')
@include('admin.rotation._votedModal')
@endsection
