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
            'date'  => $e->event_time ? $e->event_time->format('n/j') . '(' . ($weekdaysKo[(int) $e->event_time->dayOfWeek] ?? '') . ')' : '', // 예: 11/15(일)
        ];
    })->values();
@endphp

@section('content')
{{--
    초대 멘트 생성기 (홍보).
    - 종료가 아닌 행사를 셀렉트로 고르면, 유형/행사명/일시가 멘트에 자동 반영된다.
    - 성별(남/여) × 무료 여부로 멘트가 달라지고, 무료면 제목에 바로 명시된다.
    - 신청은 별도 링크 없이 "프로필을 눌러 신청"하도록 안내한다.
    - 플랫폼(문토/네이버)에 따라 문구가 달라진다. 네이버는 블로거 게시글에 남기는 짧은 댓글(오픈채팅 문의 유도).
    - 문구는 화면에서 자유롭게 고친 뒤 [복사] 한다. (저장되지 않음)
--}}
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if ($events->isEmpty())
                    <div class="text-center text-muted py-5">
                        초대할 수 있는 행사가 없어요. (종료되지 않은 행사가 필요합니다)<br>
                        먼저 <b>행사 목록</b>에서 행사를 생성해 주세요.
                    </div>
                @else
                <div class="btn-group w-100 mb-3" role="group" id="inv-platform" aria-label="플랫폼 선택">
                    <button type="button" class="btn btn-lg btn-primary active" data-val="munto"><i class="ti ti-message-circle me-1"></i>문토 멘트</button>
                    <button type="button" class="btn btn-lg btn-outline-primary" data-val="naver"><i class="ti ti-brand-blogger me-1"></i>네이버 블로그 댓글</button>
                </div>

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
                        <small class="form-hint text-muted">유형·일시는 자동 반영되고, 아래 내용은 자유롭게 수정할 수 있어요.</small>
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

        // ─────────────────────────────────────────────────────────────
        // 네이버: 블로거 게시글에 남기는 "댓글"용 멘트.
        //  긴 모집글이 아니라 짧고 광고 티가 덜 나게: 인사 → 한 줄 소개 → 핵심 정보(일시/장소/혜택) → 오픈채팅 문의.
        // ─────────────────────────────────────────────────────────────
        var OPEN_CHAT_URL = 'https://open.kakao.com/o/gdLw3DWi';

        function buildNaver(ctx) {
            var type    = normType(ctx.type);
            var isF     = ctx.gender === 'F';
            var recruit = isF ? '여성' : '남성';
            var benefit = ctx.free ? '선착순 무료 참여' : '';   // 네이버는 페이백이 아니라 무료 혜택
            var when    = ctx.time || '{일시}';

            // 행사 유형 명시 + 유형별 한 줄 설명
            var typeName = (type === 'wine') ? '와인파티' : '1:1 로테이션 소개팅';
            var typeDesc = (type === 'wine')
                ? '와인과 핑거푸드를 즐기며 테이블을 옮겨 다니고, 여러 분과 자연스럽게 대화하는 자리예요.'
                : '여러 분과 한 분씩 마주 앉아 1:1로 대화하고, 서로 마음에 든 경우에만 연락처가 공개돼요.';
            // 연애로 이어질 기대감을 주는 한 줄 (모집 성별 기준으로 상대를 표현)
            var partner = isF ? '남자친구' : '여자친구';
            var others  = isF ? '직장인 남성분들' : '여성분들';
            var hope = '진지하게 연애 상대를 찾는 ' + others + '이 함께해요. '
                     + '대화 몇 번이면 ' + partner + '로 이어질 인연, 이번에 꼭 만나보세요 😊';

            var lines = [
                '안녕하세요 :) 로테이블입니다. 좋은 글 잘 보고 갑니다!',
                '',
                '저희는 선릉역에서 ' + typeName + (type === 'wine' ? '를' : '을') + ' 운영하고 있는데, '
                    + (benefit
                        ? '이번 모임에 ' + recruit + '분을 선착순 무료로 초대하고 있어 조심스럽게 소개드려요 💌'
                        : '이번 모임에 함께하실 ' + recruit + '분을 찾고 있어 조심스럽게 소개드려요 💌'),
                typeDesc,
                '',
                '📅 ' + when,
                '📍 선릉역 도보 3분 이내 (정확한 장소는 확정 후 안내)',
                '👥 ' + recruit + ' 참가자 모집' + (benefit ? ' · ' + benefit + ' ✨' : ''),
                '',
                hope,
                '오픈채팅방에 들어오시면 참여하실 ' + (isF ? '남성분들' : '여성분들') + '의 상세 프로필과 로테이블 진행 방식을 볼 수 있는 링크를 안내해 드려요.',
                '위 일정 말고도 다른 시간대와 다양한 행사들도 계속 안내되고 있으니, 부담 없이 들어와서 구경해 보세요 👀',
                '궁금하신 점도 오픈채팅으로 편하게 물어봐 주세요!',
                OPEN_CHAT_URL
            ];
            return lines.join("\n");
        }

        function build(ctx) {
            if (ctx.platform === 'naver') { return buildNaver(ctx); }
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
        var state = { platform: 'munto', gender: 'F', free: '1' };

        function currentEvent() {
            var id = String($('#inv-event').val());
            return EVENTS.find(function (e) { return String(e.id) === id; }) || EVENTS[0];
        }

        // 혜택 버튼/요약 라벨: 문토는 80% 페이백, 네이버는 무료 참여
        function benefitLabel() { return state.platform === 'naver' ? '무료 참여' : '80% 페이백'; }

        function render() {
            $('#inv-free button[data-val="1"]').text(benefitLabel());
            var ev = currentEvent();
            var ctx = {
                title: ev.title,
                time: ev.time,
                date: ev.date,
                platform: state.platform,
                type: ev.type,
                gender: state.gender,
                free: state.free === '1'
            };
            $('#inv-text').val(build(ctx));
            var typeLabel = normType(ev.type) === 'wine' ? '와인파티' : '1:1 소개팅';
            $('#inv-summary').text(
                (state.platform === 'naver' ? '네이버' : '문토') + ' · ' + typeLabel + ' · ' + GENDER_LABEL[state.gender]
                + ' · ' + (state.free === '1' ? benefitLabel() : '혜택 없음')
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
        bindGroup('#inv-platform', 'platform');
        // 선택된 플랫폼은 꽉 찬 색, 아닌 쪽은 테두리만
        $('#inv-platform').on('click', 'button', function () {
            $('#inv-platform button').removeClass('btn-primary').addClass('btn-outline-primary');
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        });
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
