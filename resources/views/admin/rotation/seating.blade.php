@extends('layouts.app')

@section('title', '로테이션 설정')

@section('content')
<style>
    /* Choices 드롭다운이 턴 네비게이션 등 다른 요소 위로 오도록 z-index 최상단 처리.
       열린 셀렉트의 카드가 다른 카드/버튼보다 위에 쌓이도록 함께 올린다. */
    .choices { position: relative; }
    .choices.is-open { z-index: 1080; }
    .choices__list--dropdown,
    .choices__list[aria-expanded] {
        z-index: 1080 !important;
    }
    /* 열린 셀렉트를 품은 카드가 형제 카드 위로 오도록 */
    .card:has(.choices.is-open) { position: relative; z-index: 1080; }

    /* 칩 제거(x) 버튼: 클릭 영역을 넓히고 hover 시 배경 피드백 */
    .seat-chips .chip-remove { transition: background-color .12s ease; cursor: pointer; }
    .seat-chips .chip-remove:hover { background-color: rgba(214, 57, 57, 0.15); }
</style>
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <h3 class="card-title mb-0">로테이션 설정</h3>
                    <span class="badge d-none" id="eventStatusBadge"></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="goDetailBtn" class="btn btn-outline-secondary">
                        <i class="ti ti-external-link me-1"></i> 행사 상세
                    </button>
                    <button type="button" id="goMatchesBtn" class="btn btn-outline-pink">
                        <i class="ti ti-heart me-1"></i> 전체 매칭
                    </button>
                    <button type="button" id="goMatchedBtn" class="btn btn-outline-pink">
                        <i class="ti ti-heart-filled me-1"></i> 매칭 결과<span id="matchedCount"></span>
                    </button>
                    <button type="button" id="goMessagesBtn" class="btn btn-outline-primary">
                        <i class="ti ti-messages me-1"></i> 전체 메시지<span id="messagesCount"></span>
                    </button>
                    <button type="button" id="goRequestsBtn" class="btn btn-outline-yellow">
                        <i class="ti ti-hand-stop me-1"></i> 전체 요청<span id="requestsCount"></span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-start">
                    <div class="col-md-5">
                        <label class="form-label">행사</label>
                        <select class="form-select" id="eventSelect">
                            @foreach ($events as $event)
                                <option value="{{ $event->id }}" {{ $eventId == $event->id ? 'selected' : '' }}>{{ $event->label }}</option>
                            @endforeach
                        </select>
                        <small class="form-hint text-muted">배정할 행사를 선택하세요.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">턴</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" id="roundPrev" title="이전 턴">
                                <i class="ti ti-chevron-left"></i>
                            </button>
                            <input type="number" min="0" class="form-control text-center" id="roundInput" value="{{ $roundNo }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="roundNext" title="다음 턴">
                                <i class="ti ti-chevron-right"></i>
                            </button>
                        </div>
                        <small class="form-hint text-muted" id="roundHint">턴을 이동하며 배치를 볼 수 있습니다.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block" id="actionLabel">&nbsp;</label>
                        <div class="d-flex align-items-center" style="min-height: 38px;">
                            <button type="button" class="btn btn-primary w-100 d-none" id="rotateBtn">
                                <i class="ti ti-rotate-clockwise me-1"></i>로테이션 진행
                            </button>
                            <button type="button" class="btn btn-success w-100 d-none" id="finishBtn">
                                <i class="ti ti-checkbox me-1"></i>매칭 시작
                            </button>
                            <button type="button" class="btn btn-danger w-100 d-none" id="closeVotingBtn">
                                <i class="ti ti-flag-check me-1"></i>매칭 종료
                            </button>
                            <span class="btn btn-outline-secondary w-100 d-none disabled" id="closedBtn">
                                <i class="ti ti-lock me-1"></i>종료된 행사
                            </span>
                            <div class="d-none align-items-center justify-content-center text-danger fw-bold text-center w-100" id="pastNotice">
                                최신 턴에서만 로테이션을 진행할 수 있습니다.
                            </div>
                        </div>
                        <small class="form-hint text-muted mt-2 d-block" id="actionHint"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div id="boardArea" class="row row-cards"></div>
    </div>
</div>

<script>
    $(document).ready(function(){
        const dataUrl    = "{{ route('admin.rotation.seating.data') }}";
        const storeUrl   = "{{ route('admin.rotation.seating.store') }}";
        const deleteUrl  = "{{ route('admin.rotation.seating.delete') }}";
        const rotateUrl  = "{{ route('admin.rotation.seating.rotate') }}";
        const finishUrl  = "{{ route('admin.rotation.seating.finish') }}";
        const closeVotingUrl = "{{ route('admin.rotation.seating.closeVoting') }}";
        const matchesUrlTemplate = "{{ route('admin.rotation.event.matches.list', ['id' => '__ID__']) }}";
        const messagesUrlTemplate = "{{ route('admin.rotation.event.messages.list', ['id' => '__ID__']) }}";
        const requestsListUrl = "{{ route('admin.requests.list') }}";
        const eventDetailBase = "{{ url('admin/rotation/event') }}";

        // 서버 enum 기반 상태 라벨 (P/S/V/X). 뱃지 표기에 사용.
        const STATUS_LABEL = @json($statuses);
        // 상태별 Tabler 뱃지 색상 클래스. (모두 light 변형: 밝은 배경 + 진한 글씨로 가독성 확보)
        const STATUS_BADGE = { P: 'bg-yellow-lt', S: 'bg-green-lt', V: 'bg-pink-lt', X: 'bg-secondary-lt' };

        // 현재 행사 상태 뱃지를 최신 상태로 갱신한다.
        function renderStatusBadge(status){
            let $badge = $('#eventStatusBadge');
            if(!status || !STATUS_LABEL[status]){
                $badge.addClass('d-none').text('');
                return;
            }
            let cls = STATUS_BADGE[status] || 'bg-secondary';
            $badge.removeClass('d-none bg-yellow-lt bg-green-lt bg-pink-lt bg-secondary-lt')
                  .addClass(cls)
                  .text('현재 상태: ' + STATUS_LABEL[status]);
        }

        let members = [];
        let dataXhr = null;      // 진행 중인 요청 추적 (중복 호출 방지)
        let viewRound = null;    // 현재 보고 있는 턴 (null이면 최신 턴으로 자동 설정)
        let currentRound = 0;    // 좌석이 존재하는 최신 턴 (메타 기준)

        // 성별 색상 구분: 남성=파랑(blue), 여성=핑크(pink)
        function genderBadgeClass(g){ return g === 'F' ? 'bg-pink-lt' : 'bg-blue-lt'; }
        function genderLabel(g){ return g === 'F' ? '여' : '남'; }

        // assignedIds: 현재 턴에 이미 배정된 회원 id Set (중복 배정 방지용)
        function memberOptions(assignedIds){
            let html = '<option value="">+ 회원 추가</option>';
            members.forEach(function(m){
                if(assignedIds && assignedIds.has(m.id)) return; // 이미 배정된 회원은 제외
                // 동명이인 구분을 위해 직업/출생연도를 함께 표기한다.
                let info = [];
                if(m.birth_year){ info.push(String(m.birth_year).slice(-2) + '년생'); }
                if(m.job){ info.push(m.job); }
                let suffix = info.length ? ` · ${info.join(' · ')}` : '';
                html += `<option value="${m.id}">[${genderLabel(m.gender)}] ${m.name}${suffix}</option>`;
            });
            return html;
        }

        function loadData(){
            // 이전 요청이 진행 중이면 취소하여 중복 렌더 방지
            if(dataXhr){ dataXhr.abort(); }
            // viewRound가 아직 없으면(최초) -1을 보내 서버가 최신 턴을 선택하게 한다.
            let reqRound = (viewRound === null) ? -1 : viewRound;
            dataXhr = $.get(dataUrl, { eventId: $('#eventSelect').val(), roundNo: reqRound }, function(res){
                members = res?.members || [];
                renderActions(res?.board?.meta || {});
                renderBoard(res?.board || {});
            }).always(function(){ dataXhr = null; });
            refreshCounts();
            return dataXhr;
        }

        // 상단 버튼들에 현재 행사의 매칭 쌍 수 / 메시지 수 / 요청 수를 표시한다.
        function refreshCounts(){
            var eventId = $('#eventSelect').val();
            var $matched = $('#matchedCount');
            var $messages = $('#messagesCount');
            var $requests = $('#requestsCount');

            if(!eventId){ $matched.text(''); $messages.text(''); $requests.text(''); return; }

            function setCount($el, cnt, unit){
                $el.text((cnt > 0) ? ' (' + cnt + unit + ')' : '');
            }

            // 매칭 쌍 수 (남자 기준 중복 제거된 쌍)
            $.get(matchesUrlTemplate.replace('__ID__', eventId) + '?matched=1', function(res){
                setCount($matched, res?.total ?? (res?.data ? res.data.length : 0), '쌍');
            }).fail(function(){ $matched.text(''); });

            // 전체 메시지 수
            $.get(messagesUrlTemplate.replace('__ID__', eventId), { size: 1 }, function(res){
                setCount($messages, res?.total ?? 0, '건');
            }).fail(function(){ $messages.text(''); });

            // 전체 요청 수
            $.get(requestsListUrl, { eventId: eventId, size: 1 }, function(res){
                setCount($requests, res?.total ?? 0, '건');
            }).fail(function(){ $requests.text(''); });
        }

        // 메타 기반 버튼/네비게이션 제어:
        //  - 턴 네비게이션(◀▶)으로 과거 턴 조회 가능
        //  - [로테이션 진행]/[종료]는 "최신 턴을 보고 있을 때만" 노출
        //  - 과거 턴을 볼 때는 진행/종료 버튼 숨김
        function renderActions(meta){
            let $rotate = $('#rotateBtn').addClass('d-none');
            let $finish = $('#finishBtn').addClass('d-none');
            let $closeVoting = $('#closeVotingBtn').addClass('d-none');
            let $closed = $('#closedBtn').addClass('d-none');
            let $hint   = $('#actionHint');
            let $roundHint = $('#roundHint');
            let $pastNotice = $('#pastNotice').removeClass('d-flex').addClass('d-none');
            let $actionLabel = $('#actionLabel').removeClass('d-none');

            currentRound = (typeof meta.current_round !== 'undefined') ? meta.current_round : 0;

            // 현재 행사 상태 뱃지 최신화 (버튼 클릭 → loadData 재호출 시마다 갱신됨)
            renderStatusBadge(meta.event_status);

            // 조회 가능한 최소 턴: 1턴까지만 (0턴은 세팅용이라 조회하지 않음).
            // 단, 아직 0턴만 있는 세팅 단계(current_round=0)에서는 0턴을 본다.
            let minRound = Math.min(1, currentRound);

            // 최초 로딩이면 보는 턴을 최신 턴으로 맞춘다.
            if(viewRound === null){ viewRound = currentRound; }
            // 보는 턴이 [minRound, currentRound] 범위를 벗어나지 않도록 보정
            if(viewRound > currentRound){ viewRound = currentRound; }
            if(viewRound < minRound){ viewRound = minRound; }

            $('#roundInput').val(viewRound);

            // 네비게이션 버튼 활성/비활성 (1턴 아래로는 못 내려감)
            $('#roundPrev').prop('disabled', viewRound <= minRound);
            $('#roundNext').prop('disabled', viewRound >= currentRound);

            let isLatest = (viewRound === currentRound);
            $roundHint.text(isLatest ? '최신 턴입니다.' : '이전 턴을 보는 중입니다. (인원 추가/삭제 가능)');

            // 생성된 테이블이 없으면 액션 버튼을 노출하지 않는다.
            if(!meta.table_count){
                $hint.text('');
                return;
            }

            // 투표중/종료는 로테이션이 끝난 뒤의 "행사 전체 상태"이므로,
            // 어느 턴을 보고 있든 상태 기준으로 먼저 처리한다.
            if(meta.is_closed){
                $closed.removeClass('d-none');
                $hint.text('이미 종료된 행사입니다. (매칭 마감)');
                return;
            }
            if(meta.is_voting){
                $closeVoting.removeClass('d-none');
                $hint.html('매칭이 진행 중입니다. 회원들이 상대를 선택하고 있어요.<br>(행사 다음 날 오전 9시 자동 종료)');
                return;
            }

            // 과거 턴을 보고 있으면 진행/종료 버튼 대신 안내 문구를 표시한다. (label/힌트 라인은 왼쪽과 정렬 유지)
            if(!isLatest){
                $pastNotice.removeClass('d-none').addClass('d-flex');
                $hint.text('이전 턴은 로테이션을 진행할 수 없습니다.');
                return;
            }

            if(meta.is_last){
                $finish.removeClass('d-none');
                $hint.text('마지막 턴입니다. 매칭을 시작할 수 있습니다.');
            } else {
                $rotate.removeClass('d-none');
                // 0턴(아직 로테이션 시작 전)이면 '시작', 그 이후면 '진행'.
                if(viewRound === 0){
                    $rotate.html('<i class="ti ti-player-play me-1"></i>로테이션 시작');
                    $hint.text('로테이션을 시작합니다.');
                } else {
                    $rotate.html('<i class="ti ti-rotate-clockwise me-1"></i>로테이션 진행');
                    $hint.text('다음 턴을 자동 생성합니다. (여자 고정 / 남자 이동)');
                }
            }
        }

        function renderBoard(data){
            // 이전 Choices 인스턴스를 먼저 정리(원본 select 복원)한 뒤 보드를 비운다.
            destroySeatChoices();
            let $area = $('#boardArea').empty();
            if(!data.tables || data.tables.length === 0){
                $area.html('<div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">생성된 테이블이 없습니다. 행사 관리 &gt; 상세에서 테이블을 먼저 추가해주세요.</div></div></div>');
                return;
            }

            // 과거 턴에서도 인원 추가/삭제는 항상 가능하게 둔다. (로테이션 진행 버튼만 최신 턴 제한)
            // 현재 턴에 이미 배정된 회원 id 수집 (다른 테이블 셀렉트에서 제외)
            let assignedIds = new Set();
            data.tables.forEach(function(t){
                t.members.forEach(function(m){ assignedIds.add(m.member_id); });
            });

            data.tables.forEach(function(t){
                let chips = t.members.map(function(m){
                    // 이름 옆에 출생연도/직업을 작은 글씨로 표기한다.
                    let info = [];
                    if(m.birth_year){ info.push(String(m.birth_year).slice(-2) + '년생'); }
                    if(m.job){ info.push(m.job); }
                    let infoText = info.length
                        ? ` <span style="font-weight:400;opacity:0.7;font-size:0.85em;">${info.join(' · ')}</span>`
                        : '';
                    return `<span class="badge ${genderBadgeClass(m.gender)} me-1 mb-1 d-inline-flex align-items-center gap-1 py-1 ps-2 pe-1">
                                <span>${m.name}${infoText}</span>
                                <a href="#" class="chip-remove d-inline-flex align-items-center justify-content-center text-danger"
                                   data-seating-id="${m.seating_id}"
                                   title="제거"
                                   style="width:1.4rem;height:1.4rem;border-radius:50%;text-decoration:none;"><i class="ti ti-x" style="font-size:0.95rem;line-height:1;"></i></a>
                            </span>`;
                }).join('');

                let card = `
                    <div class="col-md-4 col-sm-6">
                        <div class="card">
                            <div class="card-header py-2"><strong>${t.table_no}번 테이블</strong>
                                <span class="text-muted ms-1">(${t.members.length}명)</span></div>
                            <div class="card-body py-2">
                                <div class="mb-2 seat-chips">${chips || '<span class="text-muted">비어있음</span>'}</div>
                                <select class="form-select form-select-sm seat-add" data-table-id="${t.id}">${memberOptions(assignedIds)}</select>
                            </div>
                        </div>
                    </div>`;
                $area.append(card);
            });

            // 각 테이블의 '회원 추가' 셀렉트를 Choices(검색형)로 변환.
            // 보드는 매 렌더마다 재생성되므로 여기서 매번 초기화한다.
            initSeatChoices();
        }

        // 회원 추가 셀렉트(.seat-add)에 Choices 적용. (단일 선택 + 검색)
        // 보드가 매 렌더마다 재생성되므로, 이전 인스턴스를 먼저 destroy 하여
        // option 이 중복 누적되는 것을 방지한다.
        let seatChoices = [];
        function destroySeatChoices(){
            seatChoices.forEach(function(c){
                try { c.destroy(); } catch (e) { /* noop */ }
            });
            seatChoices = [];
        }
        function initSeatChoices(){
            destroySeatChoices();
            document.querySelectorAll('#boardArea .seat-add').forEach(function(el){
                seatChoices.push(new Choices(el, {
                    searchEnabled: true,
                    searchFields: ['label'],
                    searchFloor: 1,
                    searchResultLimit: -1,
                    shouldSort: false,
                    itemSelectText: '',
                    searchPlaceholderValue: '🔍 이름으로 검색',
                    noResultsText: '검색 결과가 없습니다.',
                    noChoicesText: '추가할 회원이 없습니다.',
                    fuseOptions: { threshold: 0.3, ignoreLocation: true },
                }));
            });
        }

        // 회원 추가 (넣을 때마다 즉시 저장)
        $('#boardArea').on('change', '.seat-add', function(){
            let memberId = $(this).val();
            let tableId  = $(this).data('table-id');
            if(!memberId) return;

            $.ajax({
                url: storeUrl,
                type: 'POST',
                data: {
                    event_id: $('#eventSelect').val(),
                    round_no: (viewRound === null ? $('#roundInput').val() : viewRound),
                    rotation_table_id: tableId,
                    member_id: memberId
                },
                success: function(res){
                    if(res?.status === 200){ loadData(); }
                    else { alert(res?.msg); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 회원 제거 (즉시 삭제)
        $('#boardArea').on('click', '.chip-remove', function(e){
            e.preventDefault();
            let seatingId = $(this).data('seating-id');
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: { id: seatingId },
                success: function(res){
                    if(res?.status === 200){ loadData(); }
                    else { alert(res?.msg); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 로테이션 진행: 현재 턴 기준 다음 턴 자동 생성 (여자 고정 / 남자 이동)
        $('#rotateBtn').on('click', async function(){
            let msg = (viewRound === 0)
                ? '로테이션을 시작하시겠습니까?'
                : '다음 턴으로 로테이션을 진행하시겠습니까?';
            if(!(await salert({text: msg}))) return;

            $.ajax({
                url: rotateUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    // 진행 후 최신 턴을 보도록 viewRound를 리셋하고, 보드 갱신이 끝난 뒤 알림.
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 투표 시작: 행사 상태를 투표중(V)으로
        $('#finishBtn').on('click', async function(){
            if(!(await salert({html: '매칭을 시작하시겠습니까?<br>회원들이 서로에게 투표(선택)할 수 있게 됩니다.'}))) return;

            $.ajax({
                url: finishUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 투표 마감(수동): 행사 상태를 종료(X)로
        $('#closeVotingBtn').on('click', async function(){
            if(!(await salert({html: '그대로 두면 <b>행사 다음 날 오전 9시</b>에 자동으로 마감돼요.<br>지금 바로 매칭을 종료하시겠습니까?<br>종료 후에는 회원이 선택을 수정할 수 없습니다.'}))) return;

            $.ajax({
                url: closeVotingUrl,
                type: 'POST',
                data: { event_id: $('#eventSelect').val() },
                success: function(res){
                    viewRound = null;
                    $.when(loadData()).always(function(){ alert(res?.msg); });
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        });

        // 선택된 행사 id를 URL 쿼리파라미터에 반영 (공유 가능 URL)
        function syncUrl(){
            let eventId = $('#eventSelect').val();
            let url = new URL(window.location.href);
            if(eventId){ url.searchParams.set('eventId', eventId); }
            else { url.searchParams.delete('eventId'); }
            window.history.replaceState({}, '', url);
        }

        // 선택된 행사의 메시지를 모달로 조회
        $('#goMessagesBtn').on('click', function(){
            var eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            var label = $('#eventSelect option:selected').text();
            MessagesModal.open(eventId, label);
        });

        // 선택된 행사의 상세 페이지를 새 창으로 연다.
        $('#goDetailBtn').on('click', function(){
            var eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            window.open(eventDetailBase + '/' + eventId, '_blank');
        });

        // 선택된 행사의 매칭 결과를 모달로 조회
        $('#goMatchesBtn').on('click', function(){
            var eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            var label = $('#eventSelect option:selected').text();
            MatchesModal.open(eventId, label);
        });

        // 선택된 행사의 "매칭된 쌍"만 모달로 조회 (남자 기준)
        $('#goMatchedBtn').on('click', function(){
            var eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            var label = $('#eventSelect option:selected').text();
            MatchedModal.open(eventId, label);
        });

        // 선택된 행사의 회원 요청을 모달로 조회
        $('#goRequestsBtn').on('click', function(){
            var eventId = $('#eventSelect').val();
            if(!eventId){ return; }
            var label = $('#eventSelect option:selected').text();
            RequestsModal.open(eventId, label);
        });

        // 턴 네비게이션: 이전/다음 턴 보기 (1턴 ~ 최신 턴 범위, 0턴은 세팅용이라 제외)
        $('#roundPrev').on('click', function(){
            if(viewRound === null){ viewRound = currentRound; }
            let minRound = Math.min(1, currentRound);
            if(viewRound > minRound){ viewRound -= 1; loadData(); }
        });
        $('#roundNext').on('click', function(){
            if(viewRound === null){ viewRound = currentRound; }
            if(viewRound < currentRound){ viewRound += 1; loadData(); }
        });

        $('#eventSelect').change(function(){ viewRound = null; syncUrl(); loadData(); });

        // 최초 로딩: URL에 eventId가 있으면 그 행사를 선택
        (function(){
            let paramEventId = new URL(window.location.href).searchParams.get('eventId');
            if(paramEventId && $('#eventSelect option[value="' + paramEventId + '"]').length){
                $('#eventSelect').val(paramEventId);
            }
            syncUrl();
            loadData();
        })();
    });
</script>

@include('admin.rotation._messagesModal')
@include('admin.rotation._matchesModal')
@include('admin.rotation._matchedModal')
@include('admin.rotation._requestsModal')
@endsection
