{{--
    재사용 매칭(투표) 조회 모달.
    - 페이지 이동 없이 특정 행사의 투표 현황을 조회한다. (쌍방 여부 무관, 누가 누구를 선택했는지 전부)
    - 상호 매칭(상대도 나를 선택)인 경우 뱃지로 표시한다.
    - 자동 갱신 없음. 모달을 열거나 새로고침 버튼을 누를 때만 조회한다.
    - 사용법(JS): MatchesModal.open(eventId, eventLabel)
    - matches.list 라우트는 {id} 를 치환해서 사용하므로 플레이스홀더(__ID__)를 둔다.
--}}
@php
    $matchesUrlTemplate = route('admin.rotation.event.matches.list', ['id' => '__ID__']);
@endphp

<div class="modal modal-blur fade" id="matchesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">매칭 조회</h5>
                    <small class="text-muted" id="match-eventLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-center mb-2">
                    <span id="match-pageCount" class="badge bg-secondary-lt"></span>
                    <small class="text-muted ms-2">누가 누구를 선택했는지 전부 표시됩니다. (서로 선택 시 <span class="badge bg-pink-lt">상호 매칭</span>)</small>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="MatchesModal.refresh()">
                        <i class="ti ti-refresh me-1"></i> 새로고침
                    </button>
                </div>

                <div id="match-table"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.MatchesModal = (function(){
        var urlTemplate = @json($matchesUrlTemplate);

        var table = null;
        var currentEventId = null;
        var modalEl = null;
        var bsModal = null;

        function listUrl(eventId){ return urlTemplate.replace('__ID__', eventId); }

        function genderBadge(gender){
            if(gender === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(gender === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }
        function memberCell(m){
            if(!m){ return '-'; }
            var n = m.name ? $('<div>').text(m.name).html() : '-';
            var phone = m.phone ? `<div style="font-size:0.8em;color:#888;">${$('<div>').text(m.phone).html()}</div>` : '';
            return `<div><span class="fw-bold">${n}</span> ${genderBadge(m.gender)}${phone}</div>`;
        }

        function buildTable(eventId){
            table = new Tabulator("#match-table", {
                ajaxURL: listUrl(eventId),
                layout: "fitColumns",
                height: "55vh",
                pagination: false,
                placeholder: "아직 투표 내역이 없습니다.",
                columns: [
                    { title: "선택한 사람", field: "voter", headerSort: false,
                        formatter: function(cell){ return memberCell(cell.getValue()); } },
                    { title: "", width: 110, hozAlign: "center", headerSort: false,
                        formatter: function(cell){
                            var d = cell.getRow().getData();
                            if(d.is_mutual){
                                return '<i class="ti ti-heart-filled text-pink"></i><div><span class="badge bg-pink-lt mt-1">상호 매칭</span></div>';
                            }
                            return '<i class="ti ti-arrow-right text-muted"></i>';
                        }
                    },
                    { title: "선택받은 사람", field: "target", headerSort: false,
                        formatter: function(cell){ return memberCell(cell.getValue()); } },
                    { title: "투표 시각", field: "voted_at", width: 170, hozAlign: "center", headerSort: false,
                        formatter: function(cell){
                            if(!cell.getValue()){ return '-'; }
                            var s = new Date(cell.getValue()).toLocaleString('ko-KR');
                            return s.replace(/\s(오전|오후)/, '<br>$1');
                        }
                    },
                ],
                ajaxResponse: function(url, params, response){
                    var rows = (response && response.data) ? response.data : [];
                    $('#match-pageCount').text('총 ' + rows.length + '건');
                    return rows;
                },
            });
        }

        function reload(){
            if(!table || !currentEventId){ return; }
            table.setData(listUrl(currentEventId));
        }

        return {
            refresh: function(){ reload(); },
            open: function(eventId, eventLabel){
                if(!eventId){ return; }
                currentEventId = eventId;
                $('#match-eventLabel').text(eventLabel || '');

                if(!modalEl){
                    modalEl = document.getElementById('matchesModal');
                    var ModalClass =
                        (window.tabler && window.tabler.Modal) ||
                        (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                        (window.bootstrap && window.bootstrap.Modal) ||
                        (window.coreui && window.coreui.Modal) ||
                        window.Modal;
                    if(!ModalClass){
                        console.error('Modal 클래스를 찾을 수 없습니다.');
                        return;
                    }
                    bsModal = new ModalClass(modalEl);
                }

                bsModal.show();

                setTimeout(function(){
                    if(!table){ buildTable(eventId); }
                    else { table.setData(listUrl(eventId)); }
                    if(table){ table.redraw(true); }
                }, 200);
            },
        };
    })();
</script>
