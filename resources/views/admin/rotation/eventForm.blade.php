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
    .create-hint > div, .create-notes > div{ display:block; word-break:keep-all; }
    .create-hint{ font-size:.85rem; line-height:1.6; color:#495057; }
    .create-notes{ font-size:.9rem; line-height:1.75; color:#343a40; }
    #eventTimePicker[readonly]{ background-color:#fff; cursor:pointer; }

    /* 행사 생성/수정 폼 레이아웃: 섹션 제목 · 설정 행 · 하단 버튼 */
    .ef-section-title{ display:flex; align-items:center; gap:.35rem; margin-bottom:.75rem; font-size:.85rem; font-weight:800; color:#868e96; }
    .ef-options{ display:flex; flex-direction:column; gap:.6rem; }
    .ef-option{ display:flex; align-items:center; gap:1rem; margin:0; padding:.85rem 1rem; border:1px solid #e6e8eb; border-radius:12px; background:#fff; cursor:pointer; }
    .ef-option:hover{ background:#f8fafc; }
    .ef-option-text{ flex:1 1 auto; min-width:0; display:flex; flex-direction:column; gap:.2rem; }
    .ef-option-title{ font-weight:700; color:#1f2937; }
    .ef-option-desc{ font-size:.85rem; line-height:1.5; color:#6b7280; word-break:keep-all; }
    .ef-option-ctrl{ flex:0 0 auto; display:flex; align-items:center; gap:.5rem; margin:0; min-height:0; }
    .ef-option-ctrl .form-check-input{ cursor:pointer; }
    .ef-option-state{ min-width:2.5em; font-size:.85rem; font-weight:700; color:#868e96; }
    .ef-option-ctrl .form-check-input:checked + .ef-option-state{ color:#2fb344; }
    .ef-footer{ margin-top:1.5rem; padding-top:1rem; border-top:1px solid #eef0f3; }
    /* 참가비 · 수수료 (선택) */
    .ef-fee-section{ margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid #eef0f3; }
    .ef-fees{ display:flex; flex-direction:column; gap:.6rem; }
    .ef-fee-block{ display:grid; grid-template-columns:1fr 1fr; gap:.5rem .6rem; padding:.7rem .75rem; border:1px solid #e6e8eb; border-radius:12px; border-left:4px solid #adb5bd; }
    .ef-fee-block.is-munto{ border-left-color:#f76707; }
    .ef-fee-block.is-frip{ border-left-color:#15aabf; }
    .ef-fee-platform{ grid-column:1 / -1; font-weight:800; }
    .ef-fee-cell{ min-width:0; }
    .ef-fee-cell .form-control{ min-height:44px; }
    .ef-fee-label{ display:block; font-size:.78rem; color:#868e96; font-weight:700; margin-bottom:.2rem; }
    .ef-fee-eb{ font-size:.78rem; color:#ae3ec9; font-weight:700; margin-top:.2rem; min-height:1.1em; }
    @media (min-width: 768px){
        .ef-fee-block{ grid-template-columns:72px repeat(4, minmax(0, 1fr)); align-items:start; }
        .ef-fee-platform{ grid-column:auto; padding-top:1.65rem; }
    }
    @media (min-width: 992px){
        .ef-side{ border-left:1px solid #eef0f3; padding-left:1.75rem; }
    }

    /* 행사 상세 바로가기 버튼(.eventform-btn-grid) 스타일은 public/css/custom.css 공통 규칙 (로테이션 진행과 공유) */

    /* 참여자 성별 탭: 터치하기 편한 높이, 데스크톱에선 폭 제한 */
    .member-gender-tabs .btn{ min-height:44px; font-weight:700; font-size:1rem; }
    @media (min-width: 768px){ .member-gender-tabs{ max-width:420px; } }
    /* 성별 탭 + 정렬 선택: 모바일은 위아래 전체 폭, 데스크톱은 한 줄 */
    .member-view-bar{ display:flex; flex-direction:column; gap:.5rem; }
    .member-view-bar .member-gender-tabs{ width:100%; }
    .member-sort-select{ min-height:44px; font-weight:600; }
    @media (min-width: 768px){
        .member-view-bar{ flex-direction:row; align-items:center; }
        .member-view-bar .member-gender-tabs{ flex:0 1 420px; }
        .member-sort-select{ flex:0 0 220px; width:220px; }
    }

    /* 참여자 카드: ① 번호표·라벨·플랫폼·방문  ② 항목명:값  ③ 활동 숫자(누르면 목록)  ④ 버튼 */
    .pm-grid{ display:grid; grid-template-columns:minmax(0, 1fr); gap:.75rem; }
    @media (min-width: 768px){ .pm-grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1200px){ .pm-grid{ grid-template-columns:repeat(3, minmax(0, 1fr)); } }
    /* 남/여 두 칸일 때는 각 칸이 좁으므로 카드를 한 줄에 하나씩. (한 성별만 볼 때는 위의 반응형 열 수를 쓴다) */
    .pm-col-head{ font-weight:800; font-size:1.05rem; padding:.45rem .75rem; border-radius:10px; margin-bottom:.6rem; }
    /* 남/여 칸 머리를 누르면 그 칸의 카드를 접고 편다. (접은 상태는 브라우저에 기억) */
    .pm-col-head[data-bs-toggle="collapse"]{ display:flex; align-items:center; gap:.35rem; cursor:pointer; user-select:none; }
    .pm-col-head[data-bs-toggle="collapse"] .ck-chevron{ margin-left:auto; }
    @media (min-width: 992px){ .pm-cols:not(.is-solo) .pm-grid{ grid-template-columns:minmax(0, 1fr); } }
    .pm-card{ display:flex; flex-direction:column; border:1px solid #e6e8eb; border-radius:14px; background:#fff; padding:.8rem .9rem; box-shadow:0 1px 3px rgba(0,0,0,.05); border-top:4px solid #206bc4; }
    .pm-card.is-f{ border-top-color:#d6336c; }
    .pm-head{ display:flex; align-items:flex-start; gap:.65rem; }
    .pm-ticket{ flex:0 0 auto; width:52px; height:52px; border-radius:12px; color:#fff; background:#206bc4; display:flex; flex-direction:column; align-items:center; justify-content:center; line-height:1.05; }
    .pm-card.is-f .pm-ticket{ background:#d6336c; }
    .pm-ticket small{ font-size:.7rem; font-weight:700; opacity:.85; }
    .pm-ticket b{ font-size:1.25rem; font-weight:800; }
    .pm-who{ flex:1 1 auto; min-width:0; }
    .pm-name{ font-weight:800; font-size:1rem; line-height:1.3; overflow-wrap:anywhere; }
    .pm-info{ margin:.65rem 0 0; padding:.5rem .65rem; background:#f8f9fa; border-radius:10px; }
    .pm-info > div{ display:flex; gap:.5rem; font-size:.86rem; line-height:1.6; }
    .pm-info dt{ flex:0 0 52px; color:#868e96; font-weight:700; margin:0; }
    .pm-info dd{ flex:1 1 auto; margin:0; color:#343a40; overflow-wrap:anywhere; }
    .pm-stats{ display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:.35rem; margin-top:.6rem; }
    .pm-stat{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.05rem; min-height:52px; padding:.3rem .2rem;
        border:1px solid #d6e4f5; border-radius:10px; background:#f3f8ff; color:#1d4ed8; cursor:pointer; }
    .pm-stat.is-pink{ border-color:#f6cfdc; background:#fff3f7; color:#c2255c; }
    .pm-stat.is-yellow{ border-color:#f5e3a3; background:#fffbea; color:#9a6b00; }
    .pm-stat:disabled{ border-color:#eceef1; background:#fafafa; color:#adb5bd; cursor:default; }
    .pm-stat-label{ font-size:.7rem; font-weight:700; white-space:nowrap; }
    .pm-stat-val{ font-size:1.15rem; font-weight:800; line-height:1.1; }
    .pm-actions{ display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.6rem; }
    .pm-actions .btn{ flex:1 1 calc(50% - .4rem); min-height:40px; white-space:normal; }



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
        @include('admin.rotation._eventResultButtons', ['eventId' => $event->id, 'eventLabel' => $event->label, 'showList' => true])
    @endif

    {{-- 행사 정보 (생성/상세 공용) --}}
    <div class="col-12">
        <div class="card">
            @if ($isEdit)
                {{-- 접기/펼치기(상태 기억) --}}
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2" role="button" tabindex="0"
                     data-bs-toggle="collapse" data-bs-target="#eventInfoCollapse" aria-expanded="true" aria-controls="eventInfoCollapse" style="cursor:pointer;">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-1">행사 정보 수정 <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i></h3>
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
                @php
                    $isOneType = $isEdit ? $event->type === \App\Models\EventRound::TYPE_ONE : array_key_first($types) === \App\Models\EventRound::TYPE_ONE;
                    $reviewOn  = $isEdit ? (bool) $event->review_gate_enabled : true;
                    $drinkOn   = $isEdit ? (bool) $event->drink_enabled : false;
                    $idPrefix  = $isEdit ? 'edit' : 'create';
                @endphp
                {{-- 모바일: 기본 정보 → 옵션 순으로 한 줄씩. PC(lg+): 왼쪽 기본 정보 / 오른쪽 옵션 2단 --}}
                @unless ($isEdit)
                    @if (! empty($copySources))
                        {{-- 지난 행사 설정 불러오기: 제목·타입·테이블 수·옵션·참가비/수수료만 채운다. 일시는 새로 고른다. --}}
                        <div class="mb-3 p-3 rounded border bg-light">
                            <label class="form-label mb-1" for="copySource"><i class="ti ti-copy me-1"></i>지난 행사에서 불러오기 <span class="text-muted fw-normal">(선택)</span></label>
                            <select class="form-select" id="copySource">
                                <option value="">선택 안 함</option>
                                @foreach ($copySources as $src)
                                    <option value="{{ $src['id'] }}">{{ $src['label'] }}</option>
                                @endforeach
                            </select>
                            <div class="create-hint mt-1">행사명·타입·테이블 수·옵션·참가비를 가져와요. 일시는 새로 골라 주세요.</div>
                        </div>
                    @endif
                @endunless
                <div class="row g-4">
                    <div class="col-12 col-lg-7">
                        <div class="ef-section-title"><i class="ti ti-info-circle"></i>기본 정보</div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label required" for="{{ $idPrefix }}Title">행사명</label>
                                <input type="text" class="form-control form-control-lg" id="{{ $idPrefix }}Title" name="title"
                                       value="{{ $isEdit ? $event->title : '' }}" placeholder="{{ $isEdit ? '예: 로테이블 와인파티' : '예: 로테이블 1:1 소개팅' }}"
                                       maxlength="255" autocomplete="off" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label required d-block">타입</label>
                                <div class="btn-group w-100 create-type-group" role="group" aria-label="행사 타입">
                                    @foreach ($types as $value => $label)
                                        <input type="radio" class="btn-check" name="type" id="{{ $idPrefix }}Type{{ ucfirst($value) }}" value="{{ $value }}"
                                               autocomplete="off" {{ ($isEdit ? $event->type === $value : $loop->first) ? 'checked' : '' }}>
                                        <label class="btn btn-outline-{{ $value === \App\Models\EventRound::TYPE_ONE ? 'purple' : 'red' }}" for="{{ $idPrefix }}Type{{ ucfirst($value) }}">
                                            <i class="ti {{ $value === \App\Models\EventRound::TYPE_ONE ? 'ti-users' : 'ti-glass-full' }} me-1"></i>{{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label required" for="eventTimePicker">일시</label>
                                <input type="text" class="form-control form-control-lg" id="eventTimePicker" name="event_time"
                                       value="{{ $isEdit && $event->event_time ? $event->event_time->format('Y-m-d H:i') : '' }}"
                                       placeholder="날짜/시간 선택" readonly required>
                            </div>

                            @if ($isEdit)
                                @php
                                    $editStatusLabel = $statuses[$event->status] ?? $event->status;
                                    $editStatusCls   = ['P' => 'bg-yellow text-white', 'S' => 'bg-green text-white', 'V' => 'bg-pink text-white', 'X' => 'bg-dark text-white'][$event->status] ?? 'bg-secondary text-white';
                                @endphp
                                <div class="col-12 col-md-6">
                                    <label class="form-label d-block">상태</label>
                                    <div class="d-flex align-items-center gap-2 flex-wrap" style="min-height:48px;">
                                        <span class="badge {{ $editStatusCls }} fs-5 px-3 py-2">{{ $editStatusLabel }}</span>
                                        <span class="create-hint">로테이션 진행 화면에서 바뀌어요.</span>
                                    </div>
                                </div>
                            @else
                                <div class="col-12 col-md-6">
                                    <label class="form-label required" for="createTableCount">테이블 수</label>
                                    <div class="input-group create-stepper">
                                        <button type="button" class="btn btn-outline-secondary" data-step="-1" aria-label="테이블 수 줄이기"><i class="ti ti-minus"></i></button>
                                        <input type="number" class="form-control form-control-lg text-center" id="createTableCount" name="table_count"
                                               min="0" max="100" value="0" inputmode="numeric">
                                        <button type="button" class="btn btn-outline-secondary" data-step="1" aria-label="테이블 수 늘리기"><i class="ti ti-plus"></i></button>
                                    </div>
                                    <div class="create-hint mt-1">
                                        <div>테이블을 함께 만들어요. (1개 이상)</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-12 col-lg-5 ef-side">
                        <div class="ef-section-title"><i class="ti ti-adjustments"></i>옵션</div>
                        {{-- 설정 행: 행 아무 곳이나 누르면 스위치가 바뀐다. 왼쪽 설명 / 오른쪽 스위치 --}}
                        <div class="ef-options">
                            <label class="ef-option" for="reviewGateToggle">
                                <span class="ef-option-text">
                                    <span class="ef-option-title"><i class="ti ti-photo-check me-1"></i>리뷰 인증 게이트</span>
                                    <span class="ef-option-desc">켜면 종료 후 참가자가 매칭 결과를 보기 전에 리뷰 이미지를 올려야 해요.</span>
                                </span>
                                <span class="ef-option-ctrl form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="reviewGateToggle" {{ $reviewOn ? 'checked' : '' }}>
                                    <span class="ef-option-state" id="reviewGateLabel">{{ $reviewOn ? '켜짐' : '꺼짐' }}</span>
                                </span>
                            </label>

                            <label class="ef-option drink-toggle-col" for="drinkToggle" @unless ($isOneType) style="display:none;" @endunless>
                                <span class="ef-option-text">
                                    <span class="ef-option-title"><i class="ti ti-cup me-1"></i>음료 선택 <span class="badge bg-purple-lt ms-1">1:1</span></span>
                                    <span class="ef-option-desc">켜면 입장등록에서 음료를 골라요. 끄면 선택 칸이 안 보이고 참가자가 알아서 가져가요.</span>
                                </span>
                                <span class="ef-option-ctrl form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="drinkToggle" {{ $drinkOn ? 'checked' : '' }}>
                                    <span class="ef-option-state" id="drinkLabel">{{ $drinkOn ? '켜짐' : '꺼짐' }}</span>
                                </span>
                            </label>
                        </div>

                        @unless ($isEdit)
                            {{-- 생성 안내: 한 행에 한 문장. 1:1 안내는 1:1을 골랐을 때만 --}}
                            <div class="create-notes rounded border bg-light p-3 mt-3">
                                <div><i class="ti ti-point me-1"></i>생성하면 상태는 <b>대기</b>로 시작해요.</div>
                                <div><i class="ti ti-point me-1"></i>테이블 수를 입력하면 테이블이 함께 만들어져요.</div>
                                <div class="create-note-one"><i class="ti ti-point me-1"></i>1:1 소개팅은 기본 음료 {{ count(\App\Models\EventDrink::DEFAULT_NAMES) }}종이 함께 등록돼요.</div>
                            </div>
                        @endunless
                    </div>
                </div>

                {{-- 참가비 · 수수료 (선택). 페이백·매출 계산에 쓴다. 플랫폼별 한 묶음
                     모바일: 여/남 정가 2칸 + 얼리버드/수수료 2칸 / PC(md+): 플랫폼 · 여 · 남 · 얼리버드 · 수수료 한 줄 --}}
                @php
                    $feeGenders = [\App\Models\Member::GENDER_FEMALE => '여', \App\Models\Member::GENDER_MALE => '남'];
                    // 행사 생성 시 기본 정가 (플랫폼 => 성별 => 원). 수정 화면은 저장된 값을 쓴다.
                    $defaultPrices = [
                        \App\Models\Member::PLATFORM_MUNTO => [\App\Models\Member::GENDER_FEMALE => 20000, \App\Models\Member::GENDER_MALE => 20000],
                        \App\Models\Member::PLATFORM_FRIP  => [\App\Models\Member::GENDER_FEMALE => 12900, \App\Models\Member::GENDER_MALE => 19800],
                    ];
                @endphp
                <div class="ef-fee-section">
                    <div class="ef-section-title"><i class="ti ti-receipt"></i>참가비 · 수수료 <span class="fw-normal">(선택 · 페이백/매출 계산용)</span></div>
                    <div class="ef-fees">
                        @foreach (\App\Models\Member::platformOptions() as $opt)
                            <div class="ef-fee-block is-{{ $opt['id'] }}">
                                <div class="ef-fee-platform">{{ $opt['label'] }}</div>
                                @foreach ($feeGenders as $gender => $gLabel)
                                    @php $price = $isEdit ? $event->basePriceFor($opt['id'], $gender) : ($defaultPrices[$opt['id']][$gender] ?? null); @endphp
                                    <div class="ef-fee-cell">
                                        <label class="ef-fee-label" for="{{ $idPrefix }}Fee-{{ $opt['id'] }}-{{ $gender }}">{{ $gLabel }} 정가 (원)</label>
                                        <input type="text" class="form-control ef-money ef-fee-price" inputmode="numeric" id="{{ $idPrefix }}Fee-{{ $opt['id'] }}-{{ $gender }}"
                                               data-platform="{{ $opt['id'] }}" data-gender="{{ $gender }}"
                                               value="{{ $price !== null ? number_format($price) : '' }}" placeholder="미설정">
                                        <div class="ef-fee-eb" data-eb-for="{{ $opt['id'] }}-{{ $gender }}"></div>
                                    </div>
                                @endforeach
                                <div class="ef-fee-cell">
                                    <label class="ef-fee-label" for="{{ $idPrefix }}Eb-{{ $opt['id'] }}">얼리버드 할인율</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control ef-fee-eb-rate" inputmode="numeric" maxlength="3" id="{{ $idPrefix }}Eb-{{ $opt['id'] }}"
                                               data-platform="{{ $opt['id'] }}" value="{{ $isEdit ? $event->earlyBirdRateFor($opt['id']) : ($opt['id'] === \App\Models\Member::PLATFORM_MUNTO ? 10 : 0) }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="ef-fee-cell">
                                    <label class="ef-fee-label" for="{{ $idPrefix }}Pf-{{ $opt['id'] }}">플랫폼 수수료율</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control ef-fee-pf-rate" inputmode="numeric" maxlength="3" id="{{ $idPrefix }}Pf-{{ $opt['id'] }}"
                                               data-platform="{{ $opt['id'] }}" value="{{ $isEdit ? $event->platformFeeRateFor($opt['id']) : \App\Models\EventRound::DEFAULT_PLATFORM_FEE_RATE }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="create-hint mt-2">비워 두고 나중에 행사 상세에서 넣어도 돼요. 바꿔도 이미 추가한 페이백 대상의 금액은 그대로예요.</div>
                </div>

                {{-- 하단 버튼: 모바일은 전체 폭, PC 는 오른쪽 정렬 --}}
                <div class="ef-footer d-grid d-md-flex justify-content-md-end gap-2">
                    @if ($isEdit)
                        <button type="button" class="btn btn-primary btn-lg px-5 btn-update"><i class="ti ti-device-floppy me-1"></i>수정 저장</button>
                    @else
                        <a href="{{ route('admin.rotation.event.index') }}" class="btn btn-outline-secondary btn-lg order-2 order-md-1">취소</a>
                        <button type="button" class="btn btn-success btn-lg px-5 btn-create order-1 order-md-2"><i class="ti ti-plus me-1"></i>행사 생성</button>
                    @endif
                </div>
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
                    <h3 class="card-title mb-0">참여자 <span class="text-secondary fs-5 fw-normal" id="memberSortLabel">· 번호표 순</span></h3>
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
                        <div class="mt-2 mb-3 member-view-bar">
                        <div class="btn-group member-gender-tabs" role="group" aria-label="성별 보기">
                            <input type="radio" class="btn-check" name="memberGenderFilter" id="mgAll" value="" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary" for="mgAll"><i class="ti ti-users me-1"></i>전체 {{ $event->members->count() }}</label>
                            <input type="radio" class="btn-check" name="memberGenderFilter" id="mgM" value="M" autocomplete="off">
                            <label class="btn btn-outline-blue" for="mgM">남 {{ $maleCount }}</label>
                            <input type="radio" class="btn-check" name="memberGenderFilter" id="mgF" value="F" autocomplete="off">
                            <label class="btn btn-outline-pink" for="mgF">여 {{ $femaleCount }}</label>
                        </div>
                        {{-- 정렬: 기본은 번호표 순(서버 정렬). 득표수 기준으로 바꿔 볼 수 있다. --}}
                        <select class="form-select member-sort-select" id="memberSortSelect" aria-label="참여자 정렬">
                            <option value="ticket">번호표 순</option>
                            <option value="received_desc">받은 투표 많은 순</option>
                            <option value="received_asc">받은 투표 적은 순</option>
                            <option value="cast_desc">보낸 투표 많은 순</option>
                            <option value="cast_asc">보낸 투표 적은 순</option>
                            <option value="login_desc">최근 접속 순</option>
                            <option value="login_asc">오래된 접속 순 (기록 없음 먼저)</option>
                        </select>
                        </div>
                        {{-- 참여자 카드: 모바일 1열 / 768px↑ 2열 / 1200px↑ 3열. 번호표 순(서버 정렬) --}}
                        @php
                            $platformLabels   = [\App\Models\Member::PLATFORM_MUNTO => '문토', \App\Models\Member::PLATFORM_FRIP => '프립'];
                            $platformBadgeCls = [\App\Models\Member::PLATFORM_MUNTO => 'bg-orange-lt', \App\Models\Member::PLATFORM_FRIP => 'bg-cyan-lt'];
                            $typeLabels       = \App\Models\EventRound::typeOptions();
                            $typeBadgeCls     = [\App\Models\EventRound::TYPE_WINE => 'bg-red-lt', \App\Models\EventRound::TYPE_ONE => 'bg-purple-lt'];
                        @endphp
                        {{-- 남/여를 나눠 두 칸(PC 좌우, 모바일 위아래)으로 보여준다. 성별 보기 탭을 고르면 해당 칸만 넓게 보인다. --}}
                        <div class="row g-3 pm-cols">
                        @foreach (['M' => ['👨 남', 'bg-blue-lt', $maleCount], 'F' => ['👩 여', 'bg-pink-lt', $femaleCount]] as $colGender => [$colTitle, $colBg, $colCount])
                        <div class="col-12 col-lg-6 pm-col" data-gender-col="{{ $colGender }}">
                        <div class="pm-col-head {{ $colBg }}" role="button" tabindex="0"
                             data-bs-toggle="collapse" data-bs-target="#pmCollapse{{ $colGender }}"
                             aria-expanded="true" aria-controls="pmCollapse{{ $colGender }}">
                            {{ $colTitle }} <span class="text-secondary fw-normal">· {{ $colCount }}명</span>
                            <i class="ti ti-chevron-down ck-chevron" aria-hidden="true"></i>
                        </div>
                        <div class="collapse show pm-collapse" id="pmCollapse{{ $colGender }}" data-gender="{{ $colGender }}">
                        <div class="pm-grid">
                            @foreach ($event->members->where('gender', $colGender)->values() as $member)
                                @php
                                    $memberData = $member->toArray();
                                    if ($isSuperAdmin) {
                                        $memberData['password'] = $member->password;
                                    }
                                    $memberData['age']           = $member->birth_year ? (now()->year - $member->birth_year + 1) : null;
                                    $memberData['last_login_at'] = $member->last_login_at?->format('Y-m-d H:i');
                                    // 관리자 표기 라벨(`닉네임(플랫폼)_N호`) — 모달 제목/상세에서 그대로 쓴다.
                                    $memberLabel = $memberLabels[$member->id] ?? ($member->name . '호');
                                    $memberData['label'] = $memberLabel;
                                    $isFemale    = $member->gender === 'F';
                                    $phoneRaw    = preg_replace('/\D+/', '', (string) $member->phone);
                                    $phoneShown  = strlen($phoneRaw) === 11 ? preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1-$2-$3', $phoneRaw) : $member->phone;
                                    $profile     = array_filter([
                                        $member->birth_year ? ($member->birth_year % 100 < 10 ? '0' : '') . ($member->birth_year % 100) . '년생 (' . (now()->year - $member->birth_year + 1) . '세)' : null,
                                        $member->job,
                                        $member->height ? $member->height . 'cm' : null,
                                        $member->mbti,
                                    ]);
                                    $lifestyle   = array_filter([
                                        $member->drink_capacity ? '🍶 ' . $member->drink_capacity : null,
                                        $member->drink_frequency ? '🍻 ' . $member->drink_frequency : null,
                                        is_null($member->has_pet) ? null : ('🐾 ' . ($member->has_pet ? '애완동물 있음' : '애완동물 없음')),
                                        $member->friend_meet_frequency ? '👫 ' . $member->friend_meet_frequency : null,
                                        $member->religion ? '🙏 ' . $member->religion : null,
                                    ]);
                                    $visitCounts = $member->visit_counts ?? [];
                                @endphp
                                <div class="pm-card {{ $isFemale ? 'is-f' : 'is-m' }}" data-member-row data-gender="{{ $member->gender }}"
                                     data-order="{{ $loop->index }}" data-received="{{ (int) $member->received_votes_count }}" data-cast="{{ (int) $member->cast_votes_count }}" data-login="{{ $member->last_login_at?->timestamp ?? 0 }}">
                                    {{-- ① 번호표 · 라벨 · 플랫폼 · 방문 횟수 --}}
                                    <div class="pm-head">
                                        <div class="pm-ticket"><small>{{ $isFemale ? '여' : '남' }}</small><b>{{ $member->name }}</b></div>
                                        <div class="pm-who">
                                            <div class="pm-name">{{ $memberLabel }}</div>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                <span class="badge {{ $platformBadgeCls[$member->platform] ?? 'bg-secondary-lt' }}">{{ $platformLabels[$member->platform] ?? ($member->platform ?: '-') }}</span>
                                                @foreach ($typeLabels as $typeKey => $typeLabel)
                                                    @if (! empty($visitCounts[$typeKey]))
                                                        <span class="badge {{ $typeBadgeCls[$typeKey] ?? 'bg-azure-lt' }}">{{ $typeLabel }} {{ $visitCounts[$typeKey] }}회</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ② 정보: 항목명 : 값 --}}
                                    <dl class="pm-info">
                                        <div><dt>프로필</dt><dd>{{ $profile ? implode(' · ', $profile) : '-' }}</dd></div>
                                        <div><dt>지역</dt><dd>{{ $member->region ?: '-' }}
                                            @if (! is_null($member->is_smoker))
                                                <span class="badge {{ $member->is_smoker ? 'bg-red-lt' : 'bg-green-lt' }} ms-1">{{ $member->is_smoker ? '흡연' : '비흡연' }}</span>
                                            @endif
                                        </dd></div>
                                        @if ($lifestyle)
                                            <div><dt>생활</dt><dd>{{ implode(' · ', $lifestyle) }}</dd></div>
                                        @endif
                                        <div><dt>핸드폰</dt><dd>
                                            @if ($phoneRaw)
                                                <a href="tel:{{ $phoneRaw }}" class="text-reset fw-semibold">{{ $phoneShown }}</a>
                                            @else
                                                -
                                            @endif
                                        </dd></div>
                                        <div><dt>접속</dt><dd>마지막 로그인 {{ $member->last_login_at?->format('m/d H:i') ?? '기록 없음' }}</dd></div>
                                        @if ($isSuperAdmin)
                                            <div><dt>비밀번호</dt><dd><span class="text-red fw-bold font-monospace" style="user-select:all; cursor:text;">{{ $member->password ?? '-' }}</span></dd></div>
                                        @endif
                                    </dl>

                                    {{-- ③ 활동: 누르면 해당 목록 모달 (0건은 비활성) --}}
                                    <div class="pm-stats">
                                        <button type="button" class="pm-stat btn-voted" data-member-id="{{ $member->id }}" data-member-label="{{ $memberLabel }}" @disabled($member->cast_votes_count < 1)>
                                            <span class="pm-stat-label">보낸 투표</span><span class="pm-stat-val">{{ $member->cast_votes_count }}</span>
                                        </button>
                                        <button type="button" class="pm-stat is-pink btn-voters" data-member-id="{{ $member->id }}" data-member-label="{{ $memberLabel }}" @disabled($member->received_votes_count < 1)>
                                            <span class="pm-stat-label">받은 투표</span><span class="pm-stat-val">{{ $member->received_votes_count }}</span>
                                        </button>
                                        <button type="button" class="pm-stat btn-sent-messages" data-member-id="{{ $member->id }}" data-member-label="{{ $memberLabel }}" @disabled($member->sent_messages_count < 1)>
                                            <span class="pm-stat-label">메시지</span><span class="pm-stat-val">{{ $member->sent_messages_count }}</span>
                                        </button>
                                        <button type="button" class="pm-stat is-yellow btn-sent-requests" data-member-id="{{ $member->id }}" data-member-label="{{ $memberLabel }}" @disabled($member->admin_requests_count < 1)>
                                            <span class="pm-stat-label">요청</span><span class="pm-stat-val">{{ $member->admin_requests_count }}</span>
                                        </button>
                                    </div>

                                    {{-- ④ 버튼 --}}
                                    <div class="pm-actions">
                                        <button type="button" class="btn btn-outline-azure btn-member-detail" data-member='@json($memberData)'>
                                            <i class="ti ti-id me-1"></i>상세보기 · 수정
                                        </button>
                                        <button type="button" class="btn btn-outline-orange btn-member-login" data-member-id="{{ $member->id }}">
                                            <i class="ti ti-login-2 me-1"></i>회원으로 로그인
                                        </button>
                                        @if ($isSuperAdmin && $member->phone)
                                            <a href="{{ route('admin.rotation.history.index', ['phone' => $member->phone]) }}" target="_blank" rel="noopener" class="btn btn-outline-teal">
                                                <i class="ti ti-history me-1"></i>만남 이력
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        </div>
                        </div>
                        @endforeach
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

        // ===== 생성/수정 공통: 참가비 · 수수료 입력 =====
        function efDigits(v){ var d = String(v == null ? '' : v).replace(/[^\d]/g, ''); return d === '' ? null : parseInt(d, 10); }
        // 금액 칸: 입력하면서 쉼표 표시, 정가 아래에 얼리버드가 미리보기
        $(document).on('input', '.ef-money', function(){
            var n = efDigits(this.value);
            this.value = n === null ? '' : n.toLocaleString('ko-KR');
        });
        function efRenderEarlyBird(){
            $('.ef-fee-price').each(function(){
                var platform = $(this).data('platform');
                var rate = Math.min(100, efDigits($('.ef-fee-eb-rate[data-platform="' + platform + '"]').val()) || 0);
                var base = efDigits(this.value);
                $('[data-eb-for="' + platform + '-' + $(this).data('gender') + '"]')
                    .text(base !== null && rate > 0 ? '얼리버드 ' + Math.floor(base * (100 - rate) / 100).toLocaleString('ko-KR') + '원' : '');
            });
        }
        $(document).on('input', '.ef-fee-price, .ef-fee-eb-rate', efRenderEarlyBird);
        efRenderEarlyBird();
        // 요청에 붙일 참가비·수수료 값
        function efFeeData(){
            var data = {};
            $('.ef-fee-price').each(function(){ data['fee_prices[' + $(this).data('platform') + '][' + $(this).data('gender') + ']'] = this.value; });
            $('.ef-fee-eb-rate').each(function(){ data['early_bird_rates[' + $(this).data('platform') + ']'] = this.value; });
            $('.ef-fee-pf-rate').each(function(){ data['platform_fee_rates[' + $(this).data('platform') + ']'] = this.value; });
            return data;
        }

        @unless ($isEdit)
        // ===== 생성: 리뷰 인증 게이트 토글 라벨 동기화 =====
        $('#reviewGateToggle').on('change', function(){
            $('#reviewGateLabel').text(this.checked ? '켜짐' : '꺼짐');
        });

        // ===== 생성: 타입에 따라 1:1 안내·음료 선택 토글 표시 =====
        function createType(){ return $('#eventForm [name=type]:checked').val(); }
        function syncCreateType(){
            var isOne = createType() === '{{ \App\Models\EventRound::TYPE_ONE }}';
            $('.create-note-one').toggle(isOne);
            $('.drink-toggle-col').toggle(isOne);
        }
        $('#eventForm [name=type]').on('change', syncCreateType);
        syncCreateType();

        // ===== 생성: 음료 선택 토글 라벨 동기화 =====
        $('#drinkToggle').on('change', function(){
            $('#drinkLabel').text(this.checked ? '켜짐' : '꺼짐');
        });

        // ===== 생성: 지난 행사 설정 불러오기 =====
        @if (! empty($copySources))
        var COPY_SOURCES = @json($copySources);
        $('#copySource').on('change', function(){
            var src = COPY_SOURCES.filter(function(c){ return String(c.id) === String($('#copySource').val()); })[0];
            if (!src) { return; }

            $('#createTitle').val(src.title);
            $('#eventForm [name=type][value="' + src.type + '"]').prop('checked', true).trigger('change');
            $('#createTableCount').val(src.table_count);
            $('#reviewGateToggle').prop('checked', !!src.review_gate).trigger('change');
            $('#drinkToggle').prop('checked', !!src.drink).trigger('change');

            $('.ef-fee-price').each(function(){
                var v = ((src.fee_prices || {})[$(this).data('platform')] || {})[$(this).data('gender')];
                $(this).val(v != null ? Number(v).toLocaleString('ko-KR') : '');
            });
            $('.ef-fee-eb-rate').each(function(){
                var v = (src.early_bird || {})[$(this).data('platform')];
                $(this).val(v != null ? v : 0);
            });
            $('.ef-fee-pf-rate').each(function(){
                var v = (src.platform_fee || {})[$(this).data('platform')];
                if (v != null) { $(this).val(v); }
            });
            efRenderEarlyBird();
        });
        @endif

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
            if(tableCount < 1){ await salert({text: '테이블 수를 1개 이상 입력해주세요.', cancel: false}); $('#createTableCount').focus(); return false; }
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
                    data: $.extend({
                        title:               title,
                        type:                createType(),
                        event_time:          time,
                        table_count:         tableCount,
                        review_gate_enabled: $('#reviewGateToggle').is(':checked') ? 1 : 0,
                        drink_enabled:       $('#drinkToggle').is(':checked') ? 1 : 0
                    }, efFeeData()),
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

        // ===== 상세: 참여자 남/여 칸 접기/펼치기 — 기본은 펼침, 접은 상태는 기억 (헤더에서 Enter/Space 로도 토글) =====
        document.querySelectorAll('.pm-collapse').forEach(function(el){
            var KEY = 'rotable.eventDetail.members' + el.dataset.gender + 'Collapsed';
            var $head = $('[data-bs-target="#' + el.id + '"]');
            var collapsed = false;
            try { collapsed = localStorage.getItem(KEY) === '1'; } catch(e) { /* 저장소 사용 불가 시 기본(펼침) */ }
            if(collapsed){
                el.classList.remove('show');
                $head.attr('aria-expanded', 'false');
            }
            function save(v){ try { localStorage.setItem(KEY, v ? '1' : '0'); } catch(e) {} }
            el.addEventListener('hidden.bs.collapse', function(e){ if(e.target === el){ save(true); } });
            el.addEventListener('shown.bs.collapse', function(e){ if(e.target === el){ save(false); } });
            $head.on('keydown', function(e){
                if(e.target !== this){ return; }
                if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); this.click(); }
            });
        });

        // ===== 상세: 참여자 성별 보기 (전체/남/여) =====
        $('input[name="memberGenderFilter"]').on('change', function(){
            var g = $('input[name="memberGenderFilter"]:checked').val();
            // 고른 성별 칸만 남기고 넓게(전체면 좌우 반반)
            $('.pm-cols').toggleClass('is-solo', !!g);
            $('.pm-col').each(function(){
                var hide = !!g && $(this).data('gender-col') !== g;
                $(this).toggleClass('d-none', hide)
                       .toggleClass('col-lg-6', !g)
                       .toggleClass('col-lg-12', !!g);
            });
        });

        // ===== 상세: 참여자 정렬 (번호표 / 받은·보낸 투표 많은·적은 순) =====
        // 서버가 번호표 순으로 그려 둔 카드를 data-order(원래 순서)로 되돌릴 수 있게 하고,
        // 득표수가 같으면 번호표 순을 유지한다. (성별 필터와 독립적으로 동작)
        var MEMBER_SORT_LABEL = {
            ticket: '번호표 순', received_desc: '받은 투표 많은 순', received_asc: '받은 투표 적은 순',
            cast_desc: '보낸 투표 많은 순', cast_asc: '보낸 투표 적은 순',
            login_desc: '최근 접속 순', login_asc: '오래된 접속 순'
        };
        $('#memberSortSelect').on('change', function(){
            var mode = this.value;
            var key = mode.indexOf('received') === 0 ? 'received' : (mode.indexOf('cast') === 0 ? 'cast' : (mode.indexOf('login') === 0 ? 'login' : null));
            var dir = mode.slice(-3) === 'asc' ? 1 : -1;
            // 남/여 칸마다 따로 정렬한다.
            $('.pm-grid').each(function(){
                var $grid = $(this);
                var cards = $grid.children('[data-member-row]').get();
                cards.sort(function(a, b){
                    var byOrder = Number(a.dataset.order) - Number(b.dataset.order);
                    if(!key){ return byOrder; }
                    var diff = Number(a.dataset[key]) - Number(b.dataset[key]);
                    return diff !== 0 ? diff * dir : byOrder;
                });
                $grid.append(cards);
            });
            $('#memberSortLabel').text('· ' + (MEMBER_SORT_LABEL[mode] || '번호표 순'));
            // 선택한 정렬을 쿼리파라미터(?sort=)에 남겨 새로고침·링크 공유에도 유지한다. (페이지 이동 없이 주소만 바꿈)
            var url = new URL(window.location.href);
            if(mode === 'ticket'){ url.searchParams.delete('sort'); } else { url.searchParams.set('sort', mode); }
            history.replaceState(null, '', url);
        });
        // 주소의 ?sort= 로 정렬 복원 (알 수 없는 값이면 무시하고 기본 번호표 순)
        (function(){
            var saved = new URL(window.location.href).searchParams.get('sort');
            if(saved && saved !== 'ticket' && MEMBER_SORT_LABEL[saved] && $('#memberSortSelect').length){
                $('#memberSortSelect').val(saved).trigger('change');
            }
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

        // ===== 상세: 리뷰 인증 게이트 토글 라벨 동기화 =====
        $('#reviewGateToggle').on('change', function(){
            $('#reviewGateLabel').text(this.checked ? '켜짐' : '꺼짐');
        });

        // ===== 상세: 음료 선택 토글 — 라벨 동기화, 1:1 타입일 때만 표시 =====
        $('#drinkToggle').on('change', function(){
            $('#drinkLabel').text(this.checked ? '켜짐' : '꺼짐');
        });
        function syncEditType(){
            $('.drink-toggle-col').toggle($('#eventForm [name=type]:checked').val() === '{{ \App\Models\EventRound::TYPE_ONE }}');
        }
        $('#eventForm [name=type]').on('change', syncEditType);
        syncEditType();

        // ===== 상세: 행사 정보 수정 =====
        $('.btn-update').click(async function(){
            let $f = $('#eventForm');
            let title = ($f.find('[name=title]').val() || '').trim();
            let time  = $f.find('[name=event_time]').val();
            if(!title){ await salert({text: '행사명을 입력해주세요.', cancel: false}); $('#editTitle').focus(); return false; }
            if(!time){ await salert({text: '일시를 선택해주세요.', cancel: false}); return false; }

            if(await salert({text: '행사 정보를 수정하시겠습니까?'})){
                $.ajax({
                    url: "{{ route('admin.rotation.event.update', $event->id) }}",
                    type: 'PUT',
                    data: $.extend({
                        title:               title,
                        type:                $f.find('[name=type]:checked').val(),
                        event_time:          time,
                        review_gate_enabled: $('#reviewGateToggle').is(':checked') ? 1 : 0,
                        drink_enabled:       $('#drinkToggle').is(':checked') ? 1 : 0
                    }, efFeeData()),
                    // 상단 탭바·뱃지·음료 카드(1:1 전용) 등 타입/이름에 따라 달라지는 화면을 맞추기 위해 새로고침
                    success: function(res){ alert(res?.msg); if(res?.status === 200){ location.reload(); } },
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
    @include('admin.rotation._votersModal')
    @include('admin.rotation._votedModal')
    @include('admin.rotation._memberDetailModal', ['event' => $event, 'isSuperAdmin' => $isSuperAdmin ?? false])
@endif
@endsection
