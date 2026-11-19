{{--
    재사용 "매칭 조회" 모달. (전체 투표: 누가 누구를 선택했는지 전부)
    - 페이지네이션이 없으므로 테이블 라이브러리 없이 마크업으로 직접 렌더한다.
    - 상호 매칭(상대도 나를 선택)인 경우 하트·분홍 배경으로 표시한다.
    - 선택한 사람(voter)을 남/여 두 칸으로 나눠(PC 좌: 남 · 우: 여, 모바일 위아래) 각 칸 안은 번호표 순으로 보여준다.
      (행사 상세의 참여자 목록과 같은 배치)
    - 카드 렌더는 _matchCard(매칭 결과 모달과 공용). 년생·직업·키·사는 곳·핸드폰·투표 시각을 보여주고 MBTI·흡연·생활습관은 뺀다.
    - 사용법(JS): MatchesModal.open(eventId, eventLabel)
    - matches.list 라우트는 {id} 를 치환해서 사용하므로 플레이스홀더(__ID__)를 둔다.
--}}
@php
    $matchesUrlTemplate = route('admin.rotation.event.matches.list', ['id' => '__ID__']);
@endphp

@include('admin.rotation._matchCard')

<style>
    /* 남/여 두 칸: 칸 머리(행사 상세 참여자 목록과 같은 모양) + 카드 세로 나열 */
    #matchesModal .mc-col-head{ display:flex; align-items:center; gap:.35rem; width:100%; border:0; text-align:left; cursor:pointer;
                                font-weight:800; font-size:1.05rem; padding:.45rem .75rem; border-radius:10px; margin-bottom:.6rem; }
    #matchesModal .mc-col-head .ck-chevron{ margin-left:auto; }
    #matchesModal .mc-col-head[aria-expanded="true"] .ck-chevron{ transform:rotate(180deg); }
    #matchesModal .mc-col.is-collapsed .mc-col-body{ display:none; }
    #matchesModal .mc-col-list{ display:flex; flex-direction:column; gap:.6rem; }
    #matchesModal .mc-col-empty{ text-align:center; color:#868e96; padding:1.5rem 0; font-size:.9rem; }
</style>

<div class="modal fade" id="matchesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">매칭 조회</h5>
                    <small class="text-muted" id="match-eventLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-center mb-2 flex-wrap gap-1">
                    <span id="match-pageCount" class="badge bg-secondary-lt"></span>
                    <small class="text-muted">남/여 · 번호표 순 · 서로 선택하면 <span class="badge bg-pink-lt">매칭</span></small>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="MatchesModal.refresh()">
                        <i class="ti ti-refresh me-1"></i> 새로고침
                    </button>
                </div>

                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" id="match-search" class="form-control" placeholder="번호표 / 닉네임 / 직업 / 지역 / 핸드폰으로 검색" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="match-search-clear" title="검색 초기화">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div id="match-list" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.MatchesModal = (function(){
        var urlTemplate = @json($matchesUrlTemplate);

        var currentEventId = null;
        var rows = [];           // 서버에서 받은 전체 행
        var modalEl = null;
        var bsModal = null;

        function listUrl(eventId){ return urlTemplate.replace('__ID__', eventId); }



        // 번호표 비교: 숫자 번호표는 숫자로(2 < 10), 숫자가 아니면 뒤로.
        function ticketKey(p){
            var n = String((p && p.name) || '');
            return /^\d+$/.test(n) ? [0, parseInt(n, 10), n] : [1, 0, n];
        }
        function byTicket(a, b){
            var ka = ticketKey(a.voter), kb = ticketKey(b.voter);
            for(var i = 0; i < ka.length; i++){
                if(ka[i] < kb[i]){ return -1; }
                if(ka[i] > kb[i]){ return 1; }
            }
            return 0;
        }

        // 남/여 칸 접힘 상태 (기본 펼침). 다시 그려도(검색·새로고침) 유지되고, 브라우저에 기억한다.
        var COLLAPSE_KEY = 'rotable.matchesModal.collapsed.';
        function isCollapsed(g){ try { return localStorage.getItem(COLLAPSE_KEY + g) === '1'; } catch(e) { return false; } }
        function setCollapsed(g, v){ try { localStorage.setItem(COLLAPSE_KEY + g, v ? '1' : '0'); } catch(e) {} }

        // 성별 한 칸: 머리(👨 남 · N명, 누르면 접기/펼치기) + 카드 목록
        function genderColumn(gender, label, cls, list, card){
            var collapsed = isCollapsed(gender);
            return '<div class="col-12 col-lg-6 mc-col' + (collapsed ? ' is-collapsed' : '') + '" data-gender="' + gender + '">'
                 +   '<button type="button" class="mc-col-head ' + cls + '" aria-expanded="' + (collapsed ? 'false' : 'true') + '">'
                 +     label + ' <span class="text-secondary fw-normal">· ' + list.length + '명</span>'
                 +     '<i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i>'
                 +   '</button>'
                 +   '<div class="mc-col-body">'
                 +     (list.length
                          ? '<div class="mc-col-list">' + list.map(card).join('') + '</div>'
                          : '<div class="mc-col-empty">투표 내역이 없습니다.</div>')
                 +   '</div>'
                 + '</div>';
        }

        function render(){
            var kw = ($('#match-search').val() || '').trim();
            var list = MatchCard.filter(rows, kw);
            var $l = $('#match-list');

            var males   = list.filter(function(r){ return r.voter && r.voter.gender !== 'F'; }).sort(byTicket);
            var females = list.filter(function(r){ return r.voter && r.voter.gender === 'F'; }).sort(byTicket);
            var card    = function(r){ return MatchCard.html(r, { countLabel: '선택' }); };

            if(!list.length){
                $l.html('<div class="mc-empty">표시할 투표 내역이 없습니다.</div>');
            } else {
                $l.html('<div class="row g-3">'
                    + genderColumn('M', '👨 남', 'bg-blue-lt', males, card)
                    + genderColumn('F', '👩 여', 'bg-pink-lt', females, card)
                    + '</div>');
            }
            $('#match-pageCount').text('총 ' + list.length + '명 (남 ' + males.length + ' · 여 ' + females.length + ')');
        }

        function load(){
            if(!currentEventId){ return; }
            $('#match-list').html('<div class="mc-empty">불러오는 중...</div>');
            // 화면 안에 "불러오는 중"을 보여주므로 전역 로딩 팝업(ajaxSetup beforeSend/complete)은 끈다.
            $.ajax({ url: listUrl(currentEventId), type: 'GET', beforeSend: $.noop, complete: $.noop }).done(function(res){
                rows = (res && res.data) ? res.data : [];
                render();
            }).fail(function(){
                rows = [];
                $('#match-list').html('<div class="mc-empty text-danger">불러오지 못했습니다.</div>');
                $('#match-pageCount').text('');
            });
        }

        // 검색 입력 · 남/여 칸 접기 바인딩 (1회)
        $(function(){
            $('#match-list').on('click', '.mc-col-head', function(){
                var $col = $(this).closest('.mc-col');
                var collapsed = !$col.hasClass('is-collapsed');
                $col.toggleClass('is-collapsed', collapsed);
                $(this).attr('aria-expanded', collapsed ? 'false' : 'true');
                setCollapsed($col.data('gender'), collapsed);
            });
            var t = null;
            $('#match-search').on('input', function(){ clearTimeout(t); t = setTimeout(render, 150); });
            $('#match-search-clear').on('click', function(){ $('#match-search').val(''); render(); });
        });

        return {
            refresh: function(){ load(); },
            open: function(eventId, eventLabel){
                if(!eventId){ return; }
                currentEventId = eventId;
                $('#match-eventLabel').text(eventLabel || '');
                $('#match-search').val('');
                $('#match-list').empty();
                $('#match-pageCount').text('');

                if(!modalEl){
                    modalEl = document.getElementById('matchesModal');
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
                load();
            },
        };
    })();
</script>
