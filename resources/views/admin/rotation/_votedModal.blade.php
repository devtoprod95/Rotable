{{--
    매칭 조회 모달 (특정 회원이 투표한 상대 + 매칭).
    - 특정 회원이 그 행사에서 "선택(투표)한 상대들"을 상세 정보와 함께 보여준다.
    - 상대도 그 회원을 지목(상호 매칭)한 경우 뱃지로 표시한다.
    - 사용법(JS): VotedModal.open(eventId, memberId, memberLabel)
--}}
@php
    $votedUrlTemplate = route('admin.rotation.event.members.voted.list', ['id' => '__ID__', 'memberId' => '__MID__']);
@endphp

<div class="modal modal-blur fade" id="votedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">매칭 조회 (선택한 상대)</h5>
                    <small class="text-muted" id="voted-memberLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-center mb-2">
                    <span id="voted-pageCount" class="badge bg-secondary-lt"></span>
                    <small class="text-muted ms-2">이 회원이 선택(투표)한 상대 목록입니다. (서로 선택 시 <span class="badge bg-pink-lt">매칭</span>)</small>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="VotedModal.refresh()">
                        <i class="ti ti-refresh me-1"></i> 새로고침
                    </button>
                </div>

                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" id="voted-search" class="form-control" placeholder="이름 / 전화번호 / 직업으로 검색" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="voted-search-clear" title="검색 초기화">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div id="voted-table"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.VotedModal = (function(){
        var urlTemplate = @json($votedUrlTemplate);

        var table = null;
        var currentEventId = null;
        var currentMemberId = null;
        var modalEl = null;
        var bsModal = null;

        function listUrl(eventId, memberId){
            return urlTemplate.replace('__ID__', eventId).replace('__MID__', memberId);
        }

        function genderBadge(gender){
            if(gender === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(gender === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

        function nameCell(cell){
            var d = cell.getRow().getData();
            var n = d.name ? $('<div>').text(d.name).html() : '-';
            var mutual = d.is_mutual
                ? ' <span class="badge bg-pink-lt"><i class="ti ti-heart-filled text-pink"></i> 매칭</span>'
                : '';
            return `<div><span class="fw-bold">${n}</span> ${genderBadge(d.gender)}${mutual}</div>`;
        }

        function buildTable(eventId, memberId){
            table = new Tabulator("#voted-table", {
                ajaxURL: listUrl(eventId, memberId),
                layout: "fitColumns",
                height: "55vh",
                pagination: false,
                placeholder: "이 회원이 선택한 상대가 없습니다.",
                columns: [
                    { title: "이름", field: "name", minWidth: 150, headerSort: false,
                        formatter: nameCell },
                    { title: "직업", field: "job", width: 130, headerSort: false,
                        formatter: function(cell){ var v = cell.getValue(); return v ? $('<div>').text(v).html() : '-'; } },
                    { title: "번호", field: "phone", width: 140, headerSort: false,
                        formatter: function(cell){ var v = cell.getValue(); return v ? $('<div>').text(v).html() : '-'; } },
                    { title: "나이", field: "age", width: 90, hozAlign: "center", headerSort: false,
                        formatter: function(cell){
                            var d = cell.getRow().getData();
                            if(!d.age){ return '-'; }
                            var by = d.birth_year ? `<div style="font-size:0.8em;color:#888;">${d.birth_year}년생</div>` : '';
                            return `<div>${d.age}세${by}</div>`;
                        }
                    },
                    { title: "키", field: "height", width: 80, hozAlign: "center", headerSort: false,
                        formatter: function(cell){ var v = cell.getValue(); return v ? (v + 'cm') : '-'; } },
                ],
                ajaxResponse: function(url, params, response){
                    return (response && response.data) ? response.data : [];
                },
                dataLoaded: function(){ applySearch(); },
                dataFiltered: function(filters, rows){
                    $('#voted-pageCount').text('총 ' + rows.length + '명');
                },
            });
        }

        function searchFilter(data, keyword){
            if(!keyword){ return true; }
            var kw = keyword.toLowerCase();
            return [data.name, data.phone, data.job]
                .some(function(v){ return v && String(v).toLowerCase().indexOf(kw) !== -1; });
        }

        function applySearch(){
            if(!table){ return; }
            var kw = ($('#voted-search').val() || '').trim();
            if(kw === ''){ table.clearFilter(); }
            else { table.setFilter(searchFilter, kw); }
        }

        function reload(){
            if(!table || !currentEventId || !currentMemberId){ return; }
            table.setData(listUrl(currentEventId, currentMemberId));
        }

        $(function(){
            var t = null;
            $('#voted-search').on('input', function(){ clearTimeout(t); t = setTimeout(applySearch, 150); });
            $('#voted-search-clear').on('click', function(){ $('#voted-search').val(''); applySearch(); });
        });

        return {
            refresh: function(){ reload(); },
            open: function(eventId, memberId, memberLabel){
                if(!eventId || !memberId){ return; }
                currentEventId = eventId;
                currentMemberId = memberId;
                $('#voted-memberLabel').text(memberLabel || '');
                $('#voted-search').val('');

                if(!modalEl){
                    modalEl = document.getElementById('votedModal');
                    var ModalClass =
                        (window.tabler && window.tabler.Modal) ||
                        (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                        (window.bootstrap && window.bootstrap.Modal) ||
                        (window.coreui && window.coreui.Modal) ||
                        window.Modal;
                    if(!ModalClass){ console.error('Modal 클래스를 찾을 수 없습니다.'); return; }
                    bsModal = new ModalClass(modalEl);
                    var onShown = function(){
                        if(!currentEventId || !currentMemberId){ return; }
                        if(!table){ buildTable(currentEventId, currentMemberId); }
                        else { table.setData(listUrl(currentEventId, currentMemberId)); }
                        var el = document.getElementById('voted-table');
                        if(table && el && el.offsetParent !== null){ try { table.redraw(true); } catch(e){} }
                    };
                    modalEl.addEventListener('shown.bs.modal', onShown);
                    modalEl.addEventListener('shown.coreui.modal', onShown);
                }

                bsModal.show();

                var elNow = document.getElementById('voted-table');
                if(elNow && elNow.offsetParent !== null){
                    if(!table){ buildTable(currentEventId, currentMemberId); }
                    else { table.setData(listUrl(currentEventId, currentMemberId)); }
                    try { table.redraw(true); } catch(e){}
                }
            },
        };
    })();
</script>
