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
    /* 메시지 목록 — 표(라이브러리) 대신 카드 목록. 모바일·PC 같은 구조, PC 는 2열.
       카드: ① 보낸사람 → 받는사람  ② 말풍선(내용)  ③ 보낸 시각 · 읽음 상태 */
    #messagesModal .mm-list{ display:grid; grid-template-columns:1fr; gap:.6rem; }
    @media (min-width: 992px){ #messagesModal .mm-list{ grid-template-columns:1fr 1fr; } }
    #messagesModal .mm-card{ border:1px solid #e9ecf1; border-radius:14px; background:#fff; padding:.75rem .85rem; box-shadow:0 1px 3px rgba(20,30,60,.05); }
    #messagesModal .mm-card.is-new{ animation: mmFlash 1.6s ease-out; }
    @keyframes mmFlash{ from{ background:#fff3bf; } to{ background:#fff; } }
    #messagesModal .mm-head{ display:flex; align-items:center; flex-wrap:wrap; gap:.35rem; font-size:.92rem; }
    #messagesModal .mm-head .mm-no{ margin-left:auto; color:#adb5bd; font-size:.75rem; }
    #messagesModal .mm-bubble{ margin-top:.5rem; padding:.6rem .75rem; border-radius:12px; background:#f4f7ff; color:#212529;
        white-space:pre-wrap; word-break:break-word; line-height:1.6; font-size:.95rem; }
    /* 말풍선 색: 보낸 사람 기준 — 남→여 파랑 / 여→남 분홍 / 관리자 보라 (왼쪽 띠로도 구분) */
    #messagesModal .mm-card.from-m .mm-bubble{ background:#e7f1ff; border-left:4px solid #4a8fe7; }
    #messagesModal .mm-card.from-f .mm-bubble{ background:#ffeef4; border-left:4px solid #e8709a; }
    #messagesModal .mm-card.is-admin .mm-bubble{ background:#f3eefc; border-left:4px solid #8e6bd8; }
    #messagesModal .mm-foot{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.35rem; margin-top:.5rem; font-size:.8rem; color:#868e96; }
    #messagesModal .mm-empty{ text-align:center; color:#868e96; padding:2.5rem 0; }
    /* 모바일: 보내기 영역이 길어 목록을 밀어내지 않도록 접을 수 있게 */
    #messagesModal .mm-send-head{ cursor:pointer; user-select:none; }
</style>

<div class="modal fade" id="messagesModal" tabindex="-1" aria-hidden="true">
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
                    <div class="fw-bold mm-send-head d-flex align-items-center gap-1" role="button" tabindex="0"
                         data-bs-toggle="collapse" data-bs-target="#mm-sendCollapse" aria-expanded="false" aria-controls="mm-sendCollapse">
                        <i class="ti ti-send me-1"></i>관리자 메시지 보내기 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i>
                    </div>
                    <div class="text-muted small">참가자에게는 익명으로 표시돼요.</div>
                    <div class="collapse" id="mm-sendCollapse">
                    <div class="row g-2 mt-1">
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

                <div class="d-flex align-items-center gap-2 mb-2">
                    <span id="mm-pageCount" class="badge bg-secondary-lt"></span>
                    <span class="text-muted small">최신순</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="mm-copyBtn" onclick="MessagesModal.copy()"
                            title="지금 검색 조건에 맞는 메시지 전체(더 보기로 아직 안 불러온 것 포함)를 텍스트로 복사해요">
                        <i class="ti ti-copy me-1"></i>메시지 전체 복사<span id="mm-copyCount"></span>
                    </button>
                </div>

                <div id="mm-list" class="mm-list" aria-live="polite"></div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-secondary d-none" id="mm-moreBtn" onclick="MessagesModal.more()">
                        <i class="ti ti-chevron-down me-1"></i>더 보기
                    </button>
                </div>
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

        // ===== 목록 (표 라이브러리 없이 카드로 직접 렌더) =====
        var PAGE_SIZE = 50;
        var items = [];          // 지금 화면에 보이는 메시지 (최신순)
        var total = 0;
        var page = 0;
        var lastPage = 0;
        var loadingList = false;

        function esc(v){ return $('<div>').text(v == null ? '' : v).html(); }
        function fmt(v){
            if(!v){ return ''; }
            var d = new Date(v);
            if(isNaN(d.getTime())){ return ''; }
            var p = function(n){ return (n < 10 ? '0' : '') + n; };
            return (d.getMonth() + 1) + '/' + d.getDate() + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
        }
        function fromHtml(d){
            return d.is_admin
                ? '<span class="badge bg-purple-lt">관리자' + (d.admin_name ? ' (' + esc(d.admin_name) + ')' : '') + '</span>'
                : nameWithGender(d.sender ? d.sender.name : null, d.sender ? d.sender.gender : null, d.sender ? d.sender.label : null);
        }
        function card(d, isNew){
            var to = nameWithGender(d.receiver ? d.receiver.name : null, d.receiver ? d.receiver.gender : null, d.receiver ? d.receiver.label : null);
            var read = d.read_at
                ? '<span class="badge bg-green-lt"><i class="ti ti-checks me-1"></i>읽음 ' + esc(fmt(d.read_at)) + '</span>'
                : '<span class="badge bg-red-lt"><i class="ti ti-mail me-1"></i>안읽음</span>';
            var tone = d.is_admin ? ' is-admin'
                : (d.sender && d.sender.gender === 'F' ? ' from-f' : (d.sender && d.sender.gender === 'M' ? ' from-m' : ''));
            return '<div class="mm-card' + tone + (isNew ? ' is-new' : '') + '" data-id="' + d.id + '">'
                 +   '<div class="mm-head">' + fromHtml(d) + '<i class="ti ti-arrow-right text-muted"></i>' + to
                 +     '<span class="mm-no">#' + d.id + '</span></div>'
                 +   '<div class="mm-bubble">' + esc(d.body || '') + '</div>'
                 +   '<div class="mm-foot"><span><i class="ti ti-clock me-1"></i>' + esc(fmt(d.created_at)) + '</span>' + read + '</div>'
                 + '</div>';
        }
        function renderList(){
            var $l = $('#mm-list');
            if(!items.length){
                $l.html('<div class="mm-empty" style="grid-column:1 / -1;">메시지가 없어요.</div>');
            } else {
                $l.html(items.map(function(d){ return card(d, false); }).join(''));
            }
            $('#mm-pageCount').text('총 ' + total + '건');
            $('#mm-copyCount').text(total > 0 ? ' (' + total + '건)' : '');
            $('#mm-moreBtn').toggleClass('d-none', page >= lastPage);
        }
        // reset=true 면 1페이지부터 다시, false 면 다음 페이지를 이어 붙인다.
        function loadList(reset){
            if(!currentEventId || loadingList){ return; }
            loadingList = true;
            var nextPage = reset ? 1 : page + 1;
            if(reset){ $('#mm-list').html('<div class="mm-empty" style="grid-column:1 / -1;">불러오는 중…</div>'); }
            var params = $.extend({ page: nextPage, size: PAGE_SIZE }, filters());
            // 목록 조회는 화면 안에 "불러오는 중"을 보여주므로 전역 로딩 팝업(ajaxSetup beforeSend/complete)은 끈다.
            $.ajax({
                url: listUrl(currentEventId), type: 'GET', data: params,
                beforeSend: $.noop, complete: $.noop,
            }).done(function(res){
                var rows = (res && res.data) ? res.data : [];
                items = reset ? rows : items.concat(rows.filter(function(r){ return !items.some(function(x){ return x.id === r.id; }); }));
                total = res?.total ?? items.length;
                page = res?.current_page ?? nextPage;
                lastPage = res?.last_page ?? page;
                renderList();
            }).fail(function(){
                if(reset){ $('#mm-list').html('<div class="mm-empty text-danger" style="grid-column:1 / -1;">불러오지 못했어요.</div>'); }
            }).always(function(){ loadingList = false; });
        }

        function handleNewMessage(d){
            if(!currentEventId || !d || !matchesFilter(d)){ return; }
            if(items.some(function(x){ return x.id === d.id; })){ return; }
            items.unshift(d);
            total += 1;
            var $l = $('#mm-list');
            if($l.find('.mm-card').length === 0){ $l.empty(); }
            $l.prepend(card(d, true));
            $('#mm-pageCount').text('총 ' + total + '건');
            $('#mm-copyCount').text(' (' + total + '건)');
        }

        // 메시지 전체 복사: 지금 검색 조건에 맞는 메시지를 "모두" 받아(더 보기로 안 불러온 것 포함) 텍스트로 복사한다.
        //  형식(한 줄 = 메시지 1건, 탭 구분): #번호  보낸사람 → 받는사람  내용  읽음 여부  보낸 시각   (최신순)
        var COPY_MAX = 2000;
        function lineOf(d){
            var from = d.is_admin ? ('관리자' + (d.admin_name ? '(' + d.admin_name + ')' : ''))
                : ((d.sender && (d.sender.label || d.sender.name)) ? (d.sender.label || (d.sender.name + '호')) : '-');
            var to = (d.receiver && (d.receiver.label || d.receiver.name)) ? (d.receiver.label || (d.receiver.name + '호')) : '-';
            return '#' + d.id + '\t' + from + ' → ' + to + '\t' + (d.body || '').replace(/\s+/g, ' ')
                + '\t' + (d.read_at ? '읽음 ' + fmt(d.read_at) : '안읽음') + '\t' + fmt(d.created_at);
        }
        function writeClipboard(text, count){
            var done = function(){ alert('메시지 ' + count + '건의 내용을 복사했어요.'); };
            if(navigator.clipboard && window.isSecureContext){
                navigator.clipboard.writeText(text).then(done, function(){ fallbackCopy(text); done(); });
            } else { fallbackCopy(text); done(); }
        }
        function copyVisible(){
            if(!currentEventId){ return; }
            if(!total){ alert('복사할 메시지가 없어요.'); return; }
            // 이미 전부 불러왔으면 바로 복사
            if(items.length >= total){
                writeClipboard(items.map(lineOf).join('\n'), items.length);
                return;
            }
            var $btn = $('#mm-copyBtn').prop('disabled', true);
            $.ajax({
                url: listUrl(currentEventId), type: 'GET',
                data: $.extend({ page: 1, size: Math.min(total, COPY_MAX) }, filters()),
                beforeSend: $.noop, complete: $.noop,
            }).done(function(res){
                var rows = (res && res.data) ? res.data : [];
                if(!rows.length){ alert('복사할 메시지가 없어요.'); return; }
                writeClipboard(rows.map(lineOf).join('\n'), rows.length);
                if((res?.total ?? rows.length) > rows.length){
                    alert('한 번에 ' + COPY_MAX + '건까지만 복사돼요. 검색 조건을 좁혀 나눠서 복사해 주세요.');
                }
            }).fail(function(){
                alert('메시지를 불러오지 못해 복사하지 못했어요.');
            }).always(function(){ $btn.prop('disabled', false); });
        }
        function fallbackCopy(text){
            var ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.getElementById('messagesModal').appendChild(ta);
            ta.select(); try { document.execCommand('copy'); } catch(e){}
            ta.remove();
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
                        loadList(true);
                    },
                    error: function(xhr){
                        $btn.prop('disabled', false);
                        alert(xhr?.responseJSON?.error?.message ?? xhr?.responseJSON?.msg ?? '오류가 발생했습니다.');
                    }
                });
            },
            search: function(){ loadList(true); },
            more: function(){ loadList(false); },
            copy: function(){ copyVisible(); },
            open: function(eventId, eventLabel, options){
                if(!eventId){ return; }
                var opts = options || {};
                currentEventId = eventId;
                currentSenderId = opts.senderId || null;
                currentConvA = opts.convA || null;
                currentConvB = opts.convB || null;
                $('#mm-eventLabel').text(eventLabel || '');
                items = []; total = 0; page = 0; lastPage = 0;
                $('#mm-list').empty(); $('#mm-pageCount').text(''); $('#mm-copyCount').text(''); $('#mm-moreBtn').addClass('d-none');
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
                        loadList(true);
                        subscribe(currentEventId);
                    };
                    modalEl.addEventListener('shown.bs.modal', onShown);
                    modalEl.addEventListener('shown.coreui.modal', onShown);
                }

                bsModal.show();

                // 이미 열려 있으면 shown 이 재발생하지 않으므로 보이는 경우 즉시 갱신.
                var elNow = document.getElementById('mm-list');
                if(elNow && elNow.offsetParent !== null){
                    loadList(true);
                    subscribe(currentEventId);
                }
            },
        };
    })();
</script>
