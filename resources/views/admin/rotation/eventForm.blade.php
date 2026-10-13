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
                        <a href="{{ route('admin.rotation.event.messages', $event->id) }}" class="btn btn-outline-primary px-4">
                            <i class="ti ti-messages me-1"></i> 메시지 조회
                        </a>
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
                                        <th>이름 / 성별</th>
                                        <th>연락처</th>
                                        <th class="text-center">출생연도<br>나이</th>
                                        <th>직업</th>
                                        <th class="text-center">키<br>MBTI</th>
                                        <th>지역</th>
                                        <th class="text-center">흡연</th>
                                        <th class="text-center">보낸<br>메시지</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($event->members as $i => $member)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>
                                                <span class="fw-bold">{{ $member->name }}</span>
                                                @if ($member->gender === 'F')
                                                    <span class="badge bg-pink-lt">여</span>
                                                @else
                                                    <span class="badge bg-blue-lt">남</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $member->phone ?? '-' }}</td>
                                            <td class="text-center">
                                                {{ $member->birth_year ?? '-' }}<br>
                                                <span class="text-muted">{{ $member->birth_year ? (now()->year - $member->birth_year + 1) . '세' : '-' }}</span>
                                            </td>
                                            <td>{{ $member->job ?? '-' }}</td>
                                            <td class="text-center">
                                                {{ $member->height ? $member->height . 'cm' : '-' }}<br>
                                                <span class="text-muted">{{ $member->mbti ?? '-' }}</span>
                                            </td>
                                            <td>{{ $member->region ?? '-' }}</td>
                                            <td class="text-center">
                                                @if (is_null($member->is_smoker))
                                                    -
                                                @elseif ($member->is_smoker)
                                                    <span class="badge bg-red-lt">흡연</span>
                                                @else
                                                    <span class="badge bg-green-lt">비흡연</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($member->sent_messages_count > 0)
                                                    <span class="badge bg-azure-lt">{{ $member->sent_messages_count }}건</span>
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
@endsection
