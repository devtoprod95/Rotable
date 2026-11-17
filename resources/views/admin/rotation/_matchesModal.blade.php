{{--
    재사용 "매칭 조회" 모달. (전체 투표: 누가 누구를 선택했는지 전부)
    - 페이지네이션이 없으므로 테이블 라이브러리 없이 마크업으로 직접 렌더한다.
    - 상호 매칭(상대도 나를 선택)인 경우 하트·분홍 배경으로 표시한다.
    - 카드 렌더는 _matchCard(매칭 결과 모달과 공용). 년생·직업·키·사는 곳·핸드폰·투표 시각을 보여주고 MBTI·흡연·생활습관은 뺀다.
    - 사용법(JS): MatchesModal.open(eventId, eventLabel)
    - matches.list 라우트는 {id} 를 치환해서 사용하므로 플레이스홀더(__ID__)를 둔다.
--}}
@php
    $matchesUrlTemplate = route('admin.rotation.event.matches.list', ['id' => '__ID__']);
@endphp

@include('admin.rotation._matchCard')

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
                    <small class="text-muted">번호표 순 · 서로 선택하면 <span class="badge bg-pink-lt">매칭</span></small>
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

                <div id="match-list" class="mc-list" aria-live="polite"></div>
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



        function render(){
            var kw = ($('#match-search').val() || '').trim();
            var list = MatchCard.filter(rows, kw);
            var $l = $('#match-list');
            if(!list.length){
                $l.html('<div class="mc-empty">표시할 투표 내역이 없습니다.</div>');
            } else {
                $l.html(list.map(function(r){ return MatchCard.html(r, { countLabel: '선택' }); }).join(''));
            }
            $('#match-pageCount').text('총 ' + list.length + '명');
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

        // 검색 입력 바인딩 (1회)
        $(function(){
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
