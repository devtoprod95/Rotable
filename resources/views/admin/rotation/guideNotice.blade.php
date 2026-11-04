@extends('layouts.app')

@section('title', '채팅방 공지')

@section('content')
{{--
    채팅방 공지 문구 생성기.
    - 행사를 고르면 시작 시간(event_time)과 타입(와인파티/1:1)에 맞춰 문구가 자동 생성된다.
    - 문구는 화면에서 자유롭게 고친 뒤 [복사] 로 채팅방에 붙여넣는다. (수정 내용은 저장되지 않음)
--}}
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-message-2 text-pink me-1"></i> 채팅방 공지</h3>
                <span class="text-muted fs-5">— 행사 시간·타입에 맞춰 안내 문구를 자동으로 만들어요.</span>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('admin.rotation.guide.notice') }}" class="row g-3 align-items-end mb-3">
                    <div class="col-md-5">
                        <label class="form-label">행사</label>
                        <select name="eventId" class="form-select" onchange="this.form.submit()">
                            @foreach ($events as $e)
                                <option value="{{ $e->id }}" @selected($event && $event->id === $e->id)>
                                    [{{ $typeOptions[$e->type] ?? $e->type }}] {{ $e->title }}
                                    @if ($e->event_time) ({{ $e->event_time->format('n/j H:i') }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">장소</label>
                        <input type="text" name="place" class="form-control" value="{{ $place }}" placeholder="장소">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="ti ti-refresh me-1"></i> 적용
                        </button>
                    </div>
                </form>

                @if (! $event)
                    <div class="text-center text-muted py-5">선택할 수 있는 행사가 없습니다. 먼저 행사를 생성해 주세요.</div>
                @else
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                        <span class="badge {{ $event->isOneToOne() ? 'bg-pink-lt' : 'bg-orange-lt' }}">
                            {{ $typeOptions[$event->type] ?? $event->type }}
                        </span>
                        <span class="text-muted">
                            시작 시간 {{ $event->event_time ? $event->event_time->format('H:i') : '미정' }} 자동 반영
                        </span>
                        <button type="button" class="btn btn-primary btn-sm ms-auto" id="btnCopyNotice">
                            <i class="ti ti-copy me-1"></i> 복사
                        </button>
                    </div>

                    <textarea id="noticeText" class="form-control" rows="28"
                              style="line-height:1.6; font-size:.95rem;">{{ $notice }}</textarea>
                    <small class="form-hint text-muted mt-2 d-block">
                        ※ 내용은 화면에서 자유롭게 고칠 수 있지만 저장되지는 않습니다. 복사 전에 수정해 주세요.
                    </small>
                @endif
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        const btn = document.getElementById('btnCopyNotice');
        const ta  = document.getElementById('noticeText');
        if (!btn || !ta) return;

        btn.addEventListener('click', async function () {
            try {
                await navigator.clipboard.writeText(ta.value);
            } catch (e) {
                // clipboard API 불가(비 https 등) 시 선택 복사로 대체
                ta.select();
                document.execCommand('copy');
            }
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-check me-1"></i> 복사됨';
            setTimeout(() => { btn.innerHTML = original; }, 1500);
        });
    })();
</script>
@endsection
