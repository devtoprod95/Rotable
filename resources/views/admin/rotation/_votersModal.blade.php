{{--
    받은 투표 모달 — 특정 회원을 "선택(지목)한 사람들".
    - 사람 1명 = 한 칸. 번호표 박스(성별 색) · 라벨 · 년생/직업/키/사는 곳 · 핸드폰 · 투표 시각, 서로 선택이면 ❤ 매칭.
    - 표 라이브러리 없이 직접 렌더한다. (모바일·PC 같은 구조, 992px↑ 2열)
    - 사용법(JS): VotersModal.open(eventId, memberId, memberLabel)
--}}
@php
    $voterUrlTemplate = route('admin.rotation.event.members.voters.list', ['id' => '__ID__', 'memberId' => '__MID__']);
@endphp

<style>
    #voterModal .vt-list{ display:grid; grid-template-columns:1fr; gap:.5rem; }
    @media (min-width: 992px){ #voterModal .vt-list{ grid-template-columns:1fr 1fr; } }
    #voterModal .vt-item{ display:flex; align-items:flex-start; gap:.6rem; border:1px solid #e9ecf1; border-radius:12px; background:#fff; padding:.6rem .7rem; }
    #voterModal .vt-item.is-mutual{ border-color:#f5b5c8; background:#fff5f8; }
    #voterModal .vt-ticket{ flex:0 0 auto; width:46px; height:46px; border-radius:12px; color:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; line-height:1.05; }
    #voterModal .vt-ticket small{ font-size:.66rem; font-weight:700; opacity:.85; }
    #voterModal .vt-ticket b{ font-size:1.1rem; font-weight:800; }
    #voterModal .vt-ticket.is-m{ background:#206bc4; } #voterModal .vt-ticket.is-f{ background:#d6336c; }
    #voterModal .vt-body{ flex:1 1 auto; min-width:0; }
    #voterModal .vt-top{ display:flex; align-items:center; flex-wrap:wrap; gap:.35rem; }
    #voterModal .vt-name{ font-weight:800; overflow-wrap:anywhere; }
    #voterModal .vt-time{ margin-left:auto; font-size:.75rem; color:#868e96; white-space:nowrap; }
    #voterModal .vt-spec{ font-size:.82rem; color:#495057; line-height:1.5; overflow-wrap:anywhere; }
    #voterModal .vt-phone{ font-size:.82rem; color:#495057; }
    #voterModal .vt-phone a{ color:inherit; text-decoration:none; }
    #voterModal .vt-empty{ grid-column:1 / -1; text-align:center; color:#868e96; padding:2.5rem 0; }
</style>

<div class="modal fade" id="voterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">받은 투표 <span class="text-muted fw-normal fs-5">· 나를 선택한 사람</span></h5>
                    <small class="text-muted" id="voter-memberLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <span id="voter-pageCount" class="badge bg-secondary-lt"></span>
                    <span id="voter-mutualCount" class="badge bg-pink-lt d-none"></span>
                    <small class="text-muted">번호표 순 · 서로 선택하면 ❤ 매칭</small>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="VotersModal.refresh()">
                        <i class="ti ti-refresh me-1"></i> 새로고침
                    </button>
                </div>

                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" id="voter-search" class="form-control" placeholder="번호표 / 닉네임 / 직업 / 지역 / 핸드폰으로 검색" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="voter-search-clear" title="검색 초기화">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div id="voter-list" class="vt-list" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.VotersModal = (function(){
        var urlTemplate = @json($voterUrlTemplate);
        var currentEventId = null, currentMemberId = null;
        var rows = [];
        var modalEl = null, bsModal = null;

        function listUrl(eventId, memberId){ return urlTemplate.replace('__ID__', eventId).replace('__MID__', memberId); }
        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }
        function spec(d){
            var p = [];
            if(d.birth_year){ p.push(String(d.birth_year).slice(-2) + '년생' + (d.age ? ' (' + d.age + '세)' : '')); }
            if(d.job){ p.push(esc(d.job)); }
            if(d.height){ p.push(d.height + 'cm'); }
            if(d.region){ p.push(esc(d.region)); }
            return p.join(' · ');
        }
        function phone(d){
            var raw = String(d.phone || '').replace(/\D+/g, '');
            if(!raw){ return ''; }
            var shown = raw.length === 11 ? raw.replace(/(\d{3})(\d{4})(\d{4})/, '$1-$2-$3') : raw;
            return '<div class="vt-phone"><i class="ti ti-phone me-1"></i><a href="tel:' + raw + '">' + esc(shown) + '</a></div>';
        }
        function time(v){
            if(!v){ return ''; }
            var d = new Date(v);
            if(isNaN(d.getTime())){ return ''; }
            var p = function(n){ return (n < 10 ? '0' : '') + n; };
            return (d.getMonth() + 1) + '/' + d.getDate() + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
        }
        function item(d){
            var sp = spec(d);
            return '<div class="vt-item' + (d.is_mutual ? ' is-mutual' : '') + '">'
                 +   '<div class="vt-ticket ' + (d.gender === 'F' ? 'is-f' : 'is-m') + '"><small>' + (d.gender === 'F' ? '여' : '남') + '</small><b>' + esc(d.name || '-') + '</b></div>'
                 +   '<div class="vt-body">'
                 +     '<div class="vt-top"><span class="vt-name">' + esc(d.label || (d.name ? d.name + '호' : '-')) + '</span>'
                 +       (d.is_mutual ? '<span class="badge bg-pink-lt"><i class="ti ti-heart-filled text-pink me-1"></i>매칭</span>' : '')
                 +       '<span class="vt-time">' + time(d.voted_at) + '</span></div>'
                 +     (sp ? '<div class="vt-spec">' + sp + '</div>' : '') + phone(d)
                 +   '</div>'
                 + '</div>';
        }
        function filtered(){
            var kw = ($('#voter-search').val() || '').trim();
            if(!kw){ return rows; }
            var k = kw.toLowerCase(), digits = kw.replace(/\D+/g, '');
            return rows.filter(function(d){
                if(digits.length >= 3 && String(d.phone || '').replace(/\D+/g, '').indexOf(digits) !== -1){ return true; }
                return [d.name, d.label, d.job, d.region].some(function(x){ return x && String(x).toLowerCase().indexOf(k) !== -1; });
            });
        }
        function render(){
            var list = filtered();
            $('#voter-list').html(list.length ? list.map(item).join('') : '<div class="vt-empty">아직 이 회원을 선택한 사람이 없습니다.</div>');
            $('#voter-pageCount').text('총 ' + list.length + '명');
            var mutual = list.filter(function(d){ return d.is_mutual; }).length;
            $('#voter-mutualCount').toggleClass('d-none', !mutual).text('❤ 매칭 ' + mutual + '명');
        }
        function reload(){
            if(!currentEventId || !currentMemberId){ return; }
            $('#voter-list').html('<div class="vt-empty">불러오는 중...</div>');
            // 화면 안에 "불러오는 중"을 보여주므로 전역 로딩 팝업(ajaxSetup beforeSend/complete)은 끈다.
            $.ajax({ url: listUrl(currentEventId, currentMemberId), type: 'GET', beforeSend: $.noop, complete: $.noop })
                .done(function(res){ rows = (res && res.data) ? res.data : []; render(); })
                .fail(function(){ rows = []; $('#voter-list').html('<div class="vt-empty text-danger">불러오지 못했습니다.</div>'); $('#voter-pageCount').text(''); });
        }

        $(function(){
            var t = null;
            $('#voter-search').on('input', function(){ clearTimeout(t); t = setTimeout(render, 150); });
            $('#voter-search-clear').on('click', function(){ $('#voter-search').val(''); render(); });
        });

        return {
            refresh: function(){ reload(); },
            open: function(eventId, memberId, memberLabel){
                if(!eventId || !memberId){ return; }
                currentEventId = eventId;
                currentMemberId = memberId;
                rows = [];
                $('#voter-memberLabel').text(memberLabel || '');
                $('#voter-search').val('');
                $('#voter-list').empty(); $('#voter-pageCount').text(''); $('#voter-mutualCount').addClass('d-none');

                if(!modalEl){
                    modalEl = document.getElementById('voterModal');
                    var ModalClass =
                        (window.tabler && window.tabler.Modal) ||
                        (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                        (window.bootstrap && window.bootstrap.Modal) ||
                        (window.coreui && window.coreui.Modal) ||
                        window.Modal;
                    if(!ModalClass){ console.error('Modal 클래스를 찾을 수 없습니다.'); return; }
                    bsModal = new ModalClass(modalEl);
                }
                bsModal.show();
                reload();
            },
        };
    })();
</script>
