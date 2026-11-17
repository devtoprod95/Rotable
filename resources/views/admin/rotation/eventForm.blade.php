@extends('layouts.app')

@php
    /** @var \App\Models\EventRound|null $event */
    $isEdit = $event !== null;
@endphp

@section('title', $isEdit ? '행사 상세' : '행사 생성')

{{-- 행사 상세는 메뉴(행사 운영 > 행사 상세)에 있어 브레드크럼이 자동으로 맞는다.
     행사 생성은 메뉴에 없는 하위 화면이라 '행사 목록' 뒤에 실제 화면명을 덧붙인다. --}}
@unless ($isEdit)
    @section('breadcrumbCurrent', '행사 생성')
@endunless

@section('content')

<style>
    /* 행사 생성 폼 */
    .create-type-group .btn{ min-height:48px; font-weight:700; }
    .create-stepper .btn{ min-width:52px; }
    .create-stepper input::-webkit-outer-spin-button,
    .create-stepper input::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0; }
    .create-stepper input[type=number]{ -moz-appearance:textfield; }
    .create-switch{ min-height:48px; display:flex; align-items:center; gap:.5rem; padding-left:2.5rem; }
    .create-hint > div, .create-notes > div{ display:block; word-break:keep-all; }
    .create-hint{ font-size:.85rem; line-height:1.6; color:#495057; }
    .create-notes{ font-size:.9rem; line-height:1.75; color:#343a40; }
    #eventTimePicker[readonly]{ background-color:#fff; cursor:pointer; }

    /* 행사 상세 바로가기 버튼(.eventform-btn-grid) 스타일은 public/css/custom.css 공통 규칙 (로테이션 진행과 공유) */

    /* 참여자 성별 탭: 터치하기 편한 높이, 데스크톱에선 폭 제한 */
    .member-gender-tabs .btn{ min-height:44px; font-weight:700; font-size:1rem; }
    @media (min-width: 768px){ .member-gender-tabs{ max-width:420px; } }

    /* 참여자 표: 모바일에서는 가로 스크롤 대신 '카드형'으로 세로 재배치한다.
       각 td 의 data-label 을 왼쪽 항목명으로 보여줘, 좁은 화면에서도 스크롤 없이 읽힌다. */
    @media (max-width: 767.98px){
        .members-table thead{ display:none; }
        .members-table, .members-table tbody, .members-table tr, .members-table td{ display:block; width:100%; }
        .members-table tr{
            border:1px solid #e6e8eb; border-radius:12px;
            padding:10px 12px; margin-bottom:12px; background:#fff;
            box-shadow:0 1px 3px rgba(0,0,0,.05);
        }
        .members-table td{
            border:0 !important; padding:4px 0 !important; text-align:left !important;
            display:flex; gap:10px; align-items:flex-start;
        }
        .members-table td::before{
            content: attr(data-label);
            flex:0 0 84px; color:#8a92a0; font-weight:700; font-size:0.82rem;
        }
        /* 회원 정보 셀은 라벨 없이 넓게(카드 제목처럼) */
        .members-table td.m-headcell{ display:block; border-bottom:1px solid #f1f3f5 !important; padding-bottom:8px !important; margin-bottom:4px; }
        .members-table td.m-headcell::before{ display:none; }
        .members-table td .d-flex.flex-column{ align-items:flex-start !important; }
    }

    /* 음료 목록 설정 (접기/펼치기) */
    .drink-setting-head{ cursor:pointer; user-select:none; min-height:48px; }
    .drink-setting-chevron{ transition:transform .15s ease; }
    .drink-setting-head[aria-expanded="true"] .drink-setting-chevron{ transform:rotate(180deg); }
    .drink-setting-notes > div{ display:block; font-size:.85rem; line-height:1.65; color:#495057; word-break:keep-all; }
    /* 음료 카드 그리드: 모바일 2열 / 태블릿 3열 / 데스크톱 4열 */
    .drink-chip-grid{ display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.5rem; }
    @media (min-width: 576px){ .drink-chip-grid{ grid-template-columns:repeat(3, minmax(0, 1fr)); } }
    @media (min-width: 992px){ .drink-chip-grid{ grid-template-columns:repeat(4, minmax(0, 1fr)); } }
    .drink-card{ display:flex; align-items:center; gap:.5rem; padding:.55rem .6rem; border:1px solid #f1d4da; border-radius:12px; background:#fff7f8; min-height:48px; }
    .drink-card-emoji{ font-size:1.3rem; line-height:1; flex:0 0 auto; }
    .drink-card-name{ flex:1 1 auto; min-width:0; font-weight:700; font-size:.92rem; overflow-wrap:anywhere; line-height:1.3; }
    .drink-card-del{ flex:0 0 auto; width:32px; height:32px; border:0; border-radius:50%; background:transparent; color:#d63939; display:inline-flex; align-items:center; justify-content:center; }
    .drink-card-del:hover, .drink-card-del:focus{ background:#fde2e2; }
    .drink-chip-empty{ grid-column:1 / -1; color:#868e96; font-size:.9rem; padding:.5rem 0; }

    /* 음료 전달 완료 체크 버튼 */
    .drink-serve-btn{ cursor:pointer; transition:background .12s ease, color .12s ease; min-height:44px; }
    .drink-serve-btn .drink-serve-check{ font-size:1.15rem; line-height:1; color:#94a3b8; }
    .drink-serve-btn.is-served{ background:#0ca678 !important; color:#fff; }
    .drink-serve-btn.is-served .text-dark{ color:#fff !important; }
    .drink-serve-btn.is-served .drink-serve-check{ color:#fff; }
    .drink-serve-btn:disabled{ opacity:.6; }

    /* 참여자 카드의 상세보기/만남 이력 버튼: 모바일에서 같은 폭으로 나란히, 터치하기 편한 높이 */
    @media (max-width: 767.98px){
        .members-table .member-action-btns{ width:100%; }
        .members-table .member-action-btns .btn{ flex:1 1 0; min-height:40px; display:inline-flex; align-items:center; justify-content:center; }
    }

    /* 테이블 관리 표: 엑셀(격자) 느낌 제거 — 세로 격자선 없이 일반 테이블처럼. (모바일 접기 동작은 유지) */
    #table-table.tabulator { border: 1px solid #e9ecf1; border-radius: 10px; }
    #table-table.tabulator .tabulator-header { background: #f6f8fb; border-bottom: 1px solid #e9ecf1; }
    #table-table.tabulator .tabulator-header .tabulator-col { background: transparent; border-right: none; font-weight: 700; color: #495057; }
    #table-table.tabulator .tabulator-row .tabulator-cell { border-right: none; }
    #table-table.tabulator .tabulator-row { border-bottom: 1px solid #f0f2f6; }
    #table-table.tabulator .tabulator-row.tabulator-row-even { background: #fcfcfd; }
    #table-table.tabulator .tabulator-row:hover { background: #f3f7ff; }
</style>

{{-- 수정(상세) 화면에서만: 행사 운영 공용 탭바 [행사 상세][체크인][로테이션 진행] --}}
@if ($isEdit)
<div class="mt-2">
    <x-event-tabs :event-id="$event->id" active="info" />
</div>
@endif

<div class="row row-cards">

    {{-- 바로가기 버튼 (상세일 때만) — 정보 수정 영역과 분리, 같은 크기 --}}
    @if ($isEdit)
        <div class="col-12">
            <div class="eventform-btn-grid">
                <button type="button" id="goRegisterQrBtn" class="btn btn-outline-dark">
                    <i class="ti ti-qrcode me-1"></i> 입장 QR
                </button>
                <button type="button" id="goMatchesBtn" class="btn btn-outline-pink">
                    <i class="ti ti-heart me-1"></i> 전체 매칭
                </button>
                <button type="button" id="goMatchedBtn" class="btn btn-outline-pink">
                    <i class="ti ti-heart-filled me-1"></i> 매칭 결과<span id="matchedCount"></span>
                </button>
                <button type="button" id="goMessagesBtn" class="btn btn-outline-primary">
                    <i class="ti ti-messages me-1"></i> 메시지<span id="messagesCount"></span>
                </button>
                <button type="button" id="goRequestsBtn" class="btn btn-outline-yellow">
                    <i class="ti ti-hand-stop me-1"></i> 요청<span id="requestsCount"></span>
                </button>
                <a href="{{ route('admin.rotation.event.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-list me-1"></i> 목록
                </a>
            </div>
        </div>
    @endif

    {{-- 행사 정보 (생성/상세 공용) --}}
    <div class="col-12">
        <div class="card">
            @if ($isEdit)
                {{-- 접기/펼치기(상태 기억) --}}
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2" role="button" tabindex="0"
                     data-bs-toggle="collapse" data-bs-target="#eventInfoCollapse" aria-expanded="true" aria-controls="eventInfoCollapse" style="cursor:pointer;">
                    <h3 class="card-title mb-0">행사 정보 수정 <span class="text-secondary small fw-normal ms-1">탭하여 접기/펼치기</span></h3>
                </div>
            @else
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h3 class="card-title mb-0"><i class="ti ti-calendar-plus me-1"></i>행사 생성</h3>
                    <a href="{{ route('admin.rotation.event.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-list me-1"></i> 목록
                    </a>
                </div>
            @endif
            <div @if ($isEdit) class="collapse show" id="eventInfoCollapse" @endif>
            <div class="card-body">
                <form id="eventForm" onsubmit="return false;">
                @if ($isEdit)
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
                                <small class="form-hint text-muted mt-2 d-block">상태는 로테이션 진행에서 관리됩니다.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">리뷰 인증 게이트</label>
                                <label class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" id="reviewGateToggle"
                                           {{ $event->review_gate_enabled ? 'checked' : '' }}>
                                    <span class="form-check-label" id="reviewGateLabel">
                                        {{ $event->review_gate_enabled ? '켜짐 (결과 전 리뷰 인증 요구)' : '꺼짐' }}
                                    </span>
                                </label>
                                <small class="form-hint text-muted mt-1 d-block">켜면 종료(X) 후 참가자가 매칭 결과를 보기 전 리뷰 이미지를 올려야 합니다.</small>
                            </div>
                        @endif
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary px-4 btn-update">수정</button>
                    </div>
                @else
                    {{-- 행사 생성: 모바일은 한 줄에 하나씩, PC 는 2열. 타입·테이블 수는 터치하기 쉬운 버튼형 --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required" for="createTitle">행사명</label>
                            <input type="text" class="form-control form-control-lg" id="createTitle" name="title"
                                   placeholder="예: 로테이블 1:1 소개팅" maxlength="255" autocomplete="off" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label required d-block">타입</label>
                            <div class="btn-group w-100 create-type-group" role="group" aria-label="행사 타입">
                                @foreach ($types as $value => $label)
                                    <input type="radio" class="btn-check" name="type" id="createType{{ ucfirst($value) }}" value="{{ $value }}"
                                           autocomplete="off" {{ $loop->first ? 'checked' : '' }}>
                                    <label class="btn btn-outline-{{ $value === \App\Models\EventRound::TYPE_ONE ? 'purple' : 'red' }}" for="createType{{ ucfirst($value) }}">
                                        <i class="ti {{ $value === \App\Models\EventRound::TYPE_ONE ? 'ti-users' : 'ti-glass-full' }} me-1"></i>{{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label required" for="eventTimePicker">일시</label>
                            <input type="text" class="form-control form-control-lg" id="eventTimePicker" name="event_time"
                                   placeholder="날짜/시간 선택" readonly required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="createTableCount">테이블 수</label>
                            <div class="input-group create-stepper">
                                <button type="button" class="btn btn-outline-secondary" data-step="-1" aria-label="테이블 수 줄이기"><i class="ti ti-minus"></i></button>
                                <input type="number" class="form-control form-control-lg text-center" id="createTableCount" name="table_count"
                                       min="0" max="100" value="0" inputmode="numeric">
                                <button type="button" class="btn btn-outline-secondary" data-step="1" aria-label="테이블 수 늘리기"><i class="ti ti-plus"></i></button>
                            </div>
                            <div class="create-hint mt-1">
                                <div>테이블을 함께 만들어요. (0이면 나중에 행사 상세에서 추가)</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label d-block">리뷰 인증 게이트</label>
                            <label class="form-check form-switch create-switch mb-0">
                                <input class="form-check-input" type="checkbox" checked id="reviewGateToggle">
                                <span class="form-check-label" id="reviewGateLabel">켜짐 (결과 전 리뷰 인증 요구)</span>
                            </label>
                            <div class="create-hint mt-1">
                                <div>켜면 종료 후 참가자가 매칭 결과를 보기 전에</div>
                                <div>리뷰 이미지를 올려야 해요.</div>
                            </div>
                        </div>
                    </div>

                    {{-- 생성 안내: 한 행에 한 문장. 1:1 안내는 1:1을 골랐을 때만 --}}
                    <div class="create-notes rounded border bg-light p-3 mt-3">
                        <div><i class="ti ti-point me-1"></i>생성하면 상태는 <b>대기</b>로 시작해요.</div>
                        <div><i class="ti ti-point me-1"></i>테이블 수를 입력하면 테이블이 함께 만들어져요.</div>
                        <div class="create-note-one"><i class="ti ti-point me-1"></i>1:1 소개팅은 기본 음료 {{ count(\App\Models\EventDrink::DEFAULT_NAMES) }}종이 함께 등록돼요.</div>
                    </div>

                    <div class="d-grid d-md-flex justify-content-md-end gap-2 mt-3">
                        <a href="{{ route('admin.rotation.event.index') }}" class="btn btn-outline-secondary btn-lg order-2 order-md-1">취소</a>
                        <button type="button" class="btn btn-success btn-lg px-5 btn-create order-1 order-md-2"><i class="ti ti-plus me-1"></i>행사 생성</button>
                    </div>
                @endif
                </form>
            </div>
            </div>
        </div>
    </div>

    {{-- 참여자 목록 (상세일 때만) --}}
    @if ($isEdit)
        @php
            $maleCount   = $event->members->where('gender', 'M')->count();
            $femaleCount = $event->members->where('gender', 'F')->count();
            // 플랫폼별 인원수 (문토/프립)
            $muntoCount = $event->members->where('platform', \App\Models\Member::PLATFORM_MUNTO)->count();
            $fripCount  = $event->members->where('platform', \App\Models\Member::PLATFORM_FRIP)->count();
            // 최고관리자만 참여자 비밀번호를 볼 수 있다.
            $isSuperAdmin = auth('admin')->user()?->hasRole(\App\Enums\Role::SuperAdmin->value) ?? false;

            // 그날의 인기 TOP3 — 받은 투표수(received_votes_count) 기준 순위.
            // 동점은 "공동 순위"로 묶고, 순위가 3 이내인 사람을 모두 노출한다.
            // (예: 1위 3명 동점이면 셋 다 공동 1위, 그다음은 2위) → 공동 때문에 5명을 넘을 수 있음.
            // 조밀 순위(dense ranking): 득표수가 바뀔 때마다 순위를 1씩만 올린다. (1-2-2-3)
            $topByGender = function (string $gender) use ($event) {
                $sorted = $event->members
                    ->where('gender', $gender)
                    ->filter(fn ($m) => ($m->received_votes_count ?? 0) > 0)
                    ->sortByDesc('received_votes_count')
                    ->values();

                $out = [];
                $rank = 0;          // 현재 표시 순위 (동점이면 같은 값 유지)
                $prevVotes = null;  // 직전 득표수
                foreach ($sorted as $m) {
                    $votes = (int) $m->received_votes_count;
                    // 득표수가 바뀌면 순위 +1 (동점이면 같은 순위 유지).
                    if ($votes !== $prevVotes) {
                        $rank++;
                        $prevVotes = $votes;
                    }
                    if ($rank > 3) { break; } // 3위까지만
                    $out[] = ['member' => $m, 'rank' => $rank, 'votes' => $votes];
                }
                return $out;
            };
            $topMale   = $topByGender('M');
            $topFemale = $topByGender('F');
            $rankMedal = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
        @endphp

        {{-- 음료 선택 (1:1 소개팅 전용) — 선택지 설정 + 참가자 선택 현황 --}}
        @if ($event->isOneToOne())
            @php
                $drinkNoneMembers = $event->members->whereNull('event_drink_id');
                $drinkMembersById = $event->members->whereNotNull('event_drink_id')->groupBy('event_drink_id');
                // 음료 전달 현황: 음료를 고른 사람 중 전달 완료/대기 인원
                $drinkChosenCount = $event->members->whereNotNull('event_drink_id')->count();
                $drinkServedCount = $event->members->whereNotNull('event_drink_id')->whereNotNull('drink_served_at')->count();
                // 음료 현황 표기: 성별(남/여 칸) · 번호표 · 기준 테이블만 (닉네임/플랫폼 없음)
                $drinkWho = function ($m) use ($memberTableNos) {
                    $tableNo = $memberTableNos[$m->id] ?? null;

                    return [
                        'id'        => $m->id,
                        'gender'    => $m->gender,
                        'no'        => $m->name,
                        'tableText' => $tableNo !== null ? $tableNo . '번 테이블' : '미배정',
                        // 음료 전달 완료 (관리자 체크)
                        'served'    => $m->drink_served_at !== null,
                        'servedAt'  => $m->drink_served_at?->format('H:i'),
                    ];
                };
                // 번호표 순 (성별 칸은 파샬에서 나눈다)
                $drinkSorted = fn ($members) => $members
                    ->sortBy(fn ($m) => (int) $m->name)
                    ->map($drinkWho)
                    ->values();
            @endphp
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h3 class="card-title mb-0"><i class="ti ti-glass-full text-red me-1"></i> 음료 선택</h3>
                        <div id="drinkSummaryBadges" class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-green-lt">선택 {{ $event->members->count() - $drinkNoneMembers->count() }}명</span>
                            <span class="badge bg-secondary-lt">미선택 {{ $drinkNoneMembers->count() }}명</span>
                            <span class="badge bg-teal text-white"><i class="ti ti-circle-check me-1"></i>전달 {{ $drinkServedCount }}/{{ $drinkChosenCount }}</span>
                            @if ($drinkChosenCount - $drinkServedCount > 0)
                                <span class="badge bg-orange-lt">전달 대기 {{ $drinkChosenCount - $drinkServedCount }}명</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            {{-- 음료 목록 설정 — 접기/펼치기(기본 접힘, 상태 기억) --}}
                            <div class="col-12">
                                <div class="drink-setting border rounded">
                                    <div class="drink-setting-head d-flex align-items-center justify-content-between gap-2 px-3 py-2"
                                         role="button" tabindex="0" data-bs-toggle="collapse" data-bs-target="#drinkSettingCollapse"
                                         aria-expanded="false" aria-controls="drinkSettingCollapse">
                                        <span class="fw-bold"><i class="ti ti-list-details me-1"></i>음료 목록 설정
                                            <span class="badge bg-red-lt ms-1" id="drinkCountBadge">{{ $event->drinks->count() }}개</span>
                                        </span>
                                        <span class="text-muted small d-flex align-items-center gap-1">
                                            <span class="d-none d-sm-inline">탭하여 접기/펼치기</span>
                                            <i class="ti ti-chevron-down drink-setting-chevron"></i>
                                        </span>
                                    </div>
                                    <div class="collapse" id="drinkSettingCollapse">
                                        <div class="px-3 pb-3 pt-1">
                                            <div class="input-group">
                                                <input type="text" id="drinkNameInput" class="form-control" maxlength="50"
                                                       placeholder="음료 이름 입력 후 추가" enterkeyhint="done" autocomplete="off">
                                                <button type="button" class="btn btn-primary" id="drinkAddBtn"><i class="ti ti-plus me-1"></i>추가</button>
                                            </div>
                                            <div class="text-muted mt-1" style="font-size:0.82em;">최대 {{ \App\Models\EventDrink::MAX_PER_EVENT }}개 · 가나다 순으로 보여요</div>
                                            <div id="drinkChips" class="drink-chip-grid mt-2"></div>
                                            <div class="drink-setting-notes mt-2">
                                                <div><i class="ti ti-point me-1"></i>음료 카드의 ✕ 를 누르면 목록에서 빠져요.</div>
                                                <div><i class="ti ti-point me-1"></i>지운 음료를 고른 참가자의 선택과 전달 체크는 해제돼요.</div>
                                                <div><i class="ti ti-point me-1"></i>참가자는 입장등록 때 이 목록에서 하나를 골라요. (등록 후 변경 불가)</div>
                                                <div><i class="ti ti-point me-1"></i>바꾼 내용은 <b>음료 목록 저장</b>을 눌러야 반영돼요.</div>
                                            </div>
                                            {{-- 하단 버튼: 모바일은 위아래, PC 는 같은 폭으로 나란히 --}}
                                            <div class="row g-2 mt-2">
                                                <div class="col-12 col-md-6">
                                                    <button type="button" class="btn btn-outline-primary w-100" id="drinkDefaultBtn"
                                                            title="{{ implode(', ', \App\Models\EventDrink::DEFAULT_NAMES) }}">
                                                        <i class="ti ti-sparkles me-1"></i>기본 음료 추가 ({{ count(\App\Models\EventDrink::DEFAULT_NAMES) }}종)
                                                    </button>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <button type="button" class="btn btn-primary w-100" id="drinkSaveBtn"><i class="ti ti-device-floppy me-1"></i>음료 목록 저장</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- 선택 현황 --}}
                            <div class="col-12" id="drinkStatus">
                                <label class="form-label mb-1">선택 현황</label>
                                <div class="text-muted mb-2" style="font-size:0.85em;"><i class="ti ti-hand-finger me-1"></i>음료를 가져다준 사람을 누르면 전달 완료로 체크돼요. 다시 누르면 해제돼요.</div>
                                <div class="row g-2">
                                @forelse ($event->drinks as $drink)
                                    <div class="col-12 col-xl-6">
                                    <div class="border rounded p-2 h-100">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold">{{ $drink->name }}</span>
                                            @php
                                                $drinkPeople   = $drinkMembersById->get($drink->id, collect());
                                                $drinkServedOf = $drinkPeople->whereNotNull('drink_served_at')->count();
                                            @endphp
                                            <span class="d-flex gap-1">
                                                <span class="badge bg-red-lt">{{ $drink->members_count }}명</span>
                                                @if ($drink->members_count > 0)
                                                    <span class="badge {{ $drinkServedOf === $drinkPeople->count() ? 'bg-teal text-white' : 'bg-orange-lt' }}">전달 {{ $drinkServedOf }}/{{ $drinkPeople->count() }}</span>
                                                @endif
                                            </span>
                                        </div>
                                        @if ($drinkMembersById->has($drink->id))
                                            <div class="mt-2">
                                                @include('admin.rotation._drinkPeople', ['people' => $drinkSorted($drinkMembersById[$drink->id]), 'servable' => true])
                                            </div>
                                        @endif
                                    </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted py-3" style="font-size:0.9em;">등록된 음료가 없어요. 위의 <b>음료 목록 설정</b>에서 음료를 추가해 주세요.</div>
                                @endforelse
                                </div>
                                @if ($drinkNoneMembers->isNotEmpty() && $event->drinks->isNotEmpty())
                                    <div class="border rounded p-2 bg-light mt-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-muted">미선택</span>
                                            <span class="badge bg-secondary-lt">{{ $drinkNoneMembers->count() }}명</span>
                                        </div>
                                        <div class="mt-2">
                                            @include('admin.rotation._drinkPeople', ['people' => $drinkSorted($drinkNoneMembers)])
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- 그날의 인기 TOP3 (받은 투표수 기준, 남/여 각각 · 동점은 공동 순위) --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="ti ti-trophy text-yellow me-1"></i> 그날의 인기 TOP3</h3>
                    <span class="text-muted ms-2 fs-5">— 받은 투표(지목) 수 기준 · 동점은 공동 순위</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- 남성 TOP3 --}}
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-blue-lt me-2">남성</span>
                                <span class="text-muted">인기 TOP3</span>
                            </div>
                            @forelse ($topMale as $row)
                                <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="font-size:1.15em; min-width:2.4em;">
                                            {{ $rankMedal[$row['rank']] ?? ($row['rank'] . '위') }}
                                        </span>
                                        <span class="fw-bold">{{ $memberLabels[$row['member']->id] ?? ($row['member']->name . '호') }}</span>
                                        @if ($row['member']->job)<span class="text-muted" style="font-size:0.85em;">{{ $row['member']->job }}</span>@endif
                                    </div>
                                    <span class="badge bg-pink-lt">{{ $row['votes'] }}표</span>
                                </div>
                            @empty
                                <div class="text-muted text-center py-3" style="font-size:0.9em;">아직 받은 투표가 없어요.</div>
                            @endforelse
                        </div>
                        {{-- 여성 TOP3 --}}
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-pink-lt me-2">여성</span>
                                <span class="text-muted">인기 TOP3</span>
                            </div>
                            @forelse ($topFemale as $row)
                                <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="font-size:1.15em; min-width:2.4em;">
                                            {{ $rankMedal[$row['rank']] ?? ($row['rank'] . '위') }}
                                        </span>
                                        <span class="fw-bold">{{ $memberLabels[$row['member']->id] ?? ($row['member']->name . '호') }}</span>
                                        @if ($row['member']->job)<span class="text-muted" style="font-size:0.85em;">{{ $row['member']->job }}</span>@endif
                                    </div>
                                    <span class="badge bg-pink-lt">{{ $row['votes'] }}표</span>
                                </div>
                            @empty
                                <div class="text-muted text-center py-3" style="font-size:0.9em;">아직 받은 투표가 없어요.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h3 class="card-title mb-0">참여자 <span class="text-secondary fs-5 fw-normal">· 번호표 순</span></h3>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-secondary-lt">총 {{ $event->members->count() }}명</span>
                        <span class="badge bg-blue-lt">남 {{ $maleCount }}명</span>
                        <span class="badge bg-pink-lt">여 {{ $femaleCount }}명</span>
                        <span class="badge bg-orange-lt">문토 {{ $muntoCount }}명</span>
                        <span class="badge bg-cyan-lt">프립 {{ $fripCount }}명</span>
                    </div>
                </div>
                <div class="card-body py-2">
                    @if ($event->members->isEmpty())
                        <div class="text-center text-muted py-4">참여자가 없습니다.</div>
                    @else
                        {{-- 성별 보기 탭: 모바일은 카드 폭 전체를 3등분, 데스크톱은 적당한 폭으로 크게 --}}
                        <div class="btn-group w-100 mt-2 mb-3 member-gender-tabs" role="group" aria-label="성별 보기">
                            <input type="radio" class="btn-check" name="memberGenderFilter" id="mgAll" value="" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary" for="mgAll"><i class="ti ti-users me-1"></i>전체 {{ $event->members->count() }}</label>
                            <input type="radio" class="btn-check" name="memberGenderFilter" id="mgM" value="M" autocomplete="off">
                            <label class="btn btn-outline-blue" for="mgM">남 {{ $maleCount }}</label>
                            <input type="radio" class="btn-check" name="memberGenderFilter" id="mgF" value="F" autocomplete="off">
                            <label class="btn btn-outline-pink" for="mgF">여 {{ $femaleCount }}</label>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table members-table">
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
                                        <tr data-member-row data-gender="{{ $member->gender }}">
                                            <td data-label="No" class="d-none d-md-table-cell member-no">{{ $i + 1 }}</td>
                                            <td class="m-headcell">
                                                @php
                                                    $memberData = $member->toArray();
                                                    if ($isSuperAdmin) {
                                                        $memberData['password'] = $member->password;
                                                    }
                                                    $memberData['age'] = $member->birth_year ? (now()->year - $member->birth_year + 1) : null;
                                                    // 관리자 표기 라벨(`닉네임(플랫폼)_N호`) — 모달 제목/상세에서 그대로 쓴다.
                                                    $memberLabel = $memberLabels[$member->id] ?? ($member->name . '호');
                                                    $memberData['label'] = $memberLabel;
                                                @endphp
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold">{{ $memberLabel }}</span>
                                                    @if ($member->gender === 'F')
                                                        <span class="badge bg-pink-lt">여</span>
                                                    @else
                                                        <span class="badge bg-blue-lt">남</span>
                                                    @endif
                                                </div>
                                                {{-- 상세보기 / 만남 이력: 모바일에선 한 줄에 같은 폭으로 나란히 --}}
                                                <div class="member-action-btns d-flex gap-2 mt-1">
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-azure py-1 px-3 btn-member-detail"
                                                            data-member='@json($memberData)'>
                                                        상세보기
                                                    </button>
                                                    @if ($isSuperAdmin && $member->phone)
                                                        <a href="{{ route('admin.rotation.history.index', ['phone' => $member->phone]) }}"
                                                           target="_blank" rel="noopener"
                                                           class="btn btn-sm btn-outline-teal py-1 px-3">
                                                            만남 이력
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="fw-bold" style="font-size: 0.85em;">
                                                    {{ $member->phone ?? '-' }}
                                                </div>
                                                @php
                                                    $platformLabels = [
                                                        \App\Models\Member::PLATFORM_MUNTO => '문토',
                                                        \App\Models\Member::PLATFORM_FRIP  => '프립',
                                                    ];
                                                    $platformBadgeCls = [
                                                        \App\Models\Member::PLATFORM_MUNTO => 'bg-orange-lt',
                                                        \App\Models\Member::PLATFORM_FRIP  => 'bg-cyan-lt',
                                                    ];
                                                @endphp
                                                <div style="font-size: 0.85em;">
                                                    <span class="badge {{ $platformBadgeCls[$member->platform] ?? 'bg-secondary-lt' }}">
                                                        {{ $platformLabels[$member->platform] ?? $member->platform }}
                                                    </span>
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
                                            <td class="text-center" data-label="방문 횟수">
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
                                            <td data-label="직업/키/MBTI">
                                                <div>{{ $member->job ?? '-' }}</div>
                                                <div class="text-muted" style="font-size: 0.85em;">
                                                    {{ $member->height ? $member->height . 'cm' : '-' }}
                                                    @if ($member->mbti)
                                                        · {{ $member->mbti }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td data-label="지역/흡연">
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
                                                {{-- 생활 습관 (주량 / 음주 횟수 / 애완동물 / 친구 약속) --}}
                                                <div class="text-muted" style="font-size: 0.8em;">
                                                    @if ($member->drink_capacity || $member->drink_frequency)
                                                        🍶 {{ $member->drink_capacity ?? '-' }} · 🍻 {{ $member->drink_frequency ?? '-' }}
                                                    @endif
                                                </div>
                                                <div class="text-muted" style="font-size: 0.8em;">
                                                    @if (! is_null($member->has_pet))
                                                        🐾 {{ $member->has_pet ? '애완동물 있음' : '애완동물 없음' }}
                                                    @endif
                                                    @if ($member->friend_meet_frequency)
                                                        · 👫 {{ $member->friend_meet_frequency }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center" data-label="투표(보낸/받은)">
                                                <div class="d-flex flex-column align-items-center gap-1">
                                                    {{-- 보낸 투표: 이 회원이 선택한 상대 (VotedModal) --}}
                                                    @if ($member->cast_votes_count > 0)
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-blue btn-voted"
                                                                data-member-id="{{ $member->id }}"
                                                                data-member-label="{{ $memberLabel }}">
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
                                                                data-member-label="{{ $memberLabel }}">
                                                            받은 {{ $member->received_votes_count }}표
                                                        </button>
                                                    @else
                                                        <span class="text-muted" style="font-size:0.85em;">받은 0표</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center" data-label="보낸 메시지">
                                                @if ($member->sent_messages_count > 0)
                                                    <button type="button"
                                                            class="btn btn-outline-primary btn-sent-messages"
                                                            data-member-id="{{ $member->id }}"
                                                            data-member-label="{{ $memberLabel }}">
                                                        {{ $member->sent_messages_count }}건
                                                    </button>
                                                @else
                                                    <span class="text-muted">0건</span>
                                                @endif
                                            </td>
                                            <td class="text-center" data-label="보낸 요청수">
                                                @if ($member->admin_requests_count > 0)
                                                    <button type="button"
                                                            class="btn btn-outline-yellow btn-sent-requests"
                                                            data-member-id="{{ $member->id }}"
                                                            data-member-label="{{ $memberLabel }}">
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
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
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
                    var container = instance && instance.calendarContainer;
                    if(!container){ return; } // 간혹 container 가 준비 안 된 경우 방어 (TypeError 방지)
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
        // ===== 생성: 리뷰 인증 게이트 토글 라벨 동기화 =====
        $('#reviewGateToggle').on('change', function(){
            $('#reviewGateLabel').text(this.checked ? '켜짐 (결과 전 리뷰 인증 요구)' : '꺼짐');
        });

        // ===== 생성: 타입에 따라 1:1 안내 표시 =====
        function createType(){ return $('#eventForm [name=type]:checked').val(); }
        function syncCreateType(){
            $('.create-note-one').toggle(createType() === '{{ \App\Models\EventRound::TYPE_ONE }}');
        }
        $('#eventForm [name=type]').on('change', syncCreateType);
        syncCreateType();

        // ===== 생성: 테이블 수 -/+ (0~100) =====
        function clampTableCount(v){
            v = parseInt(v, 10);
            if(isNaN(v) || v < 0){ v = 0; }
            if(v > 100){ v = 100; }
            return v;
        }
        $('.create-stepper [data-step]').on('click', function(){
            var $in = $('#createTableCount');
            $in.val(clampTableCount((parseInt($in.val(), 10) || 0) + parseInt($(this).data('step'), 10)));
        });
        $('#createTableCount').on('change blur', function(){ $(this).val(clampTableCount($(this).val())); });

        // ===== 생성 =====
        $('.btn-create').click(async function(){
            let $f = $('#eventForm');
            let title = ($f.find('[name=title]').val() || '').trim();
            let time  = $f.find('[name=event_time]').val();
            if(!title){ await salert({text: '행사명을 입력해주세요.', cancel: false}); $('#createTitle').focus(); return false; }
            if(!time){ await salert({text: '일시를 선택해주세요.', cancel: false}); return false; }

            let tableCount = clampTableCount($('#createTableCount').val());
            let typeLabel  = $f.find('[name=type]:checked').next('label').text().trim();
            let esc = function(v){ return $('<div>').text(v).html(); };
            let html = '<div class="text-start">'
                + '<div><b>' + esc(title) + '</b></div>'
                + '<div>' + esc(typeLabel) + ' · ' + esc(time) + '</div>'
                + '<div>테이블 ' + tableCount + '개</div>'
                + '</div><div class="mt-2">이대로 생성할까요?</div>';

            if(await salert({html: html, icon: 'question'})){
                let $btn = $(this).prop('disabled', true);
                $.ajax({
                    url: "{{ route('admin.rotation.event.store') }}",
                    type: 'POST',
                    data: {
                        title: title,
                        type: createType(),
                        event_time: time,
                        table_count: tableCount,
                        review_gate_enabled: $('#reviewGateToggle').is(':checked') ? 1 : 0
                    },
                    success: function(res){
                        alert(res?.msg);
                        if(res?.status === 200){ location.href = "{{ route('admin.rotation.event.index') }}"; }
                    },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
                }).always(function(){ $btn.prop('disabled', false); });
            }
        });
        @else
        // ===== 상세: 행사 정보 수정 영역 접힘 상태 기억 (새로고침 후에도 유지) =====
        (function(){
            var KEY = 'rotable.eventDetail.infoCollapsed';
            var el = document.getElementById('eventInfoCollapse');
            if(!el){ return; }
            var collapsed = false;
            try { collapsed = localStorage.getItem(KEY) === '1'; } catch(e) { /* 저장소 사용 불가 시 기본(펼침) */ }
            if(collapsed){
                el.classList.remove('show');
                $('[data-bs-target="#eventInfoCollapse"]').attr('aria-expanded', 'false');
            }
            function save(v){ try { localStorage.setItem(KEY, v ? '1' : '0'); } catch(e) {} }
            el.addEventListener('hidden.bs.collapse', function(e){ if(e.target === el){ save(true); } });
            el.addEventListener('shown.bs.collapse', function(e){ if(e.target === el){ save(false); } });
        })();

        // ===== 상세: 입장등록 QR 모달 =====
        $('#goRegisterQrBtn').on('click', function(){
            RegisterQrModal.open({{ $event->id }}, @json($event->label));
        });

        // ===== 상세: 참여자 성별 보기 (전체/남/여) — 표시 중인 행만 No 를 다시 매긴다. =====
        $('input[name="memberGenderFilter"]').on('change', function(){
            var g = $('input[name="memberGenderFilter"]:checked').val();
            var n = 0;
            $('.members-table tr[data-member-row]').each(function(){
                var show = !g || $(this).data('gender') === g;
                $(this).toggle(show);
                if(show){ $(this).find('.member-no').text(++n); }
            });
        });

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
            var who = label || '회원';
            VotersModal.open({{ $event->id }}, memberId, who + ' 님을 선택한 사람');
        });

        // ===== 상세: 보낸 투표(이 회원이 선택한 상대) 모달 =====
        $(document).on('click', '.btn-voted', function(){
            var memberId = $(this).data('member-id');
            var label = $(this).data('member-label') || '';
            var who = label || '회원';
            VotedModal.open({{ $event->id }}, memberId, who + ' 님이 선택/매칭한 상대');
        });

        // ===== 상세: 보낸 메시지 모달 (특정 회원이 보낸 것만) =====
        $(document).on('click', '.btn-sent-messages', function(){
            var memberId = $(this).data('member-id');
            var label = $(this).data('member-label') || '';
            var who = label || '회원';
            MessagesModal.open({{ $event->id }}, who + ' 님이 보낸 메시지', { senderId: memberId });
        });

        // ===== 상세: 보낸 요청 모달 (특정 회원이 보낸 것만) =====
        $(document).on('click', '.btn-sent-requests', function(){
            var memberId = $(this).data('member-id');
            var label = $(this).data('member-label') || '';
            var who = label || '회원';
            RequestsModal.open({{ $event->id }}, who + ' 님이 보낸 요청', { memberId: memberId });
        });

        // ===== 상세: 회원 상세정보 모달 =====
        $(document).on('click', '.btn-member-detail', function(){
            var member = $(this).data('member');
            MemberDetailModal.open(member);
        });

        // ===== 상세: 음료 목록 편집/저장 (1:1 소개팅) — 입력 후 추가, 칩을 눌러 삭제 =====
        var drinkList = @json($event->drinks->pluck('name')->values());
        var drinkMax  = {{ \App\Models\EventDrink::MAX_PER_EVENT }};

        // 음료 이름에서 어울리는 이모지 (입장등록 화면과 같은 규칙)
        function drinkEmoji(name){
            if(/아메리카노|커피|라떼/.test(name)){ return /ICE/i.test(name) ? '🧊' : '☕'; }
            if(name.indexOf('물') !== -1){ return /HOT/i.test(name) ? '♨️' : '💧'; }
            if(/카모마일|캐모마일|케모마일|차/.test(name)){ return '🌼'; }
            return '🥤';
        }

        // 음료 목록은 가나다 순으로 보여준다. (서버도 이름순으로 내려준다)
        function sortDrinkList(){
            drinkList.sort(function(a, b){ return a.localeCompare(b, 'ko'); });
        }

        function renderDrinkChips(){
            sortDrinkList();
            var $box = $('#drinkChips').empty();
            $('#drinkCountBadge').text(drinkList.length + '개');
            if(!drinkList.length){
                $box.append('<div class="drink-chip-empty">추가된 음료가 없어요.</div>');
                return;
            }
            drinkList.forEach(function(name, i){
                var $card = $('<div class="drink-card"></div>');
                $card.append($('<span class="drink-card-emoji" aria-hidden="true"></span>').text(drinkEmoji(name)));
                $card.append($('<span class="drink-card-name"></span>').text(name));
                $card.append(
                    $('<button type="button" class="drink-card-del drink-chip"><i class="ti ti-x"></i></button>')
                        .attr('data-index', i)
                        .attr('aria-label', name + ' 삭제')
                );
                $box.append($card);
            });
        }

        // 음료 목록 설정 접힘 상태 기억 (기본: 접힘)
        (function(){
            var KEY = 'rotable.eventDetail.drinkSettingOpen';
            var el = document.getElementById('drinkSettingCollapse');
            if(!el){ return; }
            var open = false;
            try { open = localStorage.getItem(KEY) === '1'; } catch(e) {}
            if(open){
                el.classList.add('show');
                $('[data-bs-target="#drinkSettingCollapse"]').attr('aria-expanded', 'true');
            }
            function save(v){ try { localStorage.setItem(KEY, v ? '1' : '0'); } catch(e) {} }
            el.addEventListener('shown.bs.collapse', function(e){ if(e.target === el){ save(true); } });
            el.addEventListener('hidden.bs.collapse', function(e){ if(e.target === el){ save(false); } });
        })();

        async function addDrink(){
            var $input = $('#drinkNameInput');
            var name = $input.val().trim();
            if(!name){ $input.focus(); return; }
            if(drinkList.indexOf(name) !== -1){
                await salert({text: '이미 추가된 음료입니다.', cancel: false});
                return;
            }
            if(drinkList.length >= drinkMax){
                await salert({text: '음료는 최대 ' + drinkMax + '개까지 등록할 수 있습니다.', cancel: false});
                return;
            }
            drinkList.push(name);
            $input.val('').focus();
            renderDrinkChips();
        }

        $('#drinkAddBtn').on('click', addDrink);
        $('#drinkNameInput').on('keydown', function(e){
            if(e.key === 'Enter' && !e.originalEvent?.isComposing){ e.preventDefault(); addDrink(); }
        });
        $(document).on('click', '.drink-chip', function(){
            drinkList.splice($(this).data('index'), 1);
            renderDrinkChips();
        });
        renderDrinkChips();

        // ===== 음료 선택 현황 실시간 갱신 =====
        // 참가자가 입장등록하며 음료를 고르면(event-drinks.{id}) 이 페이지를 다시 받아 현황 영역만 교체한다.
        // (목록 편집 중인 입력값은 건드리지 않는다.)
        (function(){
            var cfg = {!! json_encode([
                'key'    => config('broadcasting.connections.reverb.key'),
                'host'   => env('VITE_REVERB_HOST', env('REVERB_HOST')),
                'port'   => (int) env('VITE_REVERB_PORT', env('REVERB_PORT', 443)),
                'scheme' => env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https')),
            ], JSON_HEX_TAG | JSON_HEX_AMP) !!};
            // 전달 완료 체크 후 즉시 갱신에도 쓰므로 실시간 연결 여부와 관계없이 먼저 노출한다. (함수 선언은 호이스팅됨)
            if($('#drinkStatus').length){ window.refreshDrinkStatus = refreshDrinkStatus; }
            if(typeof Pusher === 'undefined' || !cfg.key || !cfg.host || !$('#drinkStatus').length){ return; }

            var timer = null;
            function refreshDrinkStatus(){
                $.get(window.location.href, function(html){
                    var $doc = $('<div>').append($.parseHTML(html));
                    ['#drinkStatus', '#drinkSummaryBadges'].forEach(function(sel){
                        var $new = $doc.find(sel);
                        if($new.length){ $(sel).html($new.html()); }
                    });
                });
            }

            var pusher = new Pusher(cfg.key, {
                wsHost: cfg.host,
                wsPort: cfg.port || 80,
                wssPort: cfg.port || 443,
                forceTLS: cfg.scheme === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                cluster: '',
            });
            var drinkChannel = pusher.subscribe('event-drinks.{{ $event->id }}');
            var scheduleRefresh = function(){
                clearTimeout(timer);
                timer = setTimeout(refreshDrinkStatus, 300);
            };
            // 참가자 음료 선택 + 관리자 전달 완료 체크(다른 관리자 화면 포함)
            drinkChannel.bind('drink.chosen', scheduleRefresh);
            drinkChannel.bind('drink.served', scheduleRefresh);
        })();

        // ===== 음료 전달 완료 체크/해제 =====
        // 현황 영역은 실시간 갱신으로 통째로 바뀌므로 document 에 위임한다.
        var drinkServedUrlTpl = "{{ route('admin.rotation.event.members.drinkServed', ['id' => $event->id, 'memberId' => '__MID__']) }}";
        $(document).on('click', '.drink-serve-btn', function(){
            var $btn = $(this);
            if($btn.prop('disabled')){ return; }
            var next = String($btn.data('served')) !== '1';
            $btn.prop('disabled', true);
            $.ajax({
                url: drinkServedUrlTpl.replace('__MID__', $btn.data('member-id')),
                type: 'PATCH',
                data: { served: next ? 1 : 0 },
                success: function(res){
                    if(res?.status !== 200){ alert(res?.msg ?? '처리하지 못했습니다.'); return; }
                    // 실시간 연결이 없어도 바로 반영되도록 현황을 다시 불러온다. (연결돼 있으면 신호로도 한 번 더 갱신)
                    if(typeof window.refreshDrinkStatus === 'function'){ window.refreshDrinkStatus(); } else { location.reload(); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); },
            }).always(function(){ $btn.prop('disabled', false); });
        });

        // 음료 목록을 서버에 저장한다. (저장 후 새로고침해 현황까지 서버 값으로 다시 그린다)
        function saveDrinkList(drinks){
            $.ajax({
                url: "{{ route('admin.rotation.event.drinks.save', $event->id) }}",
                type: 'PUT',
                data: { drinks: drinks },
                success: function(res){
                    alert(res?.msg);
                    if(res?.status === 200){ location.reload(); }
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
            });
        }

        $('#drinkSaveBtn').on('click', async function(){
            // 입력창에 적어두고 추가를 안 누른 값도 함께 담는다.
            var pending = $('#drinkNameInput').val().trim();
            if(pending && drinkList.indexOf(pending) === -1 && drinkList.length < drinkMax){ drinkList.push(pending); }

            if(await salert({text: '음료 목록을 저장하시겠습니까? 목록에서 빠진 음료의 선택은 해제됩니다.'})){
                saveDrinkList(drinkList.slice());
            }
        });

        // 기본 음료(EventDrink::DEFAULT_NAMES) 한꺼번에 추가 + 저장.
        //  - 이미 있는 음료는 건너뛰고, 기존 목록 뒤에 순서대로 붙인다. (기존 음료·참가자 선택은 그대로)
        //  - 최대 개수를 넘으면 들어갈 수 있는 만큼만 넣는다.
        var DEFAULT_DRINKS = @json(\App\Models\EventDrink::DEFAULT_NAMES);
        $('#drinkDefaultBtn').on('click', async function(){
            var missing = DEFAULT_DRINKS.filter(function(n){ return drinkList.indexOf(n) === -1; });
            if(!missing.length){
                await salert({text: '기본 음료가 이미 모두 목록에 있어요.', cancel: false});
                return;
            }
            var room = drinkMax - drinkList.length;
            if(room <= 0){
                await salert({text: '음료는 최대 ' + drinkMax + '개까지 등록할 수 있어요. 먼저 음료를 지워 주세요.', cancel: false});
                return;
            }
            var adding = missing.slice(0, room);
            var esc = function(v){ return $('<div>').text(v).html(); };
            var html = '<div>기본 음료 <b>' + adding.length + '개</b>를 추가하고 저장할까요?</div>'
                + '<div class="mt-2">' + adding.map(esc).join('<br>') + '</div>'
                + (adding.length < missing.length ? '<div class="mt-2 text-muted">최대 개수 때문에 ' + (missing.length - adding.length) + '개는 넣지 못해요.</div>' : '');
            if(!(await salert({html: html, icon: 'question'}))){ return; }

            saveDrinkList(drinkList.concat(adding));
        });

        // ===== 상세: 리뷰 인증 게이트 토글 라벨 동기화 =====
        $('#reviewGateToggle').on('change', function(){
            $('#reviewGateLabel').text(this.checked ? '켜짐 (결과 전 리뷰 인증 요구)' : '꺼짐');
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
                        event_time: time,
                        review_gate_enabled: $('#reviewGateToggle').is(':checked') ? 1 : 0
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
            layout: "fitColumns",
            responsiveLayout: "collapse",
            responsiveLayoutCollapseStartOpen: false,
            initialSort:[{column: "table_no", dir: "asc"}],
            columns: [
                { formatter: "rowSelection", titleFormatter: "rowSelection", headerSort: false, width: 48, responsive: 0 },
                { title: "No", field: "id", width: 80, responsive: 4 },
                { title: "테이블 번호", field: "table_no", hozAlign: "center", responsive: 0 },
                { title: "등록일", field: "created_at", width: 200, responsive: 3,
                    formatter: function(cell){ return cell.getValue() ? new Date(cell.getValue()).toLocaleString('ko-KR') : '-'; }
                },
                { title: "관리", width: 110, headerSort: false, responsive: 1,
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
    @include('admin.rotation._memberDetailModal', ['event' => $event, 'isSuperAdmin' => $isSuperAdmin ?? false])
    @include('admin.rotation._registerQrModal')
@endif
@endsection
