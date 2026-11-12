@extends('layouts.app')

@section('title', '채팅방 공지')

@section('content')
{{--
    채팅방 공지 문구 생성기. (탭 2개)
    - 공지 안내   : 행사 시작 시간(event_time)·타입(와인파티/1:1)에 맞춰 서버가 공지 문구를 만든다.
    - 당일 안내 멘트: 시작 몇 시간 전에 보내는 리마인드. "별일 없으신지 / 꼭 챙길 것 / 다른 분들을 위해 늦지 않게 조심히" 를
                     설레는 톤으로 안내한다. 행사명·시작/입장(10분 전) 시간·유형별 문구가 자동 반영된다.
    - 행사/장소는 두 탭이 함께 쓰고, 바꾸면 새로고침돼도 보던 탭(?tab=)이 유지된다.
    - 문구는 화면에서 자유롭게 고친 뒤 [복사] 로 채팅방에 붙여넣는다. (수정 내용은 저장되지 않음)
--}}
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-message-2 text-pink me-1"></i> 채팅방 공지</h3>
                <span class="text-muted fs-5 d-none d-md-inline">— 행사 시간·타입에 맞춰 공지와 당일 안내 멘트를 자동으로 만들어요.</span>
            </div>

            <div class="card-body">
                {{-- 탭: 공지 안내 / 당일 안내 멘트 (잘 보이도록 큰 버튼형) --}}
                <div class="btn-group w-100 mb-3" role="tablist" id="noticeTabs" aria-label="문구 종류">
                    <button type="button" class="btn btn-lg {{ $tab === 'notice' ? 'btn-primary active' : 'btn-outline-primary' }}"
                            data-tab="notice" role="tab" aria-selected="{{ $tab === 'notice' ? 'true' : 'false' }}">
                        <i class="ti ti-speakerphone me-1"></i> 공지 안내
                    </button>
                    <button type="button" class="btn btn-lg {{ $tab === 'reminder' ? 'btn-primary active' : 'btn-outline-primary' }}"
                            data-tab="reminder" role="tab" aria-selected="{{ $tab === 'reminder' ? 'true' : 'false' }}">
                        <i class="ti ti-bell-heart me-1"></i> 당일 안내 멘트
                    </button>
                </div>

                <form method="GET" action="{{ route('admin.rotation.guide.notice') }}" class="row g-3 align-items-end mb-3" id="noticeForm">
                    <input type="hidden" name="tab" id="noticeTabInput" value="{{ $tab }}">
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
                        <input type="text" name="place" id="noticePlace" class="form-control" value="{{ $place }}" placeholder="장소">
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
                        <span class="text-muted" id="noticeSummary">
                            시작 시간 {{ $event->event_time ? $event->event_time->format('H:i') : '미정' }} 자동 반영
                        </span>
                        <button type="button" class="btn btn-primary btn-lg px-4 ms-auto" id="btnCopyNotice">
                            <i class="ti ti-copy me-1"></i> 복사
                        </button>
                    </div>

                    {{-- 공지 안내 (서버 생성) --}}
                    <textarea id="noticeText" class="form-control notice-pane" data-pane="notice" rows="28"
                              style="line-height:1.6; font-size:.95rem; {{ $tab === 'notice' ? '' : 'display:none;' }}">{{ $notice }}</textarea>
                    {{-- 당일 안내 멘트 (화면에서 생성) --}}
                    <textarea id="reminderText" class="form-control notice-pane" data-pane="reminder" rows="22"
                              style="line-height:1.75; font-size:.95rem; {{ $tab === 'reminder' ? '' : 'display:none;' }}"></textarea>

                    <small class="form-hint text-muted mt-2 d-block">
                        ※ 내용은 화면에서 자유롭게 고칠 수 있지만 저장되지는 않습니다. 복사 전에 수정해 주세요.
                    </small>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($event)
<script>
    (function () {
        // 당일 안내 멘트용 행사 정보
        var EV = {
            title: @json($event->title),
            isOne: @json($event->isOneToOne()),
            at:    @json($event->event_time?->format('Y-m-d\TH:i')) // 예: 2026-11-15T14:00
        };

        // "2026-11-15T14:00" → Date (로컬 시간)
        function parseAt(at) {
            if (!at) { return null; }
            var m = at.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
            return m ? new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]) : null;
        }
        // 오전/오후 12시간제: 오후 2시 / 오전 11시 30분
        function timeKo(d) {
            var h = d.getHours(), mi = d.getMinutes();
            var h12 = h % 12 === 0 ? 12 : h % 12;
            return (h < 12 ? '오전' : '오후') + ' ' + h12 + '시' + (mi ? ' ' + mi + '분' : '');
        }
        // 당일 안내 멘트: 시작 몇 시간 전에 보내는 리마인드. (날짜 표기 없이 항상 "오늘")
        //  곧 만날 설렘 → 시간/장소 → 늦지 않게(다른 분들을 위해) → 꼭 챙길 것 → 응원
        function buildReminder(place) {
            var d     = parseAt(EV.at);
            var start = d ? timeKo(d) : '{시간}';
            var entry = d ? timeKo(new Date(d.getTime() - 10 * 60000)) : '{입장 시간}';
            var thrill = EV.isOne
                ? '한 분 한 분 마주 앉아 이야기 나누다 보면, 유난히 대화가 잘 통하는 분이 분명 있을 거예요 💬'
                : '와인 한 잔 기울이며 이야기 나누다 보면, 자꾸 눈길이 가는 분이 생기실 거예요 🍷';

            return [
                '[오늘의 로테이블 💌]',
                '안녕하세요, 로테이블입니다 😊',
                "몇 시간 뒤면 드디어 '" + EV.title + "' 시작이에요!",
                '혹시 컨디션이나 일정에 별일은 없으신가요?',
                '',
                '오늘 어떤 분과 마주 앉게 될지 저희도 벌써 두근두근해요 ✨',
                thrill,
                '평소보다 조금 더 예쁘고 멋지게, 설레는 마음만 챙겨서 오세요 😉',
                '',
                '🕑 시작: 오늘 ' + start,
                '🚪 입장: ' + entry + '부터',
                '📍 장소: ' + (place || '{장소}'),
                '',
                '모든 분이 함께 시작해야 대화 시간이 공평하게 돌아가요.',
                '다른 분들을 위해 늦지 않게, 조심히 와주세요 🙏',
                '혹시 늦으시거나 못 오시게 되면 꼭 미리 DM 부탁드려요!',
                '',
                '✅ 출발 전 꼭 확인해 주세요',
                '- 신분증 (남성분은 재직 확인 서류도)',
                '- 넉넉히 충전된 휴대폰 (모든 진행이 스마트폰으로 이뤄져요)',
                '',
                '오늘 좋은 인연 꼭 만나시길 응원할게요. 이따 반갑게 만나요 💘'
            ].join('\n');
        }

        var placeEl    = document.getElementById('noticePlace');
        var reminderEl = document.getElementById('reminderText');
        function renderReminder() { reminderEl.value = buildReminder((placeEl.value || '').trim()); }
        // 장소는 입력 즉시 당일 안내 멘트에 반영 (공지 안내는 [적용] 시 서버에서 다시 생성)
        placeEl.addEventListener('input', renderReminder);
        renderReminder();

        // 탭 전환: 버튼 색 + 보이는 문구 + hidden(tab) 값 + 주소(새로고침 시 유지)
        var tabBtns = document.querySelectorAll('#noticeTabs [data-tab]');
        function activeTab() {
            var on = document.querySelector('#noticeTabs .active');
            return on ? on.getAttribute('data-tab') : 'notice';
        }
        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-tab');
                tabBtns.forEach(function (b) {
                    var on = b === btn;
                    b.classList.toggle('active', on);
                    b.classList.toggle('btn-primary', on);
                    b.classList.toggle('btn-outline-primary', !on);
                    b.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                document.querySelectorAll('.notice-pane').forEach(function (el) {
                    el.style.display = el.getAttribute('data-pane') === tab ? '' : 'none';
                });
                document.getElementById('noticeTabInput').value = tab;
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                history.replaceState(null, '', url.toString());
            });
        });

        // 복사: 지금 보이는 탭의 문구
        var copyBtn = document.getElementById('btnCopyNotice');
        copyBtn.addEventListener('click', async function () {
            var ta = document.getElementById(activeTab() === 'reminder' ? 'reminderText' : 'noticeText');
            try {
                await navigator.clipboard.writeText(ta.value);
            } catch (e) {
                // clipboard API 불가(비 https 등) 시 선택 복사로 대체
                ta.select();
                document.execCommand('copy');
            }
            var original = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="ti ti-check me-1"></i> 복사됨';
            setTimeout(function () { copyBtn.innerHTML = original; }, 1500);
        });
    })();
</script>
@endif
@endsection
