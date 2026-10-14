{{--
    재사용 메시지 조회 모달.
    - 페이지 이동 없이 모달에서 특정 행사의 메시지를 조회한다.
    - Reverb(Pusher 프로토콜)로 실시간 수신한다. pusher-js 는 layouts/app.blade.php 에서 공통 로드.
    - 사용법(JS): MessagesModal.open(eventId, eventLabel)
    - messages.list 라우트는 {id} 를 치환해서 사용하므로 플레이스홀더(__ID__)를 둔다.
--}}
@php
    $reverbCfg = [
        'key'    => config('broadcasting.connections.reverb.key'),
        'host'   => env('VITE_REVERB_HOST', env('REVERB_HOST')),
        'port'   => (int) env('VITE_REVERB_PORT', env('REVERB_PORT', 443)),
        'scheme' => env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https')),
    ];
    // {id} 자리에 __ID__ 를 넣어 JS에서 치환할 수 있는 URL 템플릿을 만든다.
    $messagesUrlTemplate = route('admin.rotation.event.messages.list', ['id' => '__ID__']);
@endphp

<div class="modal modal-blur fade" id="messagesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 align-items-start">
                <div>
                    <h5 class="modal-title mb-0">
                        <span id="mm-modalTitleText">메시지 조회</span>
                        <span id="mm-liveBadge" class="badge bg-secondary-lt ms-2" title="실시간 연결 상태">
                            <i class="ti ti-circle-filled me-1 text-muted" style="font-size:0.6em;"></i>실시간 대기
                        </span>
                    </h5>
                    <small class="text-muted" id="mm-eventLabel"></small>
                </div>
                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="mm-searchForm" class="mb-2">
                    <div class="d-flex gap-2 align-items-end flex-wrap">
                        <div style="min-width: 130px;">
                            <label class="form-label mb-1">읽음 여부</label>
                            <select class="form-select" name="read">
                                <option value="">전체</option>
                                <option value="Y">읽음</option>
                                <option value="N">안읽음</option>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label mb-1">검색어</label>
                            <input type="text" class="form-control" name="searchText"
                                   placeholder="보낸사람 / 받는사람 이름 / 내용 검색"
                                   onkeydown="if(event.keyCode === 13){ event.preventDefault(); MessagesModal.search(); }">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary px-3" onclick="MessagesModal.search()">검색</button>
                            <button type="button" class="btn btn-outline-secondary px-3" onclick="MessagesModal.search()">
                                <i class="ti ti-refresh me-1"></i> 새로고침
                            </button>
                        </div>
                    </div>
                </form>

                <div class="d-flex align-items-center mb-2">
                    <span id="mm-pageCount" class="badge bg-secondary-lt"></span>
                </div>

                <div id="mm-table"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.MessagesModal = (function(){
        var urlTemplate = @json($messagesUrlTemplate);
        var reverbCfg   = @json($reverbCfg);

        var table = null;
        var pusher = null;
        var currentChannel = null;
        var currentEventId = null;
        var currentSenderId = null;
        var modalEl = null;
        var bsModal = null;

        function listUrl(eventId){ return urlTemplate.replace('__ID__', eventId); }

        function genderBadge(gender){
            if(gender === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(gender === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }
        function nameWithGender(name, gender){
            var n = name ? $('<div>').text(name).html() : '-';
            return `<span class="fw-bold">${n}</span> ${genderBadge(gender)}`;
        }

        function setLive(state){
            var $b = $('#mm-liveBadge');
            if(state === 'on'){ $b.removeClass('bg-secondary-lt bg-red-lt').addClass('bg-green-lt').html('<i class="ti ti-circle-filled me-1 text-green" style="font-size:0.6em;"></i>실시간 연결됨'); }
            else if(state === 'off'){ $b.removeClass('bg-secondary-lt bg-green-lt').addClass('bg-red-lt').html('<i class="ti ti-circle-filled me-1 text-red" style="font-size:0.6em;"></i>연결 끊김'); }
            else { $b.removeClass('bg-green-lt bg-red-lt').addClass('bg-secondary-lt').html('<i class="ti ti-circle-filled me-1 text-muted" style="font-size:0.6em;"></i>실시간 대기'); }
        }

        function filters(){
            var f = {
                searchText: $('#mm-searchForm [name=searchText]').val(),
                read: $('#mm-searchForm [name=read]').val(),
            };
            if(currentSenderId){ f.senderId = currentSenderId; }
            return f;
        }

        function matchesFilter(d){
            var f = filters();
            // 특정 발신자 전용 조회 중이면 그 사람이 보낸 것만 실시간 반영.
            if(currentSenderId && d.sender && Number(d.sender.id) !== Number(currentSenderId)){ return false; }
            if(f.read === 'Y' && !d.read_at){ return false; }
            if(f.read === 'N' && d.read_at){ return false; }
            var kw = (f.searchText || '').trim().toLowerCase();
            if(kw){
                var hay = [
                    d.sender ? d.sender.name : '',
                    d.receiver ? d.receiver.name : '',
                    d.body || ''
                ].join(' ').toLowerCase();
                if(hay.indexOf(kw) === -1){ return false; }
            }
            return true;
        }

        function buildTable(eventId){
            table = new Tabulator("#mm-table", {
                ajaxURL: listUrl(eventId),
                ajaxParams: filters,
                layout: "fitColumns",
                height: "55vh",
                initialSort: [{column: "id", dir: "desc"}],
                columns: [
                    { title: "No", field: "id", width: 70, hozAlign: "center" },
                    { title: "보낸사람 → 받는사람", field: "sender_id", width: 200, headerSort: false,
                        formatter: function(cell){
                            var d = cell.getRow().getData();
                            var from = nameWithGender(d.sender ? d.sender.name : null, d.sender ? d.sender.gender : null);
                            var to   = nameWithGender(d.receiver ? d.receiver.name : null, d.receiver ? d.receiver.gender : null);
                            return `<div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span>${from}</span>
                                        <i class="ti ti-arrow-right text-muted"></i>
                                        <span>${to}</span>
                                    </div>`;
                        }
                    },
                    { title: "내용", field: "body",
                        formatter: function(cell){ return $('<div>').text(cell.getValue() || '').html(); }
                    },
                    { title: "읽음", field: "read_at", width: 170, hozAlign: "center", headerSort: false,
                        formatter: function(cell){
                            var v = cell.getValue();
                            if(v){
                                var t = new Date(v).toLocaleString('ko-KR');
                                return `<span class="badge bg-green-lt">읽음</span><div style="font-size:0.8em;color:#888;">${t}</div>`;
                            }
                            return '<span class="badge bg-red-lt">안읽음</span>';
                        }
                    },
                    { title: "보낸 시각", field: "created_at", width: 170, hozAlign: "center",
                        formatter: function(cell){
                            if(!cell.getValue()){ return '-'; }
                            var s = new Date(cell.getValue()).toLocaleString('ko-KR');
                            // "2026. 10. 13. 오전 11:34:00" → 오전/오후 앞에서 줄바꿈
                            return s.replace(/\s(오전|오후)/, '<br>$1');
                        }
                    },
                ],
                ajaxResponse: function(url, params, response){
                    var total = response.total ?? (response.data ? response.data.length : 0);
                    $('#mm-pageCount').text('총 ' + total + '건');
                    return response;
                },
            });
        }

        function handleNewMessage(d){
            if(!table || !d || !matchesFilter(d)){ return; }
            if(table.getRow(d.id)){ return; }
            table.addData([d], true).then(function(){
                var row = table.getRow(d.id);
                if(row){
                    var el = row.getElement();
                    el.style.transition = 'background-color 1.5s';
                    el.style.backgroundColor = '#fff3bf';
                    setTimeout(function(){ el.style.backgroundColor = ''; }, 1500);
                }
                var cur = parseInt(($('#mm-pageCount').text().match(/\d+/) || [0])[0], 10) || 0;
                $('#mm-pageCount').text('총 ' + (cur + 1) + '건');
            });
        }

        function ensurePusher(){
            if(pusher){ return pusher; }
            if(typeof Pusher === 'undefined' || !reverbCfg.key || !reverbCfg.host){ setLive('off'); return null; }
            try {
                pusher = new Pusher(reverbCfg.key, {
                    wsHost: reverbCfg.host,
                    wsPort: reverbCfg.port || 80,
                    wssPort: reverbCfg.port || 443,
                    forceTLS: reverbCfg.scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                    cluster: '',
                });
                pusher.connection.bind('connected', function(){ setLive('on'); });
                pusher.connection.bind('disconnected', function(){ setLive('off'); });
                pusher.connection.bind('unavailable', function(){ setLive('off'); });
                pusher.connection.bind('error', function(){ setLive('off'); });
            } catch(e){
                console.error('Reverb 연결 실패:', e);
                setLive('off');
            }
            return pusher;
        }

        function subscribe(eventId){
            var p = ensurePusher();
            if(!p){ return; }
            if(currentChannel){ p.unsubscribe(currentChannel); currentChannel = null; }
            currentChannel = 'event-messages.' + eventId;
            var channel = p.subscribe(currentChannel);
            channel.bind('message.sent', handleNewMessage);
            if(p.connection.state === 'connected'){ setLive('on'); }
        }

        function unsubscribe(){
            if(pusher && currentChannel){ pusher.unsubscribe(currentChannel); }
            currentChannel = null;
        }

        return {
            search: function(){
                if(!table || !currentEventId){ return; }
                table.setData(listUrl(currentEventId), filters());
            },
            open: function(eventId, eventLabel, options){
                if(!eventId){ return; }
                var opts = options || {};
                currentEventId = eventId;
                currentSenderId = opts.senderId || null;
                $('#mm-eventLabel').text(eventLabel || '');
                $('#mm-searchForm')[0].reset();
                // 특정 발신자 전용 조회면 제목에 표시.
                $('#mm-modalTitleText').text(currentSenderId ? '보낸 메시지' : '메시지 조회');
                setLive('대기');

                if(!modalEl){
                    modalEl = document.getElementById('messagesModal');
                    // 이 프로젝트는 Tabler 번들(UMD)을 쓴다. 번들은 window.tabler 네임스페이스로
                    // 노출되며 Modal 클래스는 window.tabler.Modal 또는 window.tabler.bootstrap.Modal 이다.
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
                    // 모달 닫힐 때 구독 해제
                    modalEl.addEventListener('hidden.bs.modal', function(){ unsubscribe(); });
                    modalEl.addEventListener('hidden.coreui.modal', function(){ unsubscribe(); });
                    // 모달이 완전히 표시된 뒤에 테이블을 생성/갱신한다. (숨김 상태 생성 시 레이아웃 null 에러 방지)
                    var onShown = function(){
                        if(!currentEventId){ return; }
                        if(!table){ buildTable(currentEventId); }
                        else { table.setData(listUrl(currentEventId), filters()); }
                        subscribe(currentEventId);
                        var el = document.getElementById('mm-table');
                        if(table && el && el.offsetParent !== null){ try { table.redraw(true); } catch(e){} }
                    };
                    modalEl.addEventListener('shown.bs.modal', onShown);
                    modalEl.addEventListener('shown.coreui.modal', onShown);
                }

                bsModal.show();

                // 이미 열려 있으면 shown 이 재발생하지 않으므로 보이는 경우 즉시 갱신.
                var elNow = document.getElementById('mm-table');
                if(elNow && elNow.offsetParent !== null){
                    if(!table){ buildTable(currentEventId); }
                    else { table.setData(listUrl(currentEventId), filters()); }
                    subscribe(currentEventId);
                    try { table.redraw(true); } catch(e){}
                }
            },
        };
    })();
</script>
