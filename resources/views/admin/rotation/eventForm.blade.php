@extends('layouts.app')

@php
    /** @var \App\Models\EventRound|null $event */
    $isEdit = $event !== null;
@endphp

@section('title', $isEdit ? '행사 상세' : '행사 생성')

@section('content')
<div class="row row-cards mt-2">

    {{-- 행사 정보 (생성/상세 공용) --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">{{ $isEdit ? '행사 정보 수정' : '행사 생성' }}</h3>
                <div class="d-flex gap-2">
                    @if ($isEdit)
                        <a href="{{ route('admin.rotation.seating.index', ['eventId' => $event->id]) }}" class="btn btn-azure px-4">
                            <i class="ti ti-layout-grid me-1"></i> 좌석 배정
                        </a>
                        <button type="button" id="goMatchesBtn" class="btn btn-outline-pink px-4">
                            <i class="ti ti-heart me-1"></i> 전체 매칭
                        </button>
                        <button type="button" id="goMatchedBtn" class="btn btn-outline-pink px-4">
                            <i class="ti ti-heart-filled me-1"></i> 매칭 결과<span id="matchedCount"></span>
                        </button>
                        <button type="button" id="goMessagesBtn" class="btn btn-outline-primary px-4">
                            <i class="ti ti-messages me-1"></i> 전체 메시지<span id="messagesCount"></span>
                        </button>
                        <button type="button" id="goRequestsBtn" class="btn btn-outline-yellow px-4">
                            <i class="ti ti-hand-stop me-1"></i> 전체 요청<span id="requestsCount"></span>
                        </button>
                    @endif
                    <a href="{{ route('admin.rotation.event.index') }}" class="btn btn-outline-secondary px-4">목록</a>
                </div>
            </div>
            <div class="card-body">
                <form id="eventForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">행사명</label>
                            <input type="text" class="form-control" name="title"
                                   value="{{ $isEdit ? $event->title : '' }}" placeholder="예: 로테이블 와인파티" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label required">타입</label>
                            <select class="form-select" name="type" required>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" {{ $isEdit && $event->type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">일시</label>
                            <input type="text" class="form-control" id="eventTimePicker" name="event_time"
                                   value="{{ $isEdit && $event->event_time ? $event->event_time->format('Y-m-d H:i') : '' }}"
                                   placeholder="날짜/시간 선택" required>
                        </div>

                        @if ($isEdit)
                            <div class="col-md-2">
                                <label class="form-label">상태</label>
                                <input type="text" class="form-control" value="{{ $statuses[$event->status] ?? $event->status }}" disabled readonly>
                                <small class="form-hint text-muted mt-2 d-block">상태는 좌석 배정에서 관리됩니다.</small>
                            </div>
                        @else
                            <div class="col-md-2">
                                <label class="form-label">테이블 수</label>
                                <input type="number" min="0" max="100" class="form-control" name="table_count" placeholder="0">
                            </div>
                        @endif
                    </div>

                    <div class="mt-3">
                        @if ($isEdit)
                            <button type="button" class="btn btn-primary px-4 btn-update">수정</button>
                        @else
                            <button type="button" class="btn btn-success px-4 btn-create">생성</button>
                            <small class="form-hint text-muted mt-2 d-block">※ 생성 시 상태는 자동으로 '대기'로 설정되며, 테이블 수를 입력하면 테이블과 0턴이 함께 생성됩니다.</small>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 참여자 목록 (상세일 때만) --}}
    @if ($isEdit)
        @php
            $maleCount   = $event->members->where('gender', 'M')->count();
            $femaleCount = $event->members->where('gender', 'F')->count();
            // 최고관리자만 참여자 비밀번호를 볼 수 있다.
            $isSuperAdmin = auth('admin')->user()?->hasRole(\App\Enums\Role::SuperAdmin->value) ?? false;
        @endphp
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">참여자</h3>
                    <div class="d-flex gap-2">
                        <span class="badge bg-blue-lt">남 {{ $maleCount }}명</span>
                        <span class="badge bg-pink-lt">여 {{ $femaleCount }}명</span>
                        <span class="badge bg-secondary-lt">총 {{ $event->members->count() }}명</span>
                    </div>
                </div>
                <div class="card-body py-2">
                    @if ($event->members->isEmpty())
                        <div class="text-center text-muted py-4">참여자가 없습니다.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th class="w-1">No</th>
                                        <th style="width: 120px;">회원 정보</th>
                                        <th class="text-center">방문 횟수</th>
                                        <th>직업 / 키 / MBTI</th>
                                        <th>지역 / 흡연</th>
                                        <th class="text-center">투표<br>(보낸/받은)</th>
                                        <th class="text-center">보낸<br>메시지</th>
                                        <th class="text-center">보낸<br>요청수</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($event->members as $i => $member)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>
                                                @php
                                                    $memberData = $member->toArray();
                                                    if ($isSuperAdmin) {
                                                        $memberData['password'] = $member->password;
                                                    }
                                                    $memberData['age'] = $member->birth_year ? (now()->year - $member->birth_year + 1) : null;
                                                @endphp
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold">{{ $member->name }}</span>
                                                    @if ($member->gender === 'F')
                                                        <span class="badge bg-pink-lt">여</span>
                                                    @else
                                                        <span class="badge bg-blue-lt">남</span>
                                                    @endif
                                                </div>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-azure py-1 px-3 mt-1 btn-member-detail"
                                                        data-member='@json($memberData)'>
                                                    상세보기
                                                </button>
                                                @if ($isSuperAdmin && $member->phone)
                                                    <a href="{{ route('admin.rotation.history.index', ['phone' => $member->phone]) }}"
                                                       target="_blank" rel="noopener"
                                                       class="btn btn-sm btn-outline-teal py-1 px-3 mt-1">
                                                        만남 이력
                                                    </a>
                                                @endif
                                                <div class="fw-bold" style="font-size: 0.85em;">
                                                    {{ $member->phone ?? '-' }}
                                                </div>
                                                <div class="text-muted" style="font-size: 0.85em;">
                                                    @if ($member->birth_year)
                                                        {{ $member->birth_year }}년생 · {{ now()->year - $member->birth_year + 1 }}세
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                                @if ($isSuperAdmin)
                                                    <div style="font-size: 0.85em; margin-top: 2px;">
                                                        <span class="text-red fw-bold font-monospace" style="user-select: all; cursor: text;">{{ $member->password ?? '-' }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $visitCounts = $member->visit_counts ?? [];
                                                    $typeLabels  = \App\Models\EventRound::typeOptions();
                                                    // 유형별 뱃지 색상
                                                    $typeBadgeCls = [
                                                        \App\Models\EventRound::TYPE_WINE => 'bg-red-lt',
                                                        \App\Models\EventRound::TYPE_ONE  => 'bg-purple-lt',
                                                    ];
                                                @endphp
                                                @if (!empty($visitCounts))
                                                    <div class="d-flex flex-column align-items-center gap-1">
                                                        @foreach ($typeLabels as $typeKey => $typeLabel)
                                                            @if (!empty($visitCounts[$typeKey]))
                                                                <span class="badge {{ $typeBadgeCls[$typeKey] ?? 'bg-azure-lt' }}">{{ $typeLabel }} {{ $visitCounts[$typeKey] }}회</span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div>{{ $member->job ?? '-' }}</div>
                                                <div class="text-muted" style="font-size: 0.85em;">
                                                    {{ $member->height ? $member->height . 'cm' : '-' }}
                                                    @if ($member->mbti)
                                                        · {{ $member->mbti }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div>{{ $member->region ?? '-' }}</div>
                                                <div style="font-size: 0.85em;">
                                                    @if (is_null($member->is_smoker))
                                                        <span class="text-muted">-</span>
                                                    @elseif ($member->is_smoker)
                                                        <span class="badge bg-red-lt">흡연</span>
                                                    @else
                                                        <span class="badge bg-green-lt">비흡연</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex flex-column align-items-center gap-1">
                                                    {{-- 보낸 투표: 이 회원이 선택한 상대 (VotedModal) --}}
                                                    @if ($member->cast_votes_count > 0)
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-blue btn-voted"
                                                                data-member-id="{{ $member->id }}"
                                                                data-member-label="{{ $member->name }}">
                                                            보낸 {{ $member->cast_votes_count }}표
                                                        </button>
                                                    @else
                                                        <span class="text-muted" style="font-size:0.85em;">보낸 0표</span>
                                                    @endif
                                                    {{-- 받은 투표: 이 회원을 선택한 사람 (VotersModal) --}}
                                                    @if ($member->received_votes_count > 0)
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-pink btn-voters"
                                                                data-member-id="{{ $member->id }}"
                                                                data-member-label="{{ $member->name }}">
                                                            받은 {{ $member->received_votes_count }}표
                                                        </button>
                                                    @else
                                                        <span class="text-muted" style="font-size:0.85em;">받은 0표</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if ($member->sent_messages_count > 0)
                                                    <button type="button"
                                                            class="btn btn-outline-primary btn-sent-messages"
                                                            data-member-id="{{ $member->id }}"
                                                            data-member-label="{{ $member->name }}">
                                                        {{ $member->sent_messages_count }}건
                                                    </button>
                                                @else
                                                    <span class="text-muted">0건</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($member->admin_requests_count > 0)
                                                    <button type="button"
                                                            class="btn btn-outline-yellow btn-sent-requests"
                                                            data-member-id="{{ $member->id }}"
                                                            data-member-label="{{ $member->name }}">
                                                        {{ $member->admin_requests_count }}건
                                                    </button>
                                                @else
                                                    <span class="text-muted">0건</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- 테이블 CRUD (상세일 때만) --}}
    @if ($isEdit)
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">테이블 관리</h3>
                    <div class="d-flex gap-2 ms-auto">
                        <button type="button" class="btn btn-danger btn-remove">
                            <i class="ti ti-trash me-1"></i> 삭제
                        </button>
                        <button type="button" class="btn btn-success btn-add-table">
                            <i class="ti ti-plus me-1"></i> 테이블 추가
                        </button>
                    </div>
                </div>
                <div id="table-table"></div>
            </div>
        </div>
    @endif
</div>

<script>
    $(document).ready(function(){
        // 일시 datepicker (기존 flatpickr, 시간 포함) — 달력에 '오늘'/'확인' 버튼 추가
        if(typeof flatpickr !== 'undefined'){
            flatpickr('#eventTimePicker', {
                enableTime: true,
                time_24hr: true,
                dateFormat: "Y-m-d H:i",
                locale: "ko",
                onReady: function(selectedDates, dateStr, instance){
                    var container = instance.calendarContainer;
                    if(container.querySelector('.fp-custom-controls')) return;

                    var btnGroup = document.createElement('div');
                    btnGroup.className = 'd-flex gap-2 mt-2 p-2 fp-custom-controls';

                    var todayBtn = document.createElement('button');
                    todayBtn.type = 'button';
                    todayBtn.className = 'btn btn-sm btn-outline-primary w-100';
                    todayBtn.innerText = '오늘';
                    todayBtn.onclick = function(){ instance.setDate(new Date(), true); };

                    var confirmBtn = document.createElement('button');
                    confirmBtn.type = 'button';
                    confirmBtn.className = 'btn btn-sm btn-primary w-100';
                    confirmBtn.innerText = '확인';
                    confirmBtn.onclick = function(){ instance.close(); };

                    btnGroup.appendChild(todayBtn);
                    btnGroup.appendChild(confirmBtn);
                    container.appendChild(btnGroup);
                }
            });
        }

        @unless ($isEdit)
        // ===== 생성 =====
        $('.btn-create').click(async function(){
            let $f = $('#eventForm');
            let title = $f.find('[name=title]').val();
            let time  = $f.find('[name=event_time]').val();
            if(!title){ await salert({text: '행사명을 입력해주세요.', cancel: false}); return false; }
            if(!time){ await salert({text: '일시를 선택해주세요.', cancel: false}); return false; }

            if(await salert({text: '행사를 생성하시겠습니까?'})){
                $.ajax({
                    url: "{{ route('admin.rotation.event.store') }}",
                    type: 'POST',
                    data: {
                        title: title,
                        type: $f.find('[name=type]').val(),
                        event_time: time,
                        table_count: $f.find('[name=table_count]').val() || 0
                    },
                    success: function(res){
                        alert(res?.msg);
                        if(res?.status === 200){ location.href = "{{ route('admin.rotation.event.index') }}"; }
                    },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
                });
            }
        });
        @else
        // ===== 상세: 매칭 조회 모달 =====
        $('#goMatchesBtn').on('click', function(){
            MatchesModal.open({{ $event->id }}, @json($event->label));
        });

        // ===== 상세: 매칭 결과(매칭된 쌍만, 남자 기준) 모달 =====
        $('#goMatchedBtn').on('click', function(){
            MatchedModal.open({{ $event->id }}, @json($event->label));
        });

        // ===== 상세: 메시지 조회 모달 =====
        $('#goMessagesBtn').on('click', function(){
            MessagesModal.open({{ $event->id }}, @json($event->label));
        });

        // ===== 상세: 전체 요청 모달 =====
        $('#goRequestsBtn').on('click', function(){
            RequestsModal.open({{ $event->id }}, @json($event->label));
        });

        // ===== 상세: 상단 버튼 수 표시 (매칭 쌍 / 메시지 / 요청) =====
        (function(){
            var eventId = {{ $event->id }};
            var matchesUrl  = "{{ route('admin.rotation.event.matches.list', ['id' => $event->id]) }}";
            var messagesUrl = "{{ route('admin.rotation.event.messages.list', ['id' => $event->id]) }}";
            var requestsUrl = "{{ route('admin.requests.list') }}";

            function setCount(sel, cnt, unit){
                $(sel).text((cnt > 0) ? ' (' + cnt + unit + ')' : '');
            }

            $.get(matchesUrl + '?matched=1', function(res){
                setCount('#matchedCount', res?.total ?? (res?.data ? res.data.length : 0), '쌍');
            });
            $.get(messagesUrl, { size: 1 }, function(res){
                setCount('#messagesCount', res?.total ?? 0, '건');
            });
            $.get(requestsUrl, { eventId: eventId, size: 1 }, function(res){
                setCount('#requestsCount', res?.total ?? 0, '건');
            });
        })();

        // ===== 상세: 받은 투표수(나를 선택한 사람) 모달 =====
        $(document).on('click', '.btn-voters', function(){
            var memberId = $(this).data('member-id');
            var label = $(this).data('member-label') || '';
            VotersModal.open({{ $event->id }}, memberId, label + ' 님을 선택한 사람');
        });

        // ===== 상세: 보낸 투표(이 회원이 선택한 상대) 모달 =====
        $(document).on('click', '.btn-voted', function(){
            var memberId = $(this).data('member-id');
            var label = $(this).data('member-label') || '';
            VotedModal.open({{ $event->id }}, memberId, label + ' 님이 선택/매칭한 상대');
        });

        // ===== 상세: 보낸 메시지 모달 (특정 회원이 보낸 것만) =====
        $(document).on('click', '.btn-sent-messages', function(){
            var memberId = $(this).data('member-id');
            var label = $(this).data('member-label') || '';
            MessagesModal.open({{ $event->id }}, label + ' 님이 보낸 메시지', { senderId: memberId });
        });

        // ===== 상세: 보낸 요청 모달 (특정 회원이 보낸 것만) =====
        $(document).on('click', '.btn-sent-requests', function(){
            var memberId = $(this).data('member-id');
            var label = $(this).data('member-label') || '';
            RequestsModal.open({{ $event->id }}, label + ' 님이 보낸 요청', { memberId: memberId });
        });

        // ===== 상세: 회원 상세정보 모달 =====
        $(document).on('click', '.btn-member-detail', function(){
            var member = $(this).data('member');
            MemberDetailModal.open(member);
        });

        // ===== 상세: 행사 정보 수정 =====
        $('.btn-update').click(async function(){
            let $f = $('#eventForm');
            let title = $f.find('[name=title]').val();
            let time  = $f.find('[name=event_time]').val();
            if(!title){ await salert({text: '행사명을 입력해주세요.', cancel: false}); return false; }
            if(!time){ await salert({text: '일시를 선택해주세요.', cancel: false}); return false; }

            if(await salert({text: '행사 정보를 수정하시겠습니까?'})){
                $.ajax({
                    url: "{{ route('admin.rotation.event.update', $event->id) }}",
                    type: 'PUT',
                    data: {
                        title: title,
                        type: $f.find('[name=type]').val(),
                        event_time: time
                    },
                    success: function(res){ alert(res?.msg); },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
                });
            }
        });

        // ===== 상세: 테이블 CRUD =====
        var table;

        $('.btn-add-table').click(async function(){
            if(await salert({text: '테이블을 1개 추가하시겠습니까?'})){
                $.ajax({
                    url: "{{ route('admin.rotation.event.tables.add', $event->id) }}",
                    type: 'POST',
                    success: function(res){ alert(res?.msg); if(res?.status === 200){ table.setData(); } },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
                });
            }
        });

        $('.btn-remove').click(async function(){
            let ids = getSelectedIds();
            if(ids.length < 1){ await salert({text: '최소 1개 이상 선택해주세요.', cancel: false}); return false; }
            if(await salert({text: `선택한 ${ids.length}개 테이블을 삭제하시겠습니까?`})){ fn_delete(ids); }
        });

        function fn_delete(ids){
            $.ajax({
                url: "{{ route('admin.rotation.event.tables.delete', $event->id) }}",
                type: 'DELETE',
                data: { ids },
                success: function(res){ alert(res?.msg); if(res?.status === 200){ table.setData(); } },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        }

        table = new Tabulator("#table-table", {
            ajaxURL: "{{ route('admin.rotation.event.tables', $event->id) }}",
            initialSort:[{column: "table_no", dir: "asc"}],
            columns: [
                { formatter: "rowSelection", titleFormatter: "rowSelection", headerSort: false, width: 60 },
                { title: "No", field: "id", width: 80 },
                { title: "테이블 번호", field: "table_no", hozAlign: "center" },
                { title: "등록일", field: "created_at", width: 200,
                    formatter: function(cell){ return cell.getValue() ? new Date(cell.getValue()).toLocaleString('ko-KR') : '-'; }
                },
                { title: "관리", width: 120, headerSort: false,
                    formatter: function(cell){
                        var data = cell.getRow().getData();
                        var $del = $("<button>").addClass("btn btn-sm btn-danger w-100").text("삭제")
                            .on("click", async function(){ if(await salert({text: `${data.table_no}번 테이블을 삭제하시겠습니까?`})){ fn_delete([data.id]); } });
                        return $del.get(0);
                    }
                },
            ],
        });

        function getSelectedIds(){ return table.getSelectedRows().map(row => row.getData().id); }
        @endunless
    });
</script>

@if ($isEdit)
    @include('admin.rotation._messagesModal')
    @include('admin.rotation._matchesModal')
    @include('admin.rotation._matchedModal')
    @include('admin.rotation._votersModal')
    @include('admin.rotation._votedModal')
    @include('admin.rotation._requestsModal')
    @include('admin.rotation._memberDetailModal')
@endif
@endsection
