{{--
    재사용 "매칭 결과" 모달. (상호 매칭된 쌍만, 남자 기준)
    - 카드 렌더는 _matchCard(매칭 조회 모달과 공용). 남자 1명 = 카드 1장, 매칭된 상대를 한 줄씩.
    - 사용법(JS): MatchedModal.open(eventId, eventLabel)
    - matches.list 라우트에 ?matched=1 을 붙여 매칭된 쌍만 받는다.
--}}
@php
    $matchedUrlTemplate = route('admin.rotation.event.matches.list', ['id' => '__ID__']);
@endphp
@include('admin.rotation._matchCard')

<div class="modal fade" id="matchedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">매칭 결과</h5>
                    <small class="text-muted" id="matched-eventLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-center mb-2 flex-wrap gap-1">
                    <span id="matched-pageCount" class="badge bg-secondary-lt"></span>
                    <small class="text-muted">서로 선택한 쌍만 · 남자 기준 · 번호표 순</small>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="MatchedModal.refresh()">
                        <i class="ti ti-refresh me-1"></i> 새로고침
                    </button>
                </div>

                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" id="matched-search" class="form-control" placeholder="번호표 / 닉네임 / 직업 / 지역 / 핸드폰으로 검색" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="matched-search-clear" title="검색 초기화">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div id="matched-list" class="mc-list" aria-live="polite"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.MatchedModal = (function(){
        var urlTemplate = @json($matchedUrlTemplate);

        var currentEventId = null;
        var rows = [];
        var modalEl = null;
        var bsModal = null;

        function listUrl(eventId){ return urlTemplate.replace('__ID__', eventId) + '?matched=1'; }


        function render(){
            var kw = ($('#matched-search').val() || '').trim();
            var list = MatchCard.filter(rows, kw);
            var $l = $('#matched-list');
            if(!list.length){
                $l.html('<div class="mc-empty">매칭된 쌍이 없습니다.</div>');
            } else {
                $l.html(list.map(function(r){ return MatchCard.html(r, { countLabel: '매칭' }); }).join(''));
            }
            // 쌍 수 = 각 남자(행)가 매칭된 상대(targets) 수의 합.
            var pairCount = list.reduce(function(sum, r){ return sum + ((r.targets || []).length); }, 0);
            $('#matched-pageCount').text('총 ' + pairCount + '쌍');
        }

        function load(){
            if(!currentEventId){ return; }
            $('#matched-list').html('<div class="mc-empty">불러오는 중...</div>');
            // 화면 안에 "불러오는 중"을 보여주므로 전역 로딩 팝업(ajaxSetup beforeSend/complete)은 끈다.
            $.ajax({ url: listUrl(currentEventId), type: 'GET', beforeSend: $.noop, complete: $.noop }).done(function(res){
                rows = (res && res.data) ? res.data : [];
                render();
            }).fail(function(){
                rows = [];
                $('#matched-list').html('<div class="mc-empty text-danger">불러오지 못했습니다.</div>');
                $('#matched-pageCount').text('');
            });
        }

        $(function(){
            var t = null;
            $('#matched-search').on('input', function(){ clearTimeout(t); t = setTimeout(render, 150); });
            $('#matched-search-clear').on('click', function(){ $('#matched-search').val(''); render(); });
        });

        return {
            refresh: function(){ load(); },
            open: function(eventId, eventLabel){
                if(!eventId){ return; }
                currentEventId = eventId;
                $('#matched-eventLabel').text(eventLabel || '');
                $('#matched-search').val('');
                $('#matched-list').empty();
                $('#matched-pageCount').text('');

                if(!modalEl){
                    modalEl = document.getElementById('matchedModal');
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
