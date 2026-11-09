{{--
    받은 투표수 조회 모달.
    - 특정 회원을 "선택(지목)한 사람들"을 상세 정보(이름/직업/전화번호/나이/키)와 함께 보여준다.
    - 상호 매칭(그 회원도 상대를 지목)인 경우 뱃지로 표시한다.
    - 사용법(JS): VotersModal.open(eventId, memberId, memberLabel)
    - voters.list 라우트는 {id}/{memberId} 를 치환해서 사용하므로 플레이스홀더(__ID__, __MID__)를 둔다.
--}}
@php
    $votersUrlTemplate = route('admin.rotation.event.members.voters.list', ['id' => '__ID__', 'memberId' => '__MID__']);
@endphp

<style>
    /* votersModal: 엑셀(격자) 느낌 제거 + 모바일 카드화 */
    #votersModal .tabulator { border: 1px solid #e9ecf1; border-radius: 10px; }
    #votersModal .tabulator .tabulator-header { background: #f6f8fb; border-bottom: 1px solid #e9ecf1; }
    #votersModal .tabulator .tabulator-header .tabulator-col { background: transparent; border-right: none; font-weight: 700; color: #495057; }
    #votersModal .tabulator .tabulator-row .tabulator-cell { border-right: none; padding: 12px 14px; }
    #votersModal .tabulator .tabulator-row { border-bottom: 1px solid #f0f2f6; }
    #votersModal .tabulator .tabulator-row.tabulator-row-even { background: #fcfcfd; }
    #votersModal .tabulator .tabulator-row:hover { background: #f3f7ff; }
    @media (max-width: 767.98px) {
        #votersModal .tabulator .tabulator-header { display: none; }
        #votersModal .tabulator { border: none; }
        #votersModal .tabulator .tabulator-tableholder { overflow-x: hidden !important; }
        #votersModal .tabulator .tabulator-row {
            display: block; width: 100% !important; border: 1px solid #e9ecf1; border-radius: 12px;
            background: #fff !important; box-shadow: 0 1px 3px rgba(20,30,60,.05); margin-bottom: 12px; padding: 6px 4px;
        }
        #votersModal .tabulator .tabulator-row .tabulator-cell {
            display: flex !important; width: 100% !important; white-space: normal !important; height: auto !important;
            border: none !important; padding: 7px 12px !important; text-align: left !important;
        }
        #votersModal .tabulator .tabulator-row .tabulator-cell::before {
            content: attr(data-mlabel); flex: 0 0 60px; color: #8a92a0; font-weight: 700; font-size: 0.82rem;
        }
    }
</style>

<div class="modal modal-blur fade" id="votersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">받은 투표 (나를 선택한 사람)</h5>
                    <small class="text-muted" id="voter-memberLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex align-items-center mb-2">
                    <span id="voter-pageCount" class="badge bg-secondary-lt"></span>
                    <small class="text-muted ms-2">이 회원을 선택한 사람 목록입니다. (서로 선택 시 <span class="badge bg-pink-lt">매칭</span>)</small>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="VotersModal.refresh()">
                        <i class="ti ti-refresh me-1"></i> 새로고침
                    </button>
                </div>

                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" id="voter-search" class="form-control" placeholder="이름 / 전화번호 / 직업으로 검색" autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="voter-search-clear" title="검색 초기화">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div id="voter-table"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.VotersModal = (function(){
        var urlTemplate = @json($votersUrlTemplate);

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
            var n = $('<div>').text(d.label || (d.name ? d.name + '호' : '-')).html();
            var mutual = d.is_mutual
                ? ' <span class="badge bg-pink-lt"><i class="ti ti-heart-filled text-pink"></i> 매칭</span>'
                : '';
            return `<div><span class="fw-bold">${n}</span> ${genderBadge(d.gender)}${mutual}</div>`;
        }

        function buildTable(eventId, memberId){
            table = new Tabulator("#voter-table", {
                ajaxURL: listUrl(eventId, memberId),
                layout: "fitColumns",
                height: "55vh",
                pagination: false,
                resizableColumns: false,
                placeholder: "아직 이 회원을 선택한 사람이 없습니다.",
                rowFormatter: function(row){
                    var labels = { name: '이름', job: '직업', phone: '번호', age: '나이', height: '키' };
                    row.getCells().forEach(function(cell){
                        var f = cell.getField();
                        if(f && labels[f]){ cell.getElement().setAttribute('data-mlabel', labels[f]); }
                    });
                },
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
                    $('#voter-pageCount').text('총 ' + rows.length + '명');
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
            var kw = ($('#voter-search').val() || '').trim();
            if(kw === ''){
                table.clearFilter();
            } else {
                table.setFilter(searchFilter, kw);
            }
        }

        function reload(){
            if(!table || !currentEventId || !currentMemberId){ return; }
            table.setData(listUrl(currentEventId, currentMemberId));
        }

        // 검색 입력 이벤트 바인딩 (스크립트 로드 시 1회).
        $(function(){
            var t = null;
            $('#voter-search').on('input', function(){
                clearTimeout(t);
                t = setTimeout(applySearch, 150);
            });
            $('#voter-search-clear').on('click', function(){
                $('#voter-search').val('');
                applySearch();
            });
        });

        return {
            refresh: function(){ reload(); },
            open: function(eventId, memberId, memberLabel){
                if(!eventId || !memberId){ return; }
                currentEventId = eventId;
                currentMemberId = memberId;
                $('#voter-memberLabel').text(memberLabel || '');
                $('#voter-search').val('');

                if(!modalEl){
                    modalEl = document.getElementById('votersModal');
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
                    // 모달이 완전히 표시된 뒤에 테이블을 생성/갱신한다. (숨김 상태 생성 시 레이아웃 null 에러 방지)
                    var onShown = function(){
                        if(!currentEventId || !currentMemberId){ return; }
                        if(!table){ buildTable(currentEventId, currentMemberId); }
                        else { table.setData(listUrl(currentEventId, currentMemberId)); }
                        var el = document.getElementById('voter-table');
                        if(table && el && el.offsetParent !== null){ try { table.redraw(true); } catch(e){} }
                    };
                    modalEl.addEventListener('shown.bs.modal', onShown);
                    modalEl.addEventListener('shown.coreui.modal', onShown);
                }

                bsModal.show();

                // 이미 열려 있으면 shown 이 재발생하지 않으므로 보이는 경우 즉시 갱신.
                var elNow = document.getElementById('voter-table');
                if(elNow && elNow.offsetParent !== null){
                    if(!table){ buildTable(currentEventId, currentMemberId); }
                    else { table.setData(listUrl(currentEventId, currentMemberId)); }
                    try { table.redraw(true); } catch(e){}
                }
            },
        };
    })();
</script>
