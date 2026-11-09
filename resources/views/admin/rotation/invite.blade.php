@extends('layouts.app')

@section('title', '초대 멘트')

@php
    // 셀렉트/멘트 자동 채움용 행사 데이터(JS 로 전달).
    $weekdaysKo = ['일', '월', '화', '수', '목', '금', '토'];
    $eventsJson = $events->map(function ($e) use ($weekdaysKo) {
        $time = '';
        if ($e->event_time) {
            $dt   = $e->event_time;
            $dow  = $weekdaysKo[(int) $dt->dayOfWeek] ?? '';
            // 오전/오후 12시간제 표기 (예: 오후 12시 00분 / 오전 9시 30분)
            $hour24 = (int) $dt->format('G');
            $ampm   = $hour24 < 12 ? '오전' : '오후';
            $hour12 = $hour24 % 12;
            if ($hour12 === 0) { $hour12 = 12; }
            $min    = $dt->format('i');
            $timeKo = $ampm . ' ' . $hour12 . '시' . ($min !== '00' ? ' ' . (int) $min . '분' : '');
            $time   = $dt->format('n월 j일') . ' ' . $dow . '요일 ' . $timeKo;
        }
        return [
            'id'    => $e->id,
            'title' => $e->title,
            'type'  => $e->type, // wine | one
            'time'  => $time,    // 예: 11월 11일 화요일 오후 12시
        ];
    })->values();
@endphp

@section('content')
{{--
    초대 멘트 생성기 (홍보).
    - 종료가 아닌 행사를 셀렉트로 고르면, 유형/행사명/일시가 멘트에 자동 반영된다.
    - 성별(남/여) × 무료 여부로 멘트가 달라지고, 무료면 제목에 바로 명시된다.
    - 신청은 별도 링크 없이 "프로필을 눌러 신청"하도록 안내한다.
    - 문구는 화면에서 자유롭게 고친 뒤 [복사] 한다. (저장되지 않음)
--}}
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-send text-pink me-1"></i> 초대 멘트</h3>
                <span class="text-muted fs-5">— 행사·성별·무료 여부에 맞춰 설레는 초대 메시지를 만들어 복사하세요.</span>
            </div>

            <div class="card-body">
                @if ($events->isEmpty())
                    <div class="text-center text-muted py-5">
                        초대할 수 있는 행사가 없어요. (종료되지 않은 행사가 필요합니다)<br>
                        먼저 <b>행사 목록</b>에서 행사를 생성해 주세요.
                    </div>
                @else
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-5">
                        <label class="form-label mb-1">행사 선택</label>
                        <select class="form-select" id="inv-event">
                            @foreach ($events as $e)
                                <option value="{{ $e->id }}">
                                    [{{ $typeOptions[$e->type] ?? $e->type }}] {{ $e->title }}
                                    @if ($e->event_time) ({{ $e->event_time->format('n/j H:i') }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="form-hint text-muted">종료되지 않은 행사만 표시됩니다. 유형·일시는 자동 반영돼요.</small>
                    </div>
                    <div class="col-12 col-md-auto">
                        <label class="form-label mb-1">성별</label>
                        <div class="btn-group d-flex" role="group" id="inv-gender">
                            <button type="button" class="btn btn-outline-blue" data-val="M">👨 남성</button>
                            <button type="button" class="btn btn-outline-pink active" data-val="F">👩 여성</button>
                        </div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <label class="form-label mb-1">혜택</label>
                        <div class="btn-group d-flex" role="group" id="inv-free">
                            <button type="button" class="btn btn-outline-secondary" data-val="0">혜택 없음</button>
                            <button type="button" class="btn btn-outline-green active" data-val="1">80% 페이백</button>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 mb-3">
                    <div class="text-secondary" style="font-size:0.92em;">
                        <i class="ti ti-info-circle me-1"></i>
                        행사명·일시는 선택한 행사에서 자동으로 채워집니다.
                        내용은 자유롭게 수정할 수 있으며 저장되지는 않습니다.
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-pink-lt" id="inv-summary"></span>
                    <button type="button" class="btn btn-primary btn-sm ms-auto" id="inv-copy">
                        <i class="ti ti-copy me-1"></i> 복사
                    </button>
                </div>

                <textarea id="inv-text" class="form-control" rows="20"
                          style="line-height:1.75; font-size:.95rem;"></textarea>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        var EVENTS = @json($eventsJson);
        if (!EVENTS.length) { return; }

        // 행사 타입 코드 정규화: 'wine' | 'one'/'solo' → 'wine' | 'solo'
        function normType(t) { return (t === 'wine') ? 'wine' : 'solo'; }

        // ─────────────────────────────────────────────────────────────
        // 멘트 풀: build(ctx) 가 초대 멘트 전문을 만든다.
        //  ctx = { title, time, type, gender('M'|'F'), free(bool) }
        //  톤: 운영자가 직접 보내는 담백한 권유체 + 핵심 혜택 나열 + 가벼운 마감 유도.
        // ─────────────────────────────────────────────────────────────

        // 첫 인사 + 초대 한 줄. 혜택(ON)이면 "참가비 80% 페이백" 조건으로 모시고 싶다고 녹인다.
        function greetingLines(ctx) {
            var out = ['안녕하세요 😊 로테이블입니다.'];
            if (ctx.free) {
                out.push('이번 오픈 기념으로, 참가비의 80%를 페이백(환급)해 드리는 조건으로 모시고 싶어 연락드려요.');
            } else {
                out.push("'" + (ctx.title || '{행사명}') + "' 행사에 초대드리고 싶어 연락드려요.");
            }
            return out;
        }

        // 유형별 핵심 소개 + 혜택 (성별 톤 살짝 반영). 참고 멘트처럼 정보 위주 + 부담 없음 강조.
        function bodyCopy(type, gender) {
            if (type === 'wine') {
                return [
"선릉역 도보 3분, 와인·핑거푸드 무제한으로 진정성있는 사람들과 만남을 즐기는 로테이션 소개팅이에요.",
"한 자리에 앉아만 계시면 여러 분들과 자연스럽게 돌아가며 대화하게 되고, 중간중간 테이블끼리 즐기는 가벼운 게임 콘텐츠도 있어요.",
"억지로 텐션 올리는 분위기가 아니라, 차분하고 편안하게 대화에 집중하는 자리예요.",
"마음에 드는 분은 비밀 투표로 선택도 할 수 있고, 글 작성 없이 스마트폰으로 간편하게, 2차 비용도 무료랍니다.",
(gender === 'F'
    ? '혼자 오셔도 어색할 틈 없이 자연스럽게 어울릴 수 있어요.'
    : '혼자 오셔도 어색할 틈 없이 자연스럽게 어울릴 수 있어요.')
                ].join("\n");
            }
            // 1:1
            return [
"선릉역 도보 3분, 다과·음료와 함께 한 분씩 마주 앉아 1:1로 대화하는 소개팅이에요.",
"모든 대화가 끝나면 마음에 든 분께 비밀 투표하고, 서로 선택했을 때만 연락처가 공개돼요.",
"글 작성 없이 스마트폰으로 간편하게, 거절 걱정 없이 편하게 참여하실 수 있어요.",
(gender === 'F'
    ? '운영진이 안전하고 편안하게 끝까지 챙겨드려요.'
    : '짧아도 제대로 대화할 수 있어 단체보다 훨씬 집중이 잘 돼요.')
            ].join("\n");
        }

        // 마무리 권유 + 마감 유도.
        function closingLines() {
            return [
'좋은 인연 만나러 편하게 오시겠어요? 자리는 조기 마감될 수 있어요 💘',
'신청 전에 제 프로필을 눌러서 행사 상세글 한 번 더 보시고 결정해 주세요!'
            ];
        }

        function build(ctx) {
            var type = normType(ctx.type);
            var lines = [];
            lines = lines.concat(greetingLines(ctx));
            lines.push("");
            lines.push(bodyCopy(type, ctx.gender));
            lines.push("");
            lines.push("🗓 일시: " + (ctx.time || '{일시}'));
            lines.push("");
            lines = lines.concat(closingLines());
            return lines.join("\n");
        }

        var GENDER_LABEL = { M: '남성', F: '여성' };
        var state = { gender: 'F', free: '1' };

        function currentEvent() {
            var id = String($('#inv-event').val());
            return EVENTS.find(function (e) { return String(e.id) === id; }) || EVENTS[0];
        }

        function render() {
            var ev = currentEvent();
            var ctx = {
                title: ev.title,
                time: ev.time,
                type: ev.type,
                gender: state.gender,
                free: state.free === '1'
            };
            $('#inv-text').val(build(ctx));
            var typeLabel = normType(ev.type) === 'wine' ? '와인파티' : '1:1 소개팅';
            $('#inv-summary').text(
                typeLabel + ' · ' + GENDER_LABEL[state.gender]
                + ' · ' + (state.free === '1' ? '80% 페이백' : '혜택 없음')
            );
        }

        function bindGroup(sel, key) {
            $(sel).on('click', 'button', function () {
                $(sel + ' button').removeClass('active');
                $(this).addClass('active');
                state[key] = String($(this).data('val'));
                render();
            });
        }
        bindGroup('#inv-gender', 'gender');
        bindGroup('#inv-free', 'free');
        $('#inv-event').on('change', render);

        $('#inv-copy').on('click', async function () {
            var ta = document.getElementById('inv-text');
            try {
                await navigator.clipboard.writeText(ta.value);
            } catch (e) {
                ta.select();
                document.execCommand('copy');
            }
            var original = $(this).html();
            $(this).html('<i class="ti ti-check me-1"></i> 복사됨');
            var btn = this;
            setTimeout(function () { $(btn).html(original); }, 1500);
        });

        render();
    });
</script>
@endsection
