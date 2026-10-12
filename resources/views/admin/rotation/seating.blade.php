@extends('layouts.app')

@section('title', '로테이션 좌석 배정')

@section('content')
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">좌석 배정</h3></div>
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
                            <button type="button" class="btn btn-danger w-100 d-none" id="finishBtn">
                                <i class="ti ti-flag-check me-1"></i>로테이션 종료
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
                html += `<option value="${m.id}">[${genderLabel(m.gender)}] ${m.name}</option>`;
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
            return dataXhr;
        }

        // 메타 기반 버튼/네비게이션 제어:
        //  - 턴 네비게이션(◀▶)으로 과거 턴 조회 가능
        //  - [로테이션 진행]/[종료]는 "최신 턴을 보고 있을 때만" 노출
        //  - 과거 턴을 볼 때는 진행/종료 버튼 숨김
        function renderActions(meta){
            let $rotate = $('#rotateBtn').addClass('d-none');
            let $finish = $('#finishBtn').addClass('d-none');
            let $closed = $('#closedBtn').addClass('d-none');
            let $hint   = $('#actionHint');
            let $roundHint = $('#roundHint');
            let $pastNotice = $('#pastNotice').removeClass('d-flex').addClass('d-none');
            let $actionLabel = $('#actionLabel').removeClass('d-none');

            currentRound = (typeof meta.current_round !== 'undefined') ? meta.current_round : 0;

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

            // 과거 턴을 보고 있으면 진행/종료 버튼 대신 안내 문구를 표시한다. (label/힌트 라인은 왼쪽과 정렬 유지)
            if(!isLatest){
                $pastNotice.removeClass('d-none').addClass('d-flex');
                $hint.text('이전 턴은 로테이션을 진행할 수 없습니다.');
                return;
            }

            if(meta.is_closed){
                $closed.removeClass('d-none');
                $hint.text('이미 종료된 행사입니다.');
            } else if(meta.is_last){
                $finish.removeClass('d-none');
                $hint.text('마지막 턴입니다. 로테이션을 종료할 수 있습니다.');
            } else {
                $rotate.removeClass('d-none');
                // 0턴(아직 로테이션 시작 전)이면 '시작', 그 이후면 '진행'.
                if(viewRound === 0){
                    $rotate.html('<i class="ti ti-player-play me-1"></i>로테이션 시작');
                    $hint.text('0턴 배치로 로테이션을 시작합니다.');
                } else {
                    $rotate.html('<i class="ti ti-rotate-clockwise me-1"></i>로테이션 진행');
                    $hint.text('다음 턴을 자동 생성합니다. (여자 고정 / 남자 이동)');
                }
            }
        }

        function renderBoard(data){
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
                    return `<span class="badge ${genderBadgeClass(m.gender)} me-1 mb-1 d-inline-flex align-items-center gap-1">
                                ${m.name}
                                <a href="#" class="text-danger chip-remove" data-seating-id="${m.seating_id}">&times;</a>
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
                ? '0턴 배치로 로테이션을 시작하시겠습니까?'
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

        // 로테이션 종료: 행사 상태를 종료(X)로
        $('#finishBtn').on('click', async function(){
            if(!(await salert({text: '로테이션을 종료하시겠습니까? 종료 후에는 되돌릴 수 없습니다.'}))) return;

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

        // 선택된 행사 id를 URL 쿼리파라미터에 반영 (공유 가능 URL)
        function syncUrl(){
            let eventId = $('#eventSelect').val();
            let url = new URL(window.location.href);
            if(eventId){ url.searchParams.set('eventId', eventId); }
            else { url.searchParams.delete('eventId'); }
            window.history.replaceState({}, '', url);
        }

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
@endsection
