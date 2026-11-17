@extends('layouts.app')

@section('title', '만남 이력 기본 조회')

@section('content')
<style>
    /* 만남 이력 기본 조회 — 표 대신 카드. 모바일 1열 / 768px↑ 2열 / 1200px↑ 3열 */
    .hs-grid{ display:grid; grid-template-columns:1fr; gap:.75rem; }
    @media (min-width: 768px){ .hs-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1200px){ .hs-grid{ grid-template-columns:repeat(3, minmax(0, 1fr)); } }
    .hs-card{ display:flex; flex-direction:column; border:1px solid #e6e8eb; border-radius:14px; background:#fff; padding:.8rem .9rem; box-shadow:0 1px 3px rgba(0,0,0,.05); }
    .hs-card.is-m{ border-top:4px solid #206bc4; } .hs-card.is-f{ border-top:4px solid #d6336c; }
    .hs-card.is-mutual{ box-shadow:0 0 0 2px rgba(214,51,108,.25); }
    .hs-head{ display:flex; align-items:flex-start; gap:.65rem; }
    .hs-ticket{ flex:0 0 auto; width:50px; height:50px; border-radius:12px; color:#fff; background:#206bc4; display:flex; flex-direction:column; align-items:center; justify-content:center; line-height:1.05; }
    .hs-card.is-f .hs-ticket{ background:#d6336c; }
    .hs-ticket small{ font-size:.68rem; font-weight:700; opacity:.85; } .hs-ticket b{ font-size:1.2rem; font-weight:800; }
    .hs-who{ flex:1 1 auto; min-width:0; }
    .hs-name{ font-weight:800; line-height:1.3; overflow-wrap:anywhere; }
    .hs-info{ margin:.6rem 0 0; padding:.5rem .65rem; background:#f8f9fa; border-radius:10px; }
    .hs-info > div{ display:flex; gap:.5rem; font-size:.86rem; line-height:1.6; }
    .hs-info dt{ flex:0 0 48px; color:#868e96; font-weight:700; margin:0; }
    .hs-info dd{ flex:1 1 auto; margin:0; color:#343a40; overflow-wrap:anywhere; }
    .hs-actions{ display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.4rem; margin-top:.6rem; }
    .hs-actions .btn{ min-height:40px; padding-left:.35rem; padding-right:.35rem; white-space:nowrap; }
    .hs-empty{ grid-column:1 / -1; text-align:center; color:#868e96; padding:2.5rem 0; }
    /* 본인 행사별 신원 카드 */
    .hs-me{ border:1px solid #e6e8eb; border-radius:12px; padding:.65rem .8rem; background:#fff; }
    .hs-me-title{ font-weight:800; overflow-wrap:anywhere; }
    .hs-me-id{ display:flex; flex-wrap:wrap; gap:.3rem; margin-top:.35rem; }
    .hs-me-spec{ font-size:.84rem; color:#495057; margin-top:.35rem; line-height:1.55; overflow-wrap:anywhere; }
    /* 결과 필터 줄: 모바일은 한 줄씩 전체 폭 */
    .hs-filters{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
    @media (max-width: 767.98px){
        .hs-filters > *{ flex:1 1 100%; width:100% !important; }
        .hs-filters .hs-filter-btns{ display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
        .hs-filters .hs-filter-btns .btn{ width:100%; min-height:40px; }
    }
</style>
<div class="row row-cards mt-2">

    {{-- 검색 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-user-search me-1"></i> 만남 이력 기본 조회</h3>
            </div>
            <div class="card-body">
                <div class="text-muted mb-3" style="font-size:.9rem; line-height:1.7;">
                    <div>전화번호를 입력하면 그 사람이 <b>같은 행사에 함께 참여했던 모든 상대</b>를 보여줘요.</div>
                    <div>전화번호가 정확히 일치해야 해요. (하이픈은 넣어도 돼요)</div>
                </div>
                <label class="form-label" for="phoneInput">전화번호</label>
                <div class="input-group input-group-lg">
                    <input type="tel" class="form-control" id="phoneInput" placeholder="예: 01012345678" inputmode="numeric" autocomplete="off"
                           onkeydown="if(event.keyCode===13){ event.preventDefault(); $('#btnSearch').click(); }">
                    <button type="button" class="btn btn-primary px-4" id="btnSearch">
                        <i class="ti ti-search me-1"></i> 검색
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 검색 대상(나) 요약 --}}
    <div class="col-12 d-none" id="meCard">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-user me-1"></i> 검색한 회원 (본인)</h3>
            </div>
            <div class="card-body">
                {{-- 유형별 참여 횟수 뱃지 (종합) --}}
                <div id="meEventsBadges" class="d-flex align-items-center gap-2 flex-wrap mb-3"></div>
                {{-- 본인이 참여한 행사별 신원 (플랫폼·닉네임·번호표 등은 행사마다 다를 수 있음) --}}
                <div id="meByEvent"></div>
            </div>
        </div>
    </div>

    {{-- 결과 --}}
    <div class="col-12 d-none" id="resultCard">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-users-group me-1"></i> 함께 참여한 상대 <span class="badge bg-secondary-lt ms-1" id="partnerCount"></span></h3>
            </div>
            <div class="card-body">
                <div class="hs-filters mb-3">
                    <select class="form-select" id="eventFilter" style="min-width:240px; width:auto;" aria-label="행사 선택"></select>
                    <select class="form-select" id="genderFilter" style="width:120px;" aria-label="성별 보기">
                        <option value="opposite" selected>이성만</option>
                        <option value="same">동성만</option>
                    </select>
                    <div class="hs-filter-btns d-flex gap-2 ms-md-auto">
                        {{-- 선택된 행사에서 "본인"의 매칭 조회 (내가 투표/매칭한 상대) --}}
                        <button type="button" class="btn btn-outline-pink d-none" id="meVotedBtn">
                            <i class="ti ti-heart me-1"></i> 내 매칭 조회
                        </button>
                        {{-- 선택된 행사에서 "본인"이 관리자에게 보낸 요청 (있을 때만) --}}
                        <button type="button" class="btn btn-outline-yellow d-none" id="myRequestBtn">
                            <i class="ti ti-hand-stop me-1"></i> 보낸 요청 <span id="myRequestCount">0</span>건
                        </button>
                    </div>
                </div>
                <div class="hs-grid" id="partnerList" aria-live="polite"></div>
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

        // 본인이 참여한 "행사별 신원" 카드. 행사마다 플랫폼·닉네임·번호표가 다를 수 있어 행사 단위로 보여준다.
        //  list: [{ event:{title,type,type_label,event_time,...}, person:{platform_label,nickname,ticket,gender,...} }]
        function ticketOf(p){
            if(p.ticket != null && p.ticket !== ''){ return String(p.ticket); }
            var m = /_([0-9]+)호\s*$/.exec(p.label || '');
            return m ? m[1] : '';
        }
        function profileOf(p){
            var s = [];
            if(p.birth_year){ s.push(String(p.birth_year).slice(-2) + '년생' + (p.age ? ' (' + p.age + '세)' : '')); }
            else if(p.age){ s.push(p.age + '세'); }
            if(p.job){ s.push(esc(p.job)); }
            if(p.height){ s.push(p.height + 'cm'); }
            if(p.mbti){ s.push(esc(p.mbti)); }
            return s.join(' · ');
        }
        function lifestyleOf(p){
            var s = [];
            if(p.drink_capacity){ s.push('🍶 ' + esc(p.drink_capacity)); }
            if(p.drink_frequency){ s.push('🍻 ' + esc(p.drink_frequency)); }
            if(p.has_pet === true){ s.push('🐾 애완동물 있음'); } else if(p.has_pet === false){ s.push('🐾 애완동물 없음'); }
            if(p.friend_meet_frequency){ s.push('👫 ' + esc(p.friend_meet_frequency)); }
            return s.join(' · ');
        }
        function smokeBadge(p){
            if(p.is_smoker === true){ return ' <span class="badge bg-red-lt">흡연</span>'; }
            if(p.is_smoker === false){ return ' <span class="badge bg-green-lt">비흡연</span>'; }
            return '';
        }
        function phoneHtml(v){
            var raw = String(v || '').replace(/\D+/g, '');
            if(!raw){ return '-'; }
            var shown = raw.length === 11 ? raw.replace(/(\d{3})(\d{4})(\d{4})/, '$1-$2-$3') : raw;
            return '<a href="tel:' + raw + '" class="text-reset fw-semibold">' + esc(shown) + '</a>';
        }
        function platformName(code, fallback){
            if(code === 'frip'){ return '프립'; }
            if(code === 'munto'){ return '문토'; }
            return fallback || '';
        }

        function renderMeByEvent(list){
            if(!list || !list.length){ return ''; }
            var cards = list.map(function(it){
                var e = it.event || {}, p = it.person || {};
                var t = e.event_time ? new Date(e.event_time).toLocaleDateString('ko-KR') : '';
                var typeCls = (VISIT_TYPE_BADGE && VISIT_TYPE_BADGE[e.type]) ? VISIT_TYPE_BADGE[e.type] : 'bg-azure-lt';
                var ticket = ticketOf(p);
                var gender = p.gender === 'F' ? '여' : (p.gender === 'M' ? '남' : '');
                var pf = p.platform_label ? '<span class="badge bg-secondary-lt">' + esc(p.platform_label) + '</span>' : '';
                var prof = profileOf(p);
                var extra = [prof, p.region ? esc(p.region) : ''].filter(Boolean).join(' · ');
                return '<div class="hs-me">'
                     +   '<div class="d-flex align-items-center flex-wrap gap-1"><span class="hs-me-title">' + (esc(e.title) || '행사') + '</span>'
                     +     '<span class="badge ' + typeCls + '">' + (esc(e.type_label) || '') + '</span>'
                     +     '<span class="text-muted small ms-auto">' + esc(t) + '</span></div>'
                     +   '<div class="hs-me-id">' + pf
                     +     (p.nickname ? '<span class="badge bg-azure-lt">' + esc(p.nickname) + '</span>' : '')
                     +     ((gender || ticket) ? '<span class="badge ' + (p.gender === 'F' ? 'bg-pink' : 'bg-blue') + ' text-white">' + gender + ' ' + (ticket ? esc(ticket) + '호' : '') + '</span>' : '')
                     +   '</div>'
                     +   (extra ? '<div class="hs-me-spec">' + extra + smokeBadge(p) + '</div>' : '')
                     + '</div>';
            }).join('');
            return '<div class="text-muted fw-bold small mb-2">행사별 신원 <span class="fw-normal">· 행사마다 플랫폼·닉네임·번호표가 다를 수 있어요</span></div>'
                 + '<div class="hs-grid">' + cards + '</div>';
        }

        // 방문 횟수 컬럼 셀: 행사 유형별 누적 방문 횟수. (예: 와인파티 3회 · 1:1 2회)
        var VISIT_TYPE_LABEL = @json(\App\Models\EventRound::typeOptions());
        // 유형별 뱃지 색상 (eventForm 과 동일 규칙)
        var VISIT_TYPE_BADGE = {
            '{{ \App\Models\EventRound::TYPE_WINE }}': 'bg-red-lt',
            '{{ \App\Models\EventRound::TYPE_ONE }}': 'bg-purple-lt',
        };

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
                $('#partnerList').html('<div class="hs-empty">조건에 맞는 상대가 없습니다.</div>');
                return;
            }

            var myPhone = ($('#phoneInput').val() || '').trim();
            var html = partners.map(function(r){
                var p = r.partner || {};
                var detailUrl = detailBase + '?phoneA=' + encodeURIComponent(myPhone) + '&phoneB=' + encodeURIComponent(p.phone || '');
                var label = esc(p.label || (p.name ? p.name + '호' : ''));
                var ticket = ticketOf(p);
                var isF = p.gender === 'F';
                var pf = platformName(r.event_platform, p.platform_label ? esc(p.platform_label) : '');
                var visits = Object.keys(VISIT_TYPE_LABEL)
                    .filter(function(k){ return r.visit_counts && r.visit_counts[k] > 0; })
                    .map(function(k){ return '<span class="badge ' + (VISIT_TYPE_BADGE[k] || 'bg-azure-lt') + '">' + esc(VISIT_TYPE_LABEL[k]) + ' ' + r.visit_counts[k] + '회</span>'; })
                    .join('');
                var prof = profileOf(p), life = lifestyleOf(p);

                var msgBtn = (r.message_count > 0)
                    ? '<button type="button" class="btn btn-outline-primary btn-ptn-msg" data-event-id="' + r.event_id + '" data-my-id="' + r.my_member_id
                      + '" data-member-id="' + r.partner_member_id + '" data-label="' + label + '"><i class="ti ti-messages me-1"></i>메시지 ' + r.message_count + '</button>'
                    : '<button type="button" class="btn btn-outline-secondary" disabled><i class="ti ti-messages me-1"></i>메시지 0</button>';
                var votedBtn = '<button type="button" class="btn btn-outline-pink btn-ptn-voted" data-event-id="' + r.event_id
                      + '" data-member-id="' + r.partner_member_id + '" data-label="' + label + '"><i class="ti ti-heart me-1"></i>매칭 조회</button>';
                var detailBtn = '<a href="' + detailUrl + '" target="_blank" rel="noopener" class="btn btn-outline-primary"><i class="ti ti-external-link me-1"></i>상세 이력</a>';

                return '<div class="hs-card ' + (isF ? 'is-f' : 'is-m') + (r.is_mutual ? ' is-mutual' : '') + '">'
                     +   '<div class="hs-head">'
                     +     '<div class="hs-ticket"><small>' + (isF ? '여' : '남') + '</small><b>' + (ticket ? esc(ticket) : '-') + '</b></div>'
                     +     '<div class="hs-who"><div class="hs-name">' + (p.nickname ? esc(p.nickname) : label) + '</div>'
                     +       '<div class="d-flex flex-wrap gap-1 mt-1">' + (pf ? '<span class="badge bg-secondary-lt">' + pf + '</span>' : '') + visits + '</div></div>'
                     +     '<div class="flex-shrink-0">' + relationCell(r) + '</div>'
                     +   '</div>'
                     +   '<dl class="hs-info">'
                     +     '<div><dt>프로필</dt><dd>' + (prof || '-') + '</dd></div>'
                     +     '<div><dt>지역</dt><dd>' + (p.region ? esc(p.region) : '-') + smokeBadge(p) + '</dd></div>'
                     +     (life ? '<div><dt>생활</dt><dd>' + life + '</dd></div>' : '')
                     +     '<div><dt>핸드폰</dt><dd>' + phoneHtml(p.phone) + '</dd></div>'
                     +   '</dl>'
                     +   '<div class="hs-actions">' + votedBtn + msgBtn + detailBtn + '</div>'
                     + '</div>';
            }).join('');
            $('#partnerList').html(html);
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

            // 나 요약 — 유형별 참여 뱃지 + 행사별 신원 표
            $('#meEventsBadges').html(eventsBadges(res.events_count_by_type, res.events_count));
            $('#meByEvent').html(renderMeByEvent(res.me_by_event));
            $('#meCard').removeClass('d-none');

            if(!(res.partners || []).length){
                $('#partnerCount').text('총 0명');
                fillEventFilter(res.events);
                $('#partnerList').html('');
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
            phone = (phone || '').replace(/\D+/g, '');
            if(!phone){ salert({text:'전화번호를 입력해주세요.', cancel:false}); return; }

            $('#phoneInput').val(phone);
            $('#partnerList').html('');

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
            var meName   = (lastRes && lastRes.me) ? (lastRes.me.label || (lastRes.me.name ? lastRes.me.name + '호' : '')) : '';
            MessagesModal.open(eventId, meName + ' ↔ ' + label, { convA: myId, convB: memberId });
        });

        // 상대의 "매칭 조회" 모달 — 그 상대가 이 행사에서 투표/매칭한 상대.
        $(document).on('click', '.btn-ptn-voted', function(){
            var eventId  = $(this).data('event-id');
            var memberId = $(this).data('member-id');
            var label    = $(this).data('label') || '';
            var who = label || '회원';
            VotedModal.open(eventId, memberId, who + ' 님이 선택/매칭한 상대');
        });

        // 본인의 "매칭 조회" 모달 — 내가 선택된 행사에서 투표/매칭한 상대.
        $('#meVotedBtn').on('click', function(){
            var eventId  = $(this).data('event-id');
            var memberId = $(this).data('member-id');
            var meName   = (lastRes && lastRes.me) ? (lastRes.me.label || (lastRes.me.name ? lastRes.me.name + '호' : '')) : '';
            var meWho = meName || '회원';
            VotedModal.open(eventId, memberId, meWho + ' 님이 선택/매칭한 상대');
        });

        // 선택된 행사에서 "본인"이 관리자에게 보낸 요청 모달.
        $('#myRequestBtn').on('click', function(){
            var eventId  = $(this).data('event-id');
            var memberId = $(this).data('member-id');
            var meName   = (lastRes && lastRes.me) ? (lastRes.me.label || (lastRes.me.name ? lastRes.me.name + '호' : '')) : '';
            var meWho = meName || '회원';
            RequestsModal.open(eventId, meWho + ' 님이 보낸 요청', { memberId: memberId });
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
