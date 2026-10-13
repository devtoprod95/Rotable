@extends('layouts.app')

@php
    /** @var \App\Models\EventRound $event */
@endphp

@section('title', '메시지 조회')

@section('content')
<div class="row row-cards mt-2">

    {{-- 상단: 행사 정보 + 뒤로가기 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="card-title mb-0">
                        메시지 조회
                        <span id="liveBadge" class="badge bg-secondary-lt ms-2" title="실시간 연결 상태">
                            <i class="ti ti-circle-filled me-1 text-muted" style="font-size:0.6em;"></i>실시간 대기
                        </span>
                    </h3>
                    <small class="text-muted">{{ $event->label }}</small>
                </div>
                <a href="{{ route('admin.rotation.event.detail', $event->id) }}" class="btn btn-outline-secondary px-4">
                    <i class="ti ti-arrow-left me-1"></i> 행사 상세
                </a>
            </div>
            <div class="card-body py-2">
                <form id="searchForm">
                    <div class="d-flex gap-2 align-items-end flex-wrap">
                        <div style="min-width: 140px;">
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
                                   onkeydown="if(event.keyCode === 13){ event.preventDefault(); $('.btn-submit').click(); }">
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary px-4 btn-submit">검색</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 메시지 목록 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <span class="page-count badge bg-secondary-lt"></span>
                <button type="button" class="btn btn-outline-secondary ms-auto btn-refresh">
                    <i class="ti ti-refresh me-1"></i> 새로고침
                </button>
            </div>
            <div id="message-table"></div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var table;

        function genderBadge(gender){
            if(gender === 'F'){ return '<span class="badge bg-pink-lt">여</span>'; }
            if(gender === 'M'){ return '<span class="badge bg-blue-lt">남</span>'; }
            return '';
        }

        function nameWithGender(name, gender){
            var n = name ? $('<div>').text(name).html() : '-';
            var g = genderBadge(gender);
            return `<span class="fw-bold">${n}</span> ${g}`;
        }

        table = new Tabulator("#message-table", {
            ajaxURL: "{{ route('admin.rotation.event.messages.list', $event->id) }}",
            layout: "fitColumns",
            initialSort: [{column: "id", dir: "desc"}],
            columns: [
                { title: "No", field: "id", width: 70, hozAlign: "center" },
                // 보낸사람 → 받는사람을 한 컬럼으로 합쳐 가로폭 최소화
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
                    formatter: function(cell){
                        var v = cell.getValue() || '';
                        return $('<div>').text(v).html();
                    }
                },
                // 읽음 상태 + 읽은 시각을 한 컬럼으로 합침
                { title: "읽음", field: "read_at", width: 200, hozAlign: "center", headerSort: false,
                    formatter: function(cell){
                        var v = cell.getValue();
                        if(v){
                            var t = new Date(v).toLocaleString('ko-KR');
                            return `<span class="badge bg-green-lt">읽음</span><div style="font-size:0.8em;color:#888;">${t}</div>`;
                        }
                        return '<span class="badge bg-red-lt">안읽음</span>';
                    }
                },
                // 보낸 시각
                { title: "보낸 시각", field: "created_at", width: 200, hozAlign: "center",
                    formatter: function(cell){
                        return cell.getValue() ? new Date(cell.getValue()).toLocaleString('ko-KR') : '-';
                    }
                },
            ],
            ajaxResponse: function(url, params, response){
                var total = response.total ?? (response.data ? response.data.length : 0);
                $('.page-count').text('총 ' + total + '건');
                return response;
            },
        });

        $('.btn-submit').click(function(){
            table.setData("{{ route('admin.rotation.event.messages.list', $event->id) }}", {
                searchText: $('#searchForm [name=searchText]').val(),
                read: $('#searchForm [name=read]').val(),
            });
        });
        $('#searchForm select').change(function(){ $('.btn-submit').click(); });
        $('.btn-refresh').click(function(){ $('.btn-submit').click(); });

        /* ================= 실시간 (Laravel Reverb / Pusher 프로토콜) ================= */
        var reverbCfg = @json($reverb);

        function setLive(state){
            var $b = $('#liveBadge');
            if(state === 'on'){ $b.removeClass('bg-secondary-lt bg-red-lt').addClass('bg-green-lt').html('<i class="ti ti-circle-filled me-1 text-green" style="font-size:0.6em;"></i>실시간 연결됨'); }
            else if(state === 'off'){ $b.removeClass('bg-secondary-lt bg-green-lt').addClass('bg-red-lt').html('<i class="ti ti-circle-filled me-1 text-red" style="font-size:0.6em;"></i>연결 끊김'); }
            else { $b.removeClass('bg-green-lt bg-red-lt').addClass('bg-secondary-lt').html('<i class="ti ti-circle-filled me-1 text-muted" style="font-size:0.6em;"></i>실시간 대기'); }
        }

        // 현재 검색 필터에 새 메시지가 부합하는지 판정 (불일치 시 테이블에 넣지 않음)
        function matchesFilter(d){
            var read = $('#searchForm [name=read]').val();
            if(read === 'Y' && !d.read_at){ return false; }
            if(read === 'N' && d.read_at){ return false; }
            var kw = ($('#searchForm [name=searchText]').val() || '').trim().toLowerCase();
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

        function handleNewMessage(d){
            if(!d || !matchesFilter(d)){ return; }
            // 중복 방지 (이미 테이블에 있으면 스킵)
            if(table.getRow(d.id)){ return; }
            table.addData([d], true).then(function(){
                var row = table.getRow(d.id);
                if(row){
                    var el = row.getElement();
                    el.style.transition = 'background-color 1.5s';
                    el.style.backgroundColor = '#fff3bf';
                    setTimeout(function(){ el.style.backgroundColor = ''; }, 1500);
                }
                var cur = parseInt(($('.page-count').text().match(/\d+/) || [0])[0], 10) || 0;
                $('.page-count').text('총 ' + (cur + 1) + '건');
            });
        }

        function initRealtime(){
            if(typeof Pusher === 'undefined'){ setLive('off'); return; }
            if(!reverbCfg.key || !reverbCfg.host){ setLive('off'); return; }

            try {
                // Reverb 는 Pusher 프로토콜과 호환되므로 pusher-js 로 직접 연결한다.
                var pusher = new Pusher(reverbCfg.key, {
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

                // 공개 채널 구독. Laravel 공개 채널명은 그대로, 이벤트명은 '.' prefix 없이 broadcastAs 값.
                var channel = pusher.subscribe('event-messages.{{ $event->id }}');
                channel.bind('message.sent', handleNewMessage);
            } catch(e){
                console.error('Reverb 연결 실패:', e);
                setLive('off');
            }
        }

        initRealtime();
    });
</script>
@endsection
