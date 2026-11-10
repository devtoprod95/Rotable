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
    $recipientsUrlTemplate = route('admin.rotation.event.messages.recipients', ['id' => '__ID__']);
    $sendUrlTemplate = route('admin.rotation.event.messages.send', ['id' => '__ID__']);
@endphp

<style>
    /* 메시지 모달: Tabulator 의 "엑셀(격자)" 느낌을 걷어내고 일반 테이블처럼 보이게 한다.
       - 셀의 세로 경계선 제거(가로줄만 남김)
       - 헤더는 연한 회색 배경 + 굵은 글씨로 깔끔하게
       - 행 호버 하이라이트 */
    #messagesModal .tabulator { border: 1px solid #e9ecf1; border-radius: 10px; }
    #messagesModal .tabulator .tabulator-header { background: #f6f8fb; border-bottom: 1px solid #e9ecf1; }
    #messagesModal .tabulator .tabulator-header .tabulator-col {
        background: transparent; border-right: none; font-weight: 700; color: #495057;
    }
    #messagesModal .tabulator .tabulator-row .tabulator-cell {
        border-right: none;            /* 세로 격자선 제거 */
        padding: 12px 14px;            /* 넉넉한 여백 */
    }
    #messagesModal .tabulator .tabulator-row { border-bottom: 1px solid #f0f2f6; }
    #messagesModal .tabulator .tabulator-row.tabulator-row-even { background: #fcfcfd; }
    #messagesModal .tabulator .tabulator-row:hover { background: #f3f7ff; }

    /* ── 모바일: 표를 "세로 카드"처럼 재배치 (PC 는 위 스타일 그대로) ──
       각 행을 카드로, 각 셀을 "라벨 : 값" 한 줄로 쌓는다. Tabulator 가 셀에 붙여주는
       tabulator-field 속성으로 각 항목 앞에 라벨을 CSS 로 표시한다. */
    @media (max-width: 767.98px) {
        #messagesModal .tabulator .tabulator-header { display: none; }  /* 헤더 숨김 */
        #messagesModal .tabulator { border: none; }
        #messagesModal .tabulator .tabulator-tableholder { overflow-x: hidden !important; }
        #messagesModal .tabulator .tabulator-row {
            display: block; width: 100% !important;
            border: 1px solid #e9ecf1; border-radius: 12px;
            background: #fff !important; box-shadow: 0 1px 3px rgba(20,30,60,.05);
            margin-bottom: 12px; padding: 6px 4px;
        }
        #messagesModal .tabulator .tabulator-row .tabulator-cell {
            display: flex !important; width: 100% !important;
            white-space: normal !important; height: auto !important;
            border: none !important; padding: 7px 12px !important;
            text-align: left !important;
        }
        /* 각 셀 앞 라벨 */
        #messagesModal .tabulator .tabulator-row .tabulator-cell::before {
            content: attr(data-mlabel);
            flex: 0 0 72px; color: #8a92a0; font-weight: 700; font-size: 0.82rem;
        }
        /* '내용' 셀은 라벨 없이 넓게 (제일 중요) */
        #messagesModal .tabulator .tabulator-row .tabulator-cell[tabulator-field="body"] {
            flex-direction: column; background: #f4f7ff; border-radius: 10px;
            margin: 2px 8px; padding: 10px 12px !important;
        }
        #messagesModal .tabulator .tabulator-row .tabulator-cell[tabulator-field="body"]::before {
            flex: none; margin-bottom: 4px;
        }
    }
</style>

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
                <div class="card card-body bg-light border-0 mb-3 p-3" id="mm-sendBox">
                    <div class="fw-bold mb-2"><i class="ti ti-send me-1"></i>관리자 메시지 보내기 <small class="text-muted fw-normal">(참가자에게는 익명으로 표시됩니다)</small></div>
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <select class="form-select" id="mm-sendTarget">
                                <option value="all">참가자 전체</option>
                                <option value="M">남자 전체</option>
                                <option value="F">여자 전체</option>
                                <option value="members">개별 선택</option>
                            </select>
                            <div class="mt-2 d-none" id="mm-sendMembersWrap">
                                <select id="mm-sendMembers" multiple></select>
                            </div>
                        </div>
                        <div class="col-12 col-md">
                            <textarea class="form-control" id="mm-sendBody" rows="3" maxlength="1000" autocomplete="off" placeholder="보낼 메시지 내용"></textarea>
                        </div>
                        <div class="col-12 col-md-auto">
                            <button type="button" class="btn btn-primary w-100" id="mm-sendBtn" onclick="MessagesModal.send()">
                                <i class="ti ti-send me-1"></i> 보내기
                            </button>
                        </div>
                    </div>
                </div>

                <form id="mm-searchForm" class="mb-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-auto">
                            <label class="form-label mb-1">읽음 여부</label>
                            <select class="form-select" name="read">
                                <option value="">전체</option>
                                <option value="Y">읽음</option>
                                <option value="N">안읽음</option>
                            </select>
                        </div>
                        <div class="col-12 col-md">
                            <label class="form-label mb-1">검색어</label>
                            <input type="text" class="form-control" name="searchText"
                                   placeholder="보낸사람 / 받는사람 이름 / 내용 검색"
                                   onkeydown="if(event.keyCode === 13){ event.preventDefault(); MessagesModal.search(); }">
                        </div>
                        <div class="col-12 col-md-auto d-flex gap-2">
                            <button type="button" class="btn btn-primary flex-fill px-3" onclick="MessagesModal.search()">
                                <i class="ti ti-search me-1"></i> 검색
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill px-3" onclick="MessagesModal.search()">
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
        var recipientsTemplate = @json($recipientsUrlTemplate);
        var sendTemplate = @json($sendUrlTemplate);
        var reverbCfg   = @json($reverbCfg);

        var table = null;
        var pusher = null;
        var currentChannel = null;
        var currentEventId = null;
        var currentSenderId = null;
        var currentConvA = null;
        var currentConvB = null;
        var modalEl = null;
        var bsModal = null;

        function listUrl(eventId){ return urlTemplate.replace('__ID__', eventId); }

        function genderBadge(gender){
            if(gender === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(gender === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }
        function nameWithGender(name, gender, label){
            var n = (label || name) ? $('<div>').text(label || (name + '호')).html() : '-';
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
            if(currentConvA && currentConvB){ f.convA = currentConvA; f.convB = currentConvB; }
            return f;
        }

        function matchesFilter(d){
            var f = filters();
            // 특정 발신자 전용 조회 중이면 그 사람이 보낸 것만 실시간 반영.
            if(currentSenderId && d.sender && Number(d.sender.id) !== Number(currentSenderId)){ return false; }
            // 두 회원 대화 전용 조회 중이면 그 둘 사이 메시지만 실시간 반영.
            if(currentConvA && currentConvB){
                var sid = d.sender ? Number(d.sender.id) : 0;
                var rid = d.receiver ? Number(d.receiver.id) : 0;
                var pair = (sid === Number(currentConvA) && rid === Number(currentConvB))
                        || (sid === Number(currentConvB) && rid === Number(currentConvA));
                if(!pair){ return false; }
            }
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
                layout: "fitColumns",       // 가로를 꽉 채운다
                height: "55vh",
                resizableColumns: false,    // 컬럼 리사이즈 핸들 제거(엑셀 느낌 제거)
                // 복사 기능: Ctrl+C 로 보이는 행 전체(모든 컬럼)를 복사한다. (Tabulator 공식 clipboard)
                clipboard: "copy",
                clipboardCopyRowRange: "visible",
                clipboardCopyConfig: {
                    columnHeaders: false,   // 헤더 제외
                    formatCells: false,     // formatter(HTML) 말고 원본 값으로 복사
                },
                initialSort: [{column: "id", dir: "desc"}],
                // 각 셀에 모바일 라벨(data-mlabel)을 달아둔다. (CSS 가 모바일에서만 ::before 로 표시)
                rowFormatter: function(row){
                    var labels = { id: 'No', sender_id: '보낸/받는', body: '내용', read_at: '읽음', created_at: '보낸 시각' };
                    row.getCells().forEach(function(cell){
                        var f = cell.getField();
                        if(f && labels[f]){ cell.getElement().setAttribute('data-mlabel', labels[f]); }
                    });
                },
                columns: [
                    { title: "No", field: "id", width: 70, hozAlign: "center",
                        accessorClipboard: function(value){ return value; } },
                    { title: "보낸사람 → 받는사람", field: "sender_id", width: 220, headerSort: false,
                        // 복사 시: "A호(남) → B호(여)" 텍스트
                        accessorClipboard: function(value, data){
                            var fromName = data.is_admin ? ('관리자' + (data.admin_name ? '(' + data.admin_name + ')' : '')) : ((data.sender && (data.sender.label || data.sender.name)) ? (data.sender.label || (data.sender.name + '호')) : '-');
                            var toName   = (data.receiver && (data.receiver.label || data.receiver.name)) ? (data.receiver.label || (data.receiver.name + '호')) : '-';
                            return fromName + ' → ' + toName;
                        },
                        formatter: function(cell){
                            var d = cell.getRow().getData();
                            var from = d.is_admin
                                ? '<span class="badge bg-purple-lt">관리자' + (d.admin_name ? ' (' + $('<i>').text(d.admin_name).html() + ')' : '') + '</span>'
                                : nameWithGender(d.sender ? d.sender.name : null, d.sender ? d.sender.gender : null, d.sender ? d.sender.label : null);
                            var to   = nameWithGender(d.receiver ? d.receiver.name : null, d.receiver ? d.receiver.gender : null, d.receiver ? d.receiver.label : null);
                            return `<div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span>${from}</span>
                                        <i class="ti ti-arrow-right text-muted"></i>
                                        <span>${to}</span>
                                    </div>`;
                        }
                    },
                    { title: "내용", field: "body",
                        accessorClipboard: function(value){ return value || ''; },
                        formatter: function(cell){
                            return '<div style="white-space:pre-wrap; word-break:break-word; line-height:1.5;">'
                                + $('<div>').text(cell.getValue() || '').html() + '</div>';
                        }
                    },
                    { title: "읽음", field: "read_at", width: 170, hozAlign: "center", headerSort: false,
                        // 복사 시: "읽음 (시각)" 또는 "안읽음"
                        accessorClipboard: function(value){
                            return value ? ('읽음 ' + new Date(value).toLocaleString('ko-KR')) : '안읽음';
                        },
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
                        // 복사 시: yyyy/mm/dd HH:mm (24시간)
                        accessorClipboard: function(value){
                            if(!value){ return '-'; }
                            var d = new Date(value);
                            var p = function(n){ return (n < 10 ? '0' : '') + n; };
                            return d.getFullYear() + '/' + p(d.getMonth()+1) + '/' + p(d.getDate())
                                + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
                        },
                        formatter: function(cell){
                            if(!cell.getValue()){ return '-'; }
                            var s = new Date(cell.getValue()).toLocaleString('ko-KR');
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

        var recipientChoices = null;

        function ensureRecipientChoices(){
            if(recipientChoices || typeof Choices === 'undefined'){ return recipientChoices; }
            recipientChoices = new Choices(document.getElementById('mm-sendMembers'), {
                removeItemButton: true,
                searchEnabled: true,
                searchFields: ['label'],
                searchFloor: 1,
                searchResultLimit: -1,
                shouldSort: false,
                itemSelectText: '',
                placeholderValue: '받을 참가자 선택 (여러 명 가능)',
                searchPlaceholderValue: '🔍 번호 / 닉네임 검색',
                noResultsText: '검색 결과가 없습니다.',
                noChoicesText: '선택할 참가자가 없습니다.',
                fuseOptions: { threshold: 0.3, ignoreLocation: true },
            });
            return recipientChoices;
        }

        function loadRecipients(eventId){
            var c = ensureRecipientChoices();
            if(!c){ return; }
            c.removeActiveItems();
            c.clearChoices();
            $.get(recipientsTemplate.replace('__ID__', eventId), function(res){
                var items = (res.data || []).map(function(m){
                    return { value: String(m.id), label: '[' + (m.gender === 'F' ? '여' : '남') + '] ' + m.label };
                });
                c.setChoices(items, 'value', 'label', true);
            });
        }

        $(document).on('change', '#mm-sendTarget', function(){
            $('#mm-sendMembersWrap').toggleClass('d-none', $(this).val() !== 'members');
        });

        return {
            send: function(){
                if(!currentEventId){ return; }
                var target = $('#mm-sendTarget').val();
                var body = ($('#mm-sendBody').val() || '').trim();
                var ids = recipientChoices ? recipientChoices.getValue(true) : [];
                if(!body){ alert('메시지 내용을 입력해주세요.'); return; }
                if(target === 'members' && ids.length < 1){ alert('받을 참가자를 선택해주세요.'); return; }
                if(!confirm('메시지를 보내시겠습니까?')){ return; }
                var $btn = $('#mm-sendBtn').prop('disabled', true);
                $.ajax({
                    url: sendTemplate.replace('__ID__', currentEventId),
                    type: 'POST',
                    data: { body: body, target: target, member_ids: ids },
                    // complete 는 전역 $.ajaxSetup(로딩 모달 닫기)을 덮어쓰므로 쓰지 않는다.
                    success: function(res){
                        $btn.prop('disabled', false);
                        $('#mm-sendBody').val('');
                        alert(res?.msg ?? '전송했습니다.');
                    },
                    error: function(xhr){
                        $btn.prop('disabled', false);
                        alert(xhr?.responseJSON?.error?.message ?? xhr?.responseJSON?.msg ?? '오류가 발생했습니다.');
                    }
                });
            },
            search: function(){
                if(!table || !currentEventId){ return; }
                table.setData(listUrl(currentEventId), filters());
            },
            open: function(eventId, eventLabel, options){
                if(!eventId){ return; }
                var opts = options || {};
                currentEventId = eventId;
                currentSenderId = opts.senderId || null;
                currentConvA = opts.convA || null;
                currentConvB = opts.convB || null;
                $('#mm-eventLabel').text(eventLabel || '');
                $('#mm-searchForm')[0].reset();
                $('#mm-sendTarget').val('all').trigger('change');
                $('#mm-sendBody').val('');
                loadRecipients(eventId);
                // 특정 발신자 전용 조회면 제목에 표시.
                $('#mm-modalTitleText').text((currentSenderId || (currentConvA && currentConvB)) ? '대화 메시지' : '메시지 조회');
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
