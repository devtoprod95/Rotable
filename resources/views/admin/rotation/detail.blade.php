@extends('layouts.app')

@section('title', '상세페이지 제작')

{{-- 제목(상세페이지 제작) 옆에 표시: 마지막 저장(누가·언제). 현재 선택 유형 기준. --}}
@section('pageTitleExtra')
    <span id="detailSavedInfo"
          class="badge bg-dark text-white fw-semibold align-middle"
          style="display:none; font-size:0.8125rem; padding:0.4em 0.7em;"></span>
@endsection

@section('content')
<style>
    /* 페이지 설정 · 명단 모달: 행사 카드 헤더(행사명·날짜)를 눈에 띄게 */
    .roster-ev-card { border: 2px solid #f9a8d4; }
    .roster-ev-head { background: #fdf2f8; border-bottom: 2px solid #f9a8d4; padding: .85rem 1rem; }
    .roster-ev-head .form-control-lg { background: #ffffff; border-color: #f9a8d4; color: #111827; }
    .roster-ev-head .form-control-lg:focus { border-color: #ec4899; box-shadow: 0 0 0 .2rem rgba(236, 72, 153, .2); }

    /* 참여자 명단 드래그 정렬 */
    .roster-handle{ cursor:grab; color:#94a3b8; touch-action:none; }
    .roster-handle:active{ cursor:grabbing; }
    .roster-ghost{ opacity:.45; background:#fce7f3 !important; }
    .roster-chosen{ box-shadow:0 2px 10px rgba(236,72,153,.25); }
</style>
{{-- 상세페이지용 웹폰트: @import 보다 빠르게 로드되도록 link 로 선로드 (캡처 시 폰트 적용 보장) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jua&family=Gothic+A1:wght@400;500;700;800;900&display=swap" rel="stylesheet">

{{-- 이미지 생성 중 로딩 오버레이 (드롭다운이 닫혀도 보이도록 화면 전체 덮개로 표시) --}}
<div id="imageExportOverlay"
     style="display:none; position:fixed; inset:0; z-index:2000; background:rgba(17,24,39,0.55);
            align-items:center; justify-content:center;">
    <div style="background:#ffffff; border-radius:14px; padding:26px 34px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.25);">
        <div class="spinner-border text-pink" style="width:2.5rem; height:2.5rem;" role="status" aria-hidden="true"></div>
        <div id="imageExportOverlayText" style="margin-top:14px; font-size:16px; font-weight:700; color:#374151;">
            이미지 생성 중…
        </div>
        <div style="margin-top:4px; font-size:13px; color:#9ca3af;">잠시만 기다려 주세요 (고화질 처리 중)</div>
    </div>
</div>

<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0">
                    <i class="ti ti-sparkles text-pink me-1"></i> 상세페이지 제작
                </h3>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    {{-- 유형 선택 (와인파티 / 1:1 소개팅) --}}
                    <div class="btn-group" role="group" aria-label="샘플 유형">
                        <input type="radio" class="btn-check" name="sampleType" id="sampleTypeWine" value="wine" autocomplete="off" checked>
                        <label class="btn btn-outline-pink" for="sampleTypeWine">
                            <i class="ti ti-glass-full me-1"></i> 와인파티
                        </label>
                        <input type="radio" class="btn-check" name="sampleType" id="sampleTypeSolo" value="solo" autocomplete="off">
                        <label class="btn btn-outline-pink" for="sampleTypeSolo">
                            <i class="ti ti-users me-1"></i> 1:1 소개팅
                        </label>
                    </div>

                    <span class="vr d-none d-md-inline"></span>

                    {{-- 저장 그룹: 저장본 선택/저장/다른 이름으로 저장 (자주 쓰므로 밖으로 노출) --}}
                    <div class="btn-group" role="group" aria-label="저장">
                        <button type="button" class="btn btn-primary btn-detail-save">
                            <i class="ti ti-device-floppy me-1"></i> 저장
                        </button>
                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">저장 옵션</span>
                        </button>
                        <div class="dropdown-menu">
                            <button type="button" class="dropdown-item btn-open-saves">
                                <i class="ti ti-list-details me-2 fs-2"></i> 저장 목록 불러오기
                            </button>
                            <button type="button" class="dropdown-item btn-detail-save-as">
                                <i class="ti ti-bookmark-plus me-2 fs-2"></i> 다른 이름으로 저장
                            </button>
                        </div>
                    </div>

                    <span class="vr d-none d-md-inline"></span>

                    {{-- 편집 그룹: 페이지 설정·참여자 명단(통합) / 샘플 불러오기 --}}
                    <button type="button" class="btn btn-pink btn-open-settings">
                        <i class="ti ti-adjustments me-1"></i> 페이지 설정 · 명단 편집
                    </button>
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-layout-board me-1"></i> 샘플 불러오기
                        </button>
                        <div class="dropdown-menu">
                            <button type="button" class="dropdown-item btn-sample-one">
                                <i class="ti ti-layout-rows me-2 fs-2"></i> 1열 샘플
                            </button>
                            <button type="button" class="dropdown-item btn-sample-two">
                                <i class="ti ti-layout-columns me-2 fs-2"></i> 2열 샘플
                            </button>
                        </div>
                    </div>

                    <span class="vr d-none d-md-inline"></span>

                    {{-- 결과 그룹: 미리보기 / 다운로드 --}}
                    <button type="button" class="btn btn-azure btn-open-preview">
                        <i class="ti ti-eye me-1"></i> 미리보기
                    </button>
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-download me-1"></i> 다운로드
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <button type="button" class="dropdown-item btn-detail-download">
                                <i class="ti ti-download me-2 fs-2"></i> 이미지 다운로드 <span class="text-secondary small ms-1">(전체·최대 5장)</span>
                            </button>
                            <button type="button" class="dropdown-item btn-detail-download-page" data-page-only="참가자명단">
                                <i class="ti ti-user-heart me-2 fs-2"></i> 참가자 명단만 <span class="text-secondary small ms-1">(2번 장)</span>
                            </button>
                            <button type="button" class="dropdown-item btn-open-feed">
                                <i class="ti ti-photo-down me-2 fs-2"></i> 피드 이미지 다운
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <small class="form-hint text-muted d-block mb-3">
                    <b>유형(와인파티 / 1:1 소개팅)</b>을 고른 뒤 <b>샘플 불러오기 → 1열 / 2열</b>로 기본 틀을 불러오세요.
                    이후 HTML 에디터로 꾸미고 <b>[저장]</b> 하세요.
                    편집 중인 저장본이 있으면 그 저장본이 갱신되고, 없으면 이름을 입력해 <b>저장 목록</b>에 새로 저장됩니다.
                    (<b>저장 ▾ → 저장 목록 불러오기</b> 로 이전 저장본을 불러오거나, <b>다른 이름으로 저장</b>으로 새 버전을 남길 수 있어요.)
                </small>
                <label class="form-label">상세페이지 작성 (HTML)</label>
                <textarea id="detailHtmlEditor">{!! $savedWine !!}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- 화면을 따라다니는 플로팅 미리보기 버튼 --}}
<button type="button" id="floatingPreviewBtn"
        class="btn btn-azure shadow-lg d-flex align-items-center gap-2 btn-open-preview"
        style="position: fixed; left: 24px; bottom: 24px; z-index: 1030; border-radius: 30px; padding: 12px 20px; font-weight: 700;">
    <i class="ti ti-eye fs-3"></i> 미리보기
</button>

{{-- 미리보기 모달 --}}
<div class="modal modal-blur fade" id="detailPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-eye me-1"></i> 미리보기
                    <span class="badge bg-azure-lt ms-1">PNG 다운로드 영역</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                {{-- 다운로드는 1080px 고정폭으로 캡처된다. 미리보기도 "같은 1080px"로 렌더하되,
                     화면에선 transform:scale 로 '비율 그대로' 축소만 한다. (max-width 로 줄이면
                     레이아웃이 reflow 되어 좌우/상하 패딩이 다르게 보이므로 금지)
                     → #detailPreviewScaler 가 축소 래퍼(높이 보정), #detailPreview 가 실제 1080px 캡처 대상. --}}
                <div class="d-flex justify-content-center">
                    <div id="detailPreviewScaler" style="width:100%; overflow:hidden;">
                        <div id="detailPreview" style="width: 1080px; transform-origin: top left; background:#ffffff; border-radius:8px;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-dark btn-detail-download">
                    <i class="ti ti-download me-1"></i> 이미지 다운로드
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 페이지 설정 모달 (행사명 · 남/녀 나이 · 남/녀 키) --}}
{{-- 에디터 HTML 안의 data-detail-settings(현재 설정)/data-roster-grid(명단)를 읽어 폼에 채우고,
     [적용] 시 data-setting 마커 지점의 값과 명단 그리드를 한 번에 갈아끼운다.
     (요소/data 속성 기준이라 주석이 지워져도 안전하게 동작한다.)
     ※ 페이지 설정과 참여자 명단 편집을 한 모달로 통합했다. --}}
<div class="modal modal-blur fade" id="detailSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-adjustments me-1"></i> 페이지 설정 · 참여자 명단
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h3 class="mb-2 d-flex align-items-center gap-2">
                    <i class="ti ti-adjustments text-pink"></i> 페이지 설정
                    <span class="text-secondary fs-4 fw-normal">— 참여 조건 · 자격요건</span>
                </h3>
                <div class="alert alert-info mb-3">
                    <div class="text-secondary">
                        각 <b>행사별로 남/녀 나이 · 남/녀 키</b>를 따로 입력할 수 있어요.
                        (아래 <b>참여자 명단</b>의 행사 카드와 1:1로 대응돼요.)
                        <br>나이(세)를 넣으면 <b>자동으로 "OO~OO년생"</b> 으로 환산돼 참여 조건에 표기되고,
                        샘플을 다시 불러와도 <b>설정값은 그대로 따라옵니다.</b>
                    </div>
                </div>

                {{-- 행사 카드별 설정(나이/키) 입력 영역 — JS 가 행사 수에 맞춰 렌더링 --}}
                <div id="settingsEventsBody"></div>

                <div id="settingsEmpty" class="text-center text-secondary py-5" style="display:none;">
                    <i class="ti ti-alert-triangle fs-1 d-block mb-2"></i>
                    현재 내용에서 설정 영역을 찾지 못했어요.<br>
                    먼저 <b>1열 / 2열 샘플</b>을 불러온 뒤 다시 시도해 주세요.
                </div>

                <hr class="my-4">

                {{-- ===== 참여자 명단 편집 섹션 (설정과 한 모달로 통합) ===== --}}
                <h3 class="mb-2 d-flex align-items-center gap-2">
                    <i class="ti ti-users-group text-pink"></i> 참여자 명단 편집
                    <span class="text-secondary fs-4 fw-normal">— 행사별 남/녀 명단</span>
                </h3>
                <div class="alert alert-info mb-3">
                    {{-- Tabler .alert 는 가로(flex) 배치라, 안내 문구를 한 블록으로 묶어 세로로 쌓는다. --}}
                    <div class="w-100">
                        <div class="text-secondary">
                            표에 <b>키 · 직업 · 나이</b>만 입력하면 됩니다. <b>번호(N호)와 상단 인원수(남/여)</b>는 자동으로 매겨져요.
                            부족한 쪽은 <b>모집중 추가</b> 버튼으로 "모집중" 자리를 직접 끼워 넣으세요.
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <div class="fw-bold text-pink">
                                <i class="ti ti-sort-ascending me-1"></i>
                                [적용]을 누르면 행사별 남/여 명단이 자동 정렬돼요: ① 나이 어린 순 → ② 나이가 같으면 키 큰 순
                            </div>
                            <div class="text-secondary small mt-1">
                                모집중 자리는 맨 아래 · "20대 후반", "97년생" 같은 표기도 인식 · 나이/키가 비어 있으면 뒤로
                            </div>
                            <div class="fw-bold text-pink mt-2">
                                <i class="ti ti-chart-bar me-1"></i>
                                [적용] 시 행사 카드 아래에 남/여 나이·키 분포도도 자동으로 그려져요.
                                <span class="text-secondary fw-normal small d-block mt-1">나이: 명단에 적힌 표기 그대로("30대", "30대 후반", "29세", "97년생"…) · 키: 5cm 단위(큰 키가 위)</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 행사 카드별 편집 영역이 여기에 동적으로 렌더링됨 --}}
                <div id="rosterEditorBody"></div>
                {{-- 명단 영역을 못 찾았을 때 안내 --}}
                <div id="rosterEditorEmpty" class="text-center text-secondary py-5" style="display:none;">
                    <i class="ti ti-alert-triangle fs-1 d-block mb-2"></i>
                    현재 내용에서 참여자 명단 영역을 찾지 못했어요.<br>
                    먼저 <b>1열 / 2열 샘플</b>을 불러온 뒤 다시 시도해 주세요.
                </div>

                <hr class="my-4">

                {{-- ===== 이전 로테이블 기록 편집 섹션 (명단 아래) ===== --}}
                <h3 class="mb-2 d-flex align-items-center gap-2">
                    <i class="ti ti-history text-pink"></i> 이전 로테이블 기록
                    <span class="text-secondary fs-4 fw-normal">— 날짜 · 행사명 · 인원 · 매칭</span>
                </h3>
                <div class="alert alert-info mb-3">
                    <div class="text-secondary">
                        지난 행사 기록을 직접 추가/수정/삭제할 수 있어요.
                        <b>샘플을 다시 불러와도 입력한 기록은 그대로 유지</b>됩니다.
                    </div>
                </div>
                <div id="recordsEditorBody"></div>
                <button type="button" class="btn btn-outline-pink btn-record-add">
                    <i class="ti ti-plus me-1"></i> 기록 추가
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary btn-settings-apply">
                    <i class="ti ti-check me-1"></i> 적용 (설정 · 명단)
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 저장 목록(이름 붙여 저장) 모달 --}}
{{-- 현재 선택한 유형(wine/solo)의 "이름 붙여 저장한" 상세페이지 목록을 보여주고, 불러오기/삭제한다. --}}
<div class="modal modal-blur fade" id="detailSavesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-list-details me-1"></i> 저장 목록
                    <span class="badge bg-dark ms-1" id="savesTypeBadge">전체</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-secondary small mb-3">
                    <i class="ti ti-info-circle me-1"></i>
                    <b>와인파티·1:1 소개팅 모든 유형</b>의 저장본이 함께 보여집니다. (각 항목 앞 뱃지로 유형 구분)
                    <b>불러오기</b> 하면 그 저장본의 유형으로 에디터가 전환되고 <b>현재 편집 대상</b>이 되며,
                    이후 상단 <b>[저장]</b> 을 누르면 그 저장본이 그대로 갱신됩니다.
                    (다른 이름으로 남기려면 <b>다른 이름으로 저장</b>)
                </div>
                <div id="savesListBody">
                    {{-- JS 가 목록을 렌더링 --}}
                </div>
                <div id="savesEmpty" class="text-center text-secondary py-5" style="display:none;">
                    <i class="ti ti-folder-off fs-1 d-block mb-2"></i>
                    저장된 목록이 없어요.<br>
                    상단 <b>더보기 → 다른 이름으로 저장</b> 으로 현재 상세페이지를 저장해 보세요.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary btn-detail-save-as">
                    <i class="ti ti-bookmark-plus me-1"></i> 다른 이름으로 저장
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 피드 이미지 다운로드 모달 --}}
{{-- rotable_banner.png + 선택한 행사의 "현재 DB 참여자 명단"을 합쳐 하나의 이미지로 다운로드한다. --}}
<div class="modal modal-blur fade" id="detailFeedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-photo-down me-1"></i> 피드 이미지 다운로드
                    <span class="badge bg-pink-lt ms-1">로고 + 참여자 명단 · 3:4 세로</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <div class="text-secondary small">
                        <i class="ti ti-info-circle me-1"></i>
                        현재 에디터의 <b>참여자 명단</b>을 그대로 1장의 피드 이미지로 만듭니다.
                        (명단은 <b>참여자 명단 편집</b>에서 수정하세요.)
                    </div>
                </div>

                <div id="feedEmpty" class="alert alert-warning mb-3" style="display:none;">
                    현재 내용에서 참여자 명단 영역을 찾지 못했어요.
                    먼저 <b>1열 / 2열 샘플</b>을 불러오거나 <b>참여자 명단 편집</b>으로 명단을 채워 주세요.
                </div>

                {{-- 미리보기 축소용 래퍼: 폭 1080 을 화면에선 작게(45%) 보여준다. 높이는 내용만큼. --}}
                <div style="display:flex; justify-content:center; overflow:auto;">
                    <div id="feedPreviewBox" style="width:486px; min-height:300px;">
                        {{-- 이 영역이 그대로 이미지로 캡처됩니다. (폭 1080, 높이는 명단 길이만큼) --}}
                        <div id="feedCapture" style="width:1080px; background:#ffffff; overflow:hidden; transform:scale(0.45); transform-origin:top left;">
                            <div id="feedRosterArea" style="width:100%; box-sizing:border-box; font-family:'Gothic A1','Pretendard','Apple SD Gothic Neo',sans-serif;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-pink btn-feed-download">
                    <i class="ti ti-download me-1"></i> 이미지 다운로드
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        var SAMPLES = {
            wine: {
                1: {!! json_encode($wineOne) !!},
                2: {!! json_encode($wineTwo) !!}
            },
            solo: {
                1: {!! json_encode($soloOne) !!},
                2: {!! json_encode($soloTwo) !!}
            }
        };

        // 유형별 저장본(에디터 작업 내용을 유형별로 메모리에 보관)
        var SAVED = {
            wine: {!! json_encode($savedWine) !!},
            solo: {!! json_encode($savedSolo) !!}
        };

        // 유형별 마지막 저장 메타(누가·언제). 저장 이력이 없으면 null.
        var META = {
            wine: {!! json_encode($metaWine) !!},
            solo: {!! json_encode($metaSolo) !!}
        };

        // 유형별 "현재 편집 중인 저장본" {id, title}. 저장 목록에 없는 새 내용이면 id=null.
        // [저장] 시 id 가 있으면 그 항목을 덮어쓰고(업데이트), 없으면 이름을 받아 새로 저장한다.
        var CURRENT = {
            wine: { id: {!! json_encode($currentIdWine) !!}, title: {!! json_encode($currentTitleWine) !!} },
            solo: { id: {!! json_encode($currentIdSolo) !!}, title: {!! json_encode($currentTitleSolo) !!} }
        };

        // 현재 에디터에 올라와 있는 유형 (초기값: 와인)
        var activeType = 'wine';

        // TinyMCE 초기화(또는 폴백) 완료 플래그 — 주소 복원은 이 이후에만 실행한다.
        var detailEditorReady = false;

        function currentType(){
            return $('input[name="sampleType"]:checked').val() === 'solo' ? 'solo' : 'wine';
        }

        /* =====================================================================
         * 선택 상태 ↔ 주소 쿼리파라미터 동기화
         *  - ?type=wine|solo : 현재 편집 중인 유형
         *  - ?saveId=N       : 현재 편집 중인 저장본 id (없으면 생략)
         *  새로고침·뒤로/앞으로 가기에도 선택이 유지된다.
         * =================================================================== */
        var detailPageUrl = window.location.pathname;
        var suppressUrlSync = false; // popstate/초기화 중 중복 pushState 방지

        // 현재 상태(유형+편집 저장본)를 주소에 반영한다.
        //  replace=true 면 history 를 쌓지 않고 현재 항목만 교체(유형 전환 등 빈번한 변경용).
        function syncUrl(replace){
            if (suppressUrlSync) { return; }
            var t = currentType();
            var cur = CURRENT[t] || {};
            var qs = '?type=' + encodeURIComponent(t);
            if (cur.id) { qs += '&saveId=' + encodeURIComponent(cur.id); }
            if (window.location.search === qs) { return; } // 동일하면 그대로
            var state = { type: t, saveId: cur.id || null };
            if (replace) { history.replaceState(state, '', detailPageUrl + qs); }
            else { history.pushState(state, '', detailPageUrl + qs); }
        }

        // 주소의 쿼리파라미터를 읽어 선택 상태를 복원한다. (초기 진입/뒤로가기)
        //  - type 이 현재와 다르면 유형 라디오를 전환한다.
        //  - saveId 가 있으면 그 저장본을 불러온다(없으면 최근 저장본/샘플 그대로).
        function restoreFromUrl(){
            var q = new URLSearchParams(window.location.search);
            var t = q.get('type') === 'solo' ? 'solo' : (q.get('type') === 'wine' ? 'wine' : null);
            var saveId = q.get('saveId');

            suppressUrlSync = true;
            if (t && t !== currentType()) {
                $('input[name="sampleType"][value="' + t + '"]').prop('checked', true).trigger('change');
            }
            suppressUrlSync = false;

            if (saveId) {
                // 이미 그 저장본을 편집 중이면 재로딩 불필요.
                var cur = CURRENT[currentType()] || {};
                if (String(cur.id) !== String(saveId)) {
                    loadSnapshotById(parseInt(saveId, 10), false);
                }
            }
        }

        // 저장본 id 로 에디터에 로드한다. (저장 목록의 '불러오기'와 동일 동작을 재사용)
        //  pushUrl=true 면 주소도 갱신한다.
        function loadSnapshotById(id, pushUrl){
            if (!id) { return; }
            $.ajax({
                url: "{{ url('admin/rotation/detail/saves') }}/" + id,
                type: 'GET',
                success: function(res){
                    var type = (res && res.type === 'solo') ? 'solo' : (res && res.type === 'wine' ? 'wine' : currentType());
                    suppressUrlSync = true;
                    if (type !== currentType()) {
                        $('input[name="sampleType"][value="' + type + '"]').prop('checked', true).trigger('change');
                    }
                    suppressUrlSync = false;
                    // 저장된 HTML 의 '마지막 업데이트' 문구는 지금 접속한 시각으로 교체해 노출한다.
                    setHtml((res && res.html) ? applyUpdatedAtToSample(res.html) : '');
                    CURRENT[type] = { id: (res && res.id) ? res.id : id, title: (res && res.title) || '' };
                    SAVED[type] = getHtml();
                    activeType = type;
                    renderSavedInfo();
                    if (pushUrl) { syncUrl(false); }
                },
                error: function(){ /* 없는 id 면 무시하고 기본 상태 유지 */ }
            });
        }

        // 제목 옆 배지를 현재 유형의 "편집 중 저장본 제목 + 마지막 저장(누가·언제)"으로 갱신한다.
        function renderSavedInfo(){
            var t = currentType();
            var meta = META[t];
            var cur  = CURRENT[t] || {};
            var $info = $('#detailSavedInfo');
            var parts = [];
            if (cur.id && cur.title) {
                parts.push('📄 ' + cur.title);
            } else {
                parts.push('🆕 새 상세페이지 (미저장)');
            }
            if (meta && meta.saved_at) {
                parts.push('마지막 저장: ' + (meta.saved_by || '알 수 없음') + ' · ' + meta.saved_at);
            }
            $info.text(parts.join('  ·  ')).show();
        }

        // 초기 1회 렌더(현재 유형 기준).
        renderSavedInfo();

        // 캔버스 크기 한계를 넘지 않는 안전한 scale 을 계산한다.
        // 브라우저 캔버스는 한 변 최대 ~16384px(그리고 면적 제한)을 넘으면 빈(흰) 이미지가 된다.
        // 원하는 품질 scale(desired)을 쓰되, (폭/높이 × scale) 이 안전 한계를 넘으면 자동으로 낮춘다.
        //
        // [화질 최우선 설정]
        // - 한 변 한계는 데스크톱 브라우저 실한계(16384px)에 근접한 16000px 로 둔다.
        // - 면적 한계는 세로로 긴 상세페이지에서도 배율이 과하게 깎이지 않도록 크게(약 2.5억 px²) 둔다.
        //   (1080px 폭 기준, 높이 수천 px 까지도 desired 배율을 거의 그대로 유지)
        function computeSafeScale(cssWidth, cssHeight, desired){
            var MAX_SIDE = 16000;              // 한 변 최대 안전 픽셀 (데스크톱 브라우저 실한계 근접)
            var MAX_AREA = 250 * 1000 * 1000;  // 최대 면적(px^2) — 화질 우선(넉넉히)
            var w = Math.max(1, cssWidth);
            var h = Math.max(1, cssHeight);
            var s = desired || 3;
            s = Math.min(s, MAX_SIDE / w, MAX_SIDE / h);     // 한 변 한계
            s = Math.min(s, Math.sqrt(MAX_AREA / (w * h)));  // 면적 한계
            return Math.max(1, s);                            // 최소 1배는 보장
        }

        /* =====================================================================
         * 공통 이미지 저장 유틸 — "화질 최우선 + 10MB 상한 엄수"
         * - 무손실 PNG 를 우선한다. (글자/단색 배경이 가장 선명)
         * - PNG 가 10MB 를 넘으면 해상도만 완만히(0.92배씩) 줄여 10MB 이하가 되는
         *   가장 큰 PNG 를 저장한다. (텍스트 뭉개짐 없이 선명 유지)
         * - 아주 극단적으로 못 맞추는 경우에만 최후수단으로 고품질 JPEG 로 저장한다.
         * =================================================================== */
        var EXPORT_MAX_BYTES = 10 * 1024 * 1024; // 10MB 상한 (분할 다운로드 시 장당 과용량 방지)

        // 이미지 생성 로딩 오버레이 제어 (드롭다운 닫힘과 무관하게 항상 보인다)
        function showExportOverlay(text){
            $('#imageExportOverlayText').text(text || '이미지 생성 중…');
            $('#imageExportOverlay').css('display', 'flex');
        }
        function hideExportOverlay(){
            $('#imageExportOverlay').hide();
        }

        function canvasToBlob(cv, type, quality){
            return new Promise(function(resolve){
                cv.toBlob(function(b){ resolve(b); }, type, quality);
            });
        }

        // 캔버스를 비율 유지한 채 축소한 새 캔버스를 만든다.
        function downscaleCanvas(src, ratio){
            var out = document.createElement('canvas');
            out.width  = Math.max(1, Math.round(src.width  * ratio));
            out.height = Math.max(1, Math.round(src.height * ratio));
            var ctx = out.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, out.width, out.height);
            ctx.drawImage(src, 0, 0, out.width, out.height);
            return out;
        }

        /**
         * html2canvas 캡처 전 보정 — width:100% + height:auto 이미지의 세로 눌림 방지.
         *
         * html2canvas(1.4.1) 는 height:auto 인 이미지를 캡처할 때 높이를 0/오계산해
         * 세로로 찌그러진 이미지를 그리는 경우가 있다. 캡처 대상의 레이아웃이 확정된 뒤,
         * 각 이미지의 '현재 렌더된 폭 × natural 비율' 로 픽셀 높이를 명시적으로 박아 비율을 보존한다.
         *
         * @param {HTMLElement} root 캡처 대상 컨테이너
         */
        function fixImageHeights(root){
            if (!root) { return; }
            var imgs = root.querySelectorAll('img');
            Array.prototype.forEach.call(imgs, function(img){
                var nw = img.naturalWidth, nh = img.naturalHeight;
                if (!nw || !nh) { return; } // 자연 크기를 못 읽으면 건드리지 않음

                // object-fit(contain/cover) 로 비율박스 안에 맞춰 그리는 이미지는 건드리지 않는다.
                // (고정 비율 컨테이너에서 높이를 natural 비율로 덮어쓰면 레이아웃이 깨진다.)
                var cs = window.getComputedStyle(img);
                var objFit = cs.objectFit || cs.getPropertyValue('object-fit');
                if (objFit && objFit !== 'fill' && objFit !== 'none') { return; }

                // 현재 렌더된 폭(=width:100% 결과). clientWidth 가 0 이면 natural 폭으로 폴백.
                var renderW = img.clientWidth || img.getBoundingClientRect().width || nw;
                var h = Math.round(renderW * (nh / nw));
                if (h > 0) {
                    // auto 대신 실제 픽셀 높이를 고정 → html2canvas 가 비율을 깨지 못한다.
                    img.style.height = h + 'px';
                }
            });
            // 스타일 반영(reflow) 강제.
            void root.offsetHeight;
        }

        /**
         * fixImageHeights 로 박았던 인라인 height 를 제거해 원래(height:auto) 상태로 되돌린다.
         * 캡처 대상 DOM 이 재사용(모달 등)될 때 호출한다.
         *
         * @param {HTMLElement} root
         */
        function clearImageHeights(root){
            if (!root) { return; }
            Array.prototype.forEach.call(root.querySelectorAll('img'), function(img){
                img.style.height = '';
            });
        }

        function triggerDownload(blob, filename){
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.download = filename;
            link.href = url;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        async function exportCanvasUnder10MB(canvas, baseName){
            // 1) 원본 해상도 PNG 가 한도 이하면 그대로 저장 (최고 화질, 무손실)
            var png = await canvasToBlob(canvas, 'image/png');
            if (png && png.size <= EXPORT_MAX_BYTES) {
                triggerDownload(png, baseName + '.png');
                return;
            }

            // 2) 한도 초과 → 필요한 축소 비율을 추정(sqrt 면적비)해 PNG 를 다시 만든다.
            //    추정이 빗나가도 "한도 이하가 될 때까지" 반복해서 반드시 맞춘다. (실패로 끝내지 않음)
            var cur = canvas;
            var p = png;
            var guard = 0;
            while (p && p.size > EXPORT_MAX_BYTES && guard < 10) {
                guard++;
                var ratio = Math.sqrt(EXPORT_MAX_BYTES / p.size) * 0.92;
                ratio = Math.min(0.95, Math.max(0.3, ratio)); // 한 번에 과하게 줄지 않게
                cur = downscaleCanvas(cur, ratio);
                p = await canvasToBlob(cur, 'image/png');
                if (cur.width <= 500) { break; } // 너무 작아지면 JPEG 폴백으로
            }
            if (p && p.size <= EXPORT_MAX_BYTES) {
                triggerDownload(p, baseName + '.png');
                return;
            }

            // 3) PNG 로 못 맞추면 고품질 JPEG 로 전환 (품질을 낮춰가며 한도 이하 확보)
            var qualities = [0.95, 0.92, 0.9, 0.87, 0.85, 0.8];
            for (var q = 0; q < qualities.length; q++) {
                var jpg = await canvasToBlob(cur, 'image/jpeg', qualities[q]);
                if (jpg && jpg.size <= EXPORT_MAX_BYTES) {
                    triggerDownload(jpg, baseName + '.jpg');
                    return;
                }
            }

            // 4) 그래도 안 되면 캔버스를 더 줄이며 JPEG 재시도 (마지막 안전장치)
            var safety = 0;
            while (safety < 6) {
                safety++;
                cur = downscaleCanvas(cur, 0.8);
                var jpg2 = await canvasToBlob(cur, 'image/jpeg', 0.85);
                if (jpg2 && jpg2.size <= EXPORT_MAX_BYTES) {
                    triggerDownload(jpg2, baseName + '.jpg');
                    return;
                }
                if (cur.width <= 300) { break; }
            }

            await salert({text: '이미지를 10MB 이하로 저장하지 못했습니다. 내용을 줄인 뒤 다시 시도해주세요.', cancel: false});
        }

        // 다운로드 파일명 베이스를 만든다. (현재 저장본 제목 + 유형을 포함)
        //  - 예) prefix='rotable_상세페이지', 제목='11월 와인파티' → 'rotable_상세페이지_와인파티_11월 와인파티'
        //  - 저장본이 없으면(미저장) 제목 없이 유형만 붙인다.
        //  - 파일명에 못 쓰는 문자(\ / : * ? " < > |)는 _ 로 치환한다.
        function buildDownloadBaseName(prefix){
            var type  = currentType();
            var label = type === 'solo' ? '1:1소개팅' : '와인파티';
            var cur   = CURRENT[type] || {};
            var parts = [prefix, label];
            if (cur.id && cur.title) {
                parts.push(cur.title);
            }
            return parts.join('_').replace(/[\\/:*?"<>|]+/g, '_').replace(/\s+/g, ' ').trim();
        }

        // 다운로드 버튼에 로딩 상태(스피너/문구)를 입히고, 작업 중 중복 클릭을 막는다.
        async function withButtonLoading($btn, loadingText, task){
            var original = $btn.html();
            $btn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + (loadingText || '생성 중…'));
            // 스피너가 화면에 그려질 틈을 준 뒤 무거운 작업 시작.
            await new Promise(function(r){ setTimeout(r, 30); });
            try {
                await task();
            } finally {
                $btn.prop('disabled', false).html(original);
            }
        }

        var previewModal = null;

        function getHtml(){
            return (typeof tinymce !== 'undefined' && tinymce.get('detailHtmlEditor'))
                ? tinymce.get('detailHtmlEditor').getContent()
                : $('#detailHtmlEditor').val();
        }

        function setHtml(html){
            if (typeof tinymce !== 'undefined' && tinymce.get('detailHtmlEditor')) {
                tinymce.get('detailHtmlEditor').setContent(html);
            } else {
                $('#detailHtmlEditor').val(html);
            }
        }

        // 모달 미리보기 영역에 현재 HTML 반영
        function renderPreview(){
            var html = getHtml();
            // 다운로드 캡처와 '완전히 동일한' 렌더가 되도록, 다운로드에서 쓰는 하단 스페이서를
            // 미리보기에도 똑같이 붙인다. (WYSIWYG: 좌우·상하 패딩이 1:1로 일치)
            var withSpacer = (html || '')
                + '<div data-capture-spacer="1" style="width:100%; height:48px; background:#ffffff;"></div>';
            $('#detailPreview').html(html
                ? withSpacer
                : '<div style="padding:40px; text-align:center; color:#9ca3af;">내용이 비어 있습니다.<br>1열/2열 샘플을 눌러보세요.</div>');
            // 내용 반영 후 실제 1080px 렌더 높이에 맞춰 축소 비율을 다시 계산한다.
            fitPreviewScale();
        }

        // 미리보기를 "다운로드와 동일한 1080px 렌더"로 두고, 화면에선 비율 그대로 축소만 한다.
        //  - scaler 폭(모달 가용폭)을 1080 으로 나눈 값이 scale (최대 1배 = 확대 안 함).
        //  - reflow 없이 transform:scale 이라 좌우/상하 패딩이 다운로드와 100% 동일하게 보인다.
        //  - 축소된 만큼 scaler 높이를 줄여 모달 스크롤 길이를 맞춘다.
        function fitPreviewScale(){
            var $scaler  = $('#detailPreviewScaler');
            var $preview = $('#detailPreview');
            if (!$scaler.length || !$preview.length) { return; }

            var avail = $scaler.width() || 1080;        // 모달에서 쓸 수 있는 폭
            var scale = Math.min(1, avail / 1080);      // 1080 기준 축소 비율 (확대는 안 함)

            $preview.css('transform', 'scale(' + scale + ')');

            // transform 은 레이아웃 높이를 바꾸지 않으므로, scaler 높이를 '축소된 실제 높이'로 고정.
            var realH = $preview[0].scrollHeight || $preview[0].offsetHeight || 0;
            $scaler.css('height', (realH * scale) + 'px');
        }

        // 모달이 완전히 열린 뒤/창 크기 변경 시 비율 재계산 (열릴 때 폭이 확정됨).
        (function bindPreviewScaleEvents(){
            var el = document.getElementById('detailPreviewModal');
            if (el) {
                el.addEventListener('shown.bs.modal', fitPreviewScale);
            }
            var rT;
            $(window).on('resize', function(){
                clearTimeout(rT);
                rT = setTimeout(fitPreviewScale, 120);
            });
        })();

        function getModal(){
            if (previewModal) { return previewModal; }
            var modalEl = document.getElementById('detailPreviewModal');
            var ModalClass =
                (window.tabler && window.tabler.Modal) ||
                (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                (window.bootstrap && window.bootstrap.Modal) ||
                (window.coreui && window.coreui.Modal) ||
                window.Modal;
            if(!ModalClass){
                console.error('Modal 클래스를 찾을 수 없습니다.');
                return null;
            }
            previewModal = new ModalClass(modalEl);
            return previewModal;
        }

        // TinyMCE 초기화 (레이아웃에서 tinymce.min.js 로드됨)
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#detailHtmlEditor',
                height: 680,
                menubar: false,
                branding: false,
                plugins: 'lists link image table code fullscreen media emoticons autolink',
                toolbar: 'undo redo | blocks fontsize | bold italic underline forecolor backcolor | '
                    + 'alignleft aligncenter alignright | bullist numlist | link image media table | emoticons | code fullscreen',
                toolbar_mode: 'wrap',
                // ── 저장 시 커스텀 마커(설정 캐리어/명단)가 유실되지 않도록 정리 규칙 완화 ──
                // 기본 TinyMCE 는 ① 빈 요소(예: 빈 div)를 지우고 ② 모르는 data-* 속성을 떼어낸다.
                //  → 페이지 설정 캐리어(<div data-detail-settings style="display:none"></div>)가 '빈 div' 라
                //    저장 때 통째로 제거돼 설정값이 함께 저장되지 않는 문제가 있었다.
                //  → data-* 속성 전체를 허용하고, 설정 캐리어 div 는 '비어 있어도' 보존하도록 명시한다.
                extended_valid_elements: '*[*]',
                valid_children: '+body[style],+div[div|span|b|i|img|style]',
                // ※ non_empty_elements 는 TinyMCE 6 에서 제거된 옵션이라(설정해도 무시되고 콘솔 경고만 뜸) 지웠다.
                //   빈 캐리어 div 보존은 위 extended_valid_elements / verify_html:false 로 처리된다.
                keep_styles: true,
                verify_html: false,
                content_style: "@import url('https://fonts.googleapis.com/css2?family=Jua&family=Gothic+A1:wght@400;500;700;800;900&display=swap'); body{font-family:'Gothic A1','Pretendard','Apple SD Gothic Neo',sans-serif; font-weight:700; font-size:14px;}",
                // 에디터 초기화가 끝나면, 서버가 심어둔 저장본 HTML 의 '마지막 업데이트' 문구를
                // 지금 이 페이지에 접속한 시각으로 교체한다. (saveId 로 바로 접속한 경우 포함)
                setup: function(editor){
                    editor.on('init', function(){
                        var cur = editor.getContent();
                        if (cur) {
                            var next = applyUpdatedAtToSample(cur);
                            if (next !== cur) { editor.setContent(next); }
                        }
                        detailEditorReady = true;   // 복원 트리거(아래 ready 말미에서 대기)
                    });
                }
            });
        } else {
            detailEditorReady = true; // TinyMCE 없으면(폴백 textarea) 즉시 준비 완료로 간주
        }

        // 미리보기 버튼(상단/플로팅 공통) → 모달 열기
        $('.btn-open-preview').on('click', function(){
            renderPreview();
            var m = getModal();
            if (m) { m.show(); }
        });

        /* =====================================================================
         * 참여자 명단 편집기
         * - 에디터 HTML 안의 명단 그리드 요소([data-roster-grid])를 찾아 표로 보여주고,
         * - [명단 적용] 시 입력값으로 그 요소를 통째로 재생성해 되돌려 넣는다.
         * - 번호(N호)와 상단 인원수(남/여)는 행 수로 자동 계산된다.
         * - 요소(data 속성) 기준이라 에디터가 주석 마커를 지워도 안전하게 동작한다.
         * - 명단 편집 UI 는 '페이지 설정' 모달 안에 통합되어 있다.
         * =================================================================== */

        // HTML 전체를 분리된 DOM 으로 파싱해, 명단 그리드 요소([data-roster-grid])를 찾는다.
        // 주석 마커가 아니라 DOM 요소 기준이라, 에디터가 주석을 지워도 안전하게 동작한다.
        // 반환: { root: 분리된 래퍼 jQuery, $grid: 그리드 요소 jQuery } | null
        function locateRosterGrid(html){
            var $root = $('<div></div>').html(html);
            var $grid = $root.find('[data-roster-grid]').first();
            if ($grid.length === 0) { return null; }
            return { root: $root, grid: $grid };
        }

        // 그리드 요소($grid) → 편집용 데이터 구조로 파싱.
        // { config:{...}, cards:[ {title,date, males:[{height,job,age}], females:[...] } ] }
        function parseRoster($grid){
            if (!$grid || $grid.length === 0) { return null; }

            var config = {};
            try { config = JSON.parse($grid.attr('data-roster-config') || '{}'); } catch(e){ config = {}; }

            var cards = [];
            $grid.find('[data-roster-card]').each(function(){
                var $card = $(this);
                // 카드 구조: [data-roster-card] > div(테두리) > [행사명div, 헤더div, 명단flex div]
                // 명단 flex div = 테두리 div 의 마지막 자식. 그 안에 남(좌)/여(우) 두 컬럼.
                var $inner = $card.children('div').first();
                // 명단 컨테이너: data-roster-cols 우선, 없으면(예전 마크업) 분포도 블록을 뺀 마지막 div
                var $wrap  = $inner.children('[data-roster-cols]').first();
                if ($wrap.length === 0) { $wrap = $inner.children('div').not('[data-roster-dist]').last(); }
                var $cols  = $wrap.children('div');
                function readCol($col){
                    var people = [];
                    $col.children('div').each(function(){
                        var recruit = $(this).attr('data-recruit');
                        // 수동 모집중 자리(data-recruit="manual")는 명단 순서를 유지해 복원한다.
                        if (recruit === 'manual') { people.push({ recruit: true }); return; }
                        // 성비 자동 맞춤으로 채워진 자리(data-recruit="1")는 실제 인원이 아니므로 건너뛴다.
                        if (recruit) { return; }
                        var info = $(this).children('div').last().text(); // "177cm · 쇼핑몰 · 30대 초반"
                        var parts = info.split('·').map(function(x){ return x.trim(); });
                        var height = (parts[0] || '').replace(/cm$/i,'').trim();
                        people.push({
                            height: height,
                            job:    parts[1] || '',
                            age:    parts[2] || ''
                        });
                    });
                    return people;
                }
                cards.push({
                    title:   $card.attr('data-event-title') || '',
                    date:    $card.attr('data-event-date') || '',
                    males:   readCol($($cols.get(0))),
                    females: readCol($($cols.get(1)))
                });
            });

            return { config: config, cards: cards };
        }

        // 편집용 데이터 → 그리드 HTML 재생성 (Blade 와 동일 마크업 + data 속성 유지)
        function buildRosterGrid(data){
            var c = data.config || {};
            var cardWidth     = c.cardWidth     || '100%';
            var cellTitleSize = c.cellTitleSize || '29px';
            var cellDateSize  = c.cellDateSize  || '21px';
            var cellHeadSize  = c.cellHeadSize  || '30px';
            var cellNoSize    = c.cellNoSize    || '23px';
            var cellInfoSize  = c.cellInfoSize  || '26px';
            var cellHeight    = c.cellHeight    || '108px';
            var cellPadding   = c.cellPadding   || '10px';
            var balance       = c.balance === true; // 성비 자동 맞춤 제거됨: 기본 OFF
            var recruitLabel  = (c.recruitLabel && String(c.recruitLabel).trim()) || '모집중';

            // 행사명·날짜 크기: 상단 제목(64px / 부제 34px) 톤에 맞추되, 2열은 카드 폭이 절반이라 줄인다.
            var isTwoCol    = String(c.columns) === '2' || cardWidth === '50%';
            var evTitleSize = isTwoCol ? '38px' : '54px';
            var headSize    = isTwoCol ? '30px' : '42px'; // 남/여 헤더 — 행사 제목보다 살짝 작게 (저장된 config 값은 무시 → [적용] 시 즉시 반영)
            var evDateSize  = isTwoCol ? '24px' : '30px';
            function esc(s){
                return String(s == null ? '' : s)
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }
            // 실제 참여자 칸
            function personCell(p, i, borderColor){
                return ''
                  + '<div style="height:' + cellHeight + '; padding:' + cellPadding + '; border-bottom:2px solid ' + borderColor + '; text-align:center; display:flex; flex-direction:column; justify-content:center;">'
                  +   '<div style="font-weight:900; font-size:' + cellNoSize + '; color:#000000;">' + (i + 1) + '호</div>'
                  +   '<div style="color:#000000; font-weight:800; font-size:' + cellInfoSize + '; margin-top:4px; word-break:keep-all;">' + esc(p.height) + 'cm · ' + esc(p.job) + ' · ' + esc(p.age) + '</div>'
                  + '</div>';
            }
            // "모집중" 자리 칸.
            // - manual=true: 관리자가 수동으로 끼워 넣은 모집중 자리 (data-recruit="manual" → 재파싱 때 복원)
            // - manual=false: 성비 자동 맞춤으로 채운 자리 (data-recruit="1" → 재파싱 때 제외)
            function recruitCell(i, borderColor, manual){
                var flag = manual ? 'manual' : '1';
                return ''
                  + '<div data-recruit="' + flag + '" style="height:' + cellHeight + '; padding:' + cellPadding + '; border-bottom:2px dashed ' + borderColor + '; text-align:center; display:flex; flex-direction:column; justify-content:center; background:repeating-linear-gradient(45deg,#ffffff,#ffffff 8px,#fafafa 8px,#fafafa 16px);">'
                  +   '<div style="font-weight:900; font-size:' + cellNoSize + '; color:#9ca3af;">' + (i + 1) + '호</div>'
                  +   '<div style="color:#f472b6; font-weight:900; font-size:' + cellInfoSize + '; margin-top:4px; word-break:keep-all;">' + esc(recruitLabel) + ' 💗</div>'
                  + '</div>';
            }
            // 한 컬럼 렌더: 명단 순서대로 실제 인원/수동 모집중 자리를 그리고,
            // (balance 시) 부족한 만큼 뒤에 자동 모집중 자리로 채운다.
            function columnCells(people, total, borderColor){
                var out = '';
                people.forEach(function(p, i){
                    out += p && p.recruit ? recruitCell(i, borderColor, true) : personCell(p, i, borderColor);
                });
                for (var i = people.length; i < total; i++) { out += recruitCell(i, borderColor, false); }
                return out;
            }

            // 명단에 "적힌 그대로"의 나이 구간을 돌려준다. (임의로 초·중·후반을 만들지 않는다)
            //  - "30대"          → 30대        / "30대 초반·30초" → 30대 초반 (중반·후반 동일)
            //  - "29", "29세"    → 29세        / "97년생", "1997년생" → 97년생
            //  반환: { key: 정렬값(어린 순), label } | null(빈 값·해석 불가)
            function ageBucket(raw){
                var v = String(raw || '').replace(/\s+/g, '');
                if (!v) { return null; }

                var dec = v.match(/(\d)0대(초반|중반|후반|초|중|후)?/) || v.match(/(\d)0(초반|중반|후반|초|중|후)/);
                if (dec) {
                    var base = +dec[1] * 10;
                    if (!dec[2]) { return { key: base, label: base + '대' }; }   // 그냥 "30대"
                    var idx = /초/.test(dec[2]) ? 0 : (/중/.test(dec[2]) ? 1 : 2);
                    return { key: base + 2 + idx * 3, label: base + '대 ' + ['초반', '중반', '후반'][idx] };
                }

                var yr = v.match(/(\d{4}|\d{2})년생?/);
                if (yr) {
                    var short = yr[1].slice(-2);
                    return { key: ageSortValue(v), label: short + '년생' };
                }

                var age = ageSortValue(v);
                if (!isFinite(age)) { return null; }
                return { key: age, label: age + '세' };
            }

            // 키 구간: 5cm 단위 ("175~179cm"). 빈 값·해석 불가는 null
            function heightBucket(raw){
                var h = heightSortValue(raw);
                if (!isFinite(h) || h < 100 || h > 230) { return null; }
                var lo = Math.floor(h / 5) * 5;
                return { key: -lo, label: lo + '~' + (lo + 4) + 'cm' };   // key 음수 → 큰 키가 위로
            }

            // 남/여 분포도 공통 렌더러: 남(왼쪽·파랑) / 여(오른쪽·분홍) 막대.
            //  - bucketFn(person) → { key(작을수록 위), label } | null. 같은 label 끼리 합친다.
            //  - 모집중 자리·해석 불가 값은 제외. 집계할 값이 없으면 블록을 그리지 않는다.
            //  - 재파싱 때 명단으로 읽히지 않도록 data-roster-dist 로 표시한다.
            function distributionHtml(title, males, females, bucketFn){
                var buckets = {};
                function add(list, key){
                    (list || []).forEach(function(p){
                        if (!p || p.recruit) { return; }
                        var b = bucketFn(p);
                        if (!b) { return; }
                        if (!buckets[b.label]) { buckets[b.label] = { label: b.label, key: b.key, m: 0, f: 0 }; }
                        buckets[b.label][key]++;
                    });
                }
                add(males, 'm');
                add(females, 'f');
                var ids = Object.keys(buckets).sort(function(a, b){
                    return (buckets[a].key - buckets[b].key) || a.localeCompare(b, 'ko');
                });
                if (!ids.length) { return ''; }

                var max = 1;
                ids.forEach(function(id){ max = Math.max(max, buckets[id].m, buckets[id].f); });

                function bar(n, color, side){
                    if (!n) { return ''; }
                    var radius = side === 'left' ? '9px 0 0 9px' : '0 9px 9px 0';
                    return '<div style="height:20px; width:' + Math.round(n / max * 100) + '%; background:' + color + '; border-radius:' + radius + ';"></div>';
                }

                var out = '';
                out += '<div data-roster-dist="1" style="padding:10px 10px 12px; border-top:1px solid #e5e7eb; background:#ffffff;">';
                out +=   '<div style="text-align:center; font-weight:900; font-size:' + cellDateSize + '; margin-bottom:8px;">';
                out +=     esc(title) + ' <span style="color:#3b82f6;">■ 남</span> <span style="color:#ec4899;">■ 여</span>';
                out +=   '</div>';
                ids.forEach(function(id){
                    var b = buckets[id];
                    out += '<div style="display:flex; align-items:center; gap:6px; margin:5px 0;">';
                    out +=   '<div style="flex:1; display:flex; justify-content:flex-end; align-items:center; gap:6px;">';
                    out +=     '<span style="font-weight:900; font-size:' + cellDateSize + '; color:' + (b.m ? '#1d4ed8' : '#cbd5e1') + ';">' + b.m + '</span>';
                    out +=     bar(b.m, '#3b82f6', 'left');
                    out +=   '</div>';
                    out +=   '<div style="flex:0 0 auto; min-width:140px; text-align:center; font-weight:900; font-size:' + cellDateSize + '; color:#374151; white-space:nowrap;">' + esc(b.label) + '</div>';
                    out +=   '<div style="flex:1; display:flex; align-items:center; gap:6px;">';
                    out +=     bar(b.f, '#ec4899', 'right');
                    out +=     '<span style="font-weight:900; font-size:' + cellDateSize + '; color:' + (b.f ? '#be185d' : '#cbd5e1') + ';">' + b.f + '</span>';
                    out +=   '</div>';
                    out += '</div>';
                });
                out += '</div>';
                return out;
            }

            // config 를 속성(홑따옴표로 감쌈)에 안전하게 싣기 위해 작은따옴표를 엔티티로 치환.
            var configAttr = JSON.stringify(c).replace(/'/g, '&#39;');
            var html = '<div style="display:flex; flex-wrap:wrap;" data-roster-grid="1" data-roster-config=\'' + configAttr + '\'>';

            data.cards.forEach(function(card, idx){
                var males = card.males || [], females = card.females || [];
                // balance 켜지면 남/여 중 많은 쪽 '자리 수'(모집중 자리 포함)에 맞춰 모집중으로 채운다.
                var total = balance ? Math.max(males.length, females.length) : null;
                var maleTotal   = balance ? total : males.length;
                var femaleTotal = balance ? total : females.length;
                // 헤더에 표시하는 인원수는 '실제 참여자 수'만 센다. (수동/자동 모집중 자리는 제외)
                var maleHeadCount   = males.filter(function(p){ return !(p && p.recruit); }).length;
                var femaleHeadCount = females.filter(function(p){ return !(p && p.recruit); }).length;

                html += '<div style="width:' + cardWidth + '; box-sizing:border-box; padding:3px;"'
                      + ' data-roster-card="' + idx + '"'
                      + ' data-event-title="' + esc(card.title) + '"'
                      + ' data-event-date="' + esc(card.date) + '">';
                html +=   '<div style="border:2px solid #f2d4dc; border-radius:12px; overflow:hidden; height:100%;">';
                // 행사명 + 날짜 — "와인 & 블러쉬" 팔레트: 와인색 띠 + Jua 흰 행사명(상단 제목 크기 톤) + 블러쉬 알약 날짜.
                //  (남 더스티 네이비·여 로즈와 명도 차이가 커서 영역이 분명히 나뉜다)
                html +=     '<div style="background:linear-gradient(135deg,#7a1f3d,#b4385e); padding:20px 10px 18px; text-align:center;">';
                html +=       '<div style="font-family:\'Jua\',sans-serif; font-size:' + evTitleSize + '; font-weight:400; color:#ffffff; word-break:keep-all; line-height:1.25; letter-spacing:0.5px;">' + esc(card.title) + '</div>';
                html +=       '<div style="display:inline-block; margin-top:10px; padding:5px 18px; border-radius:999px; background:#fff4ef; color:#7a1f3d; font-size:' + evDateSize + '; font-weight:900; word-break:keep-all;">🗓 ' + esc(card.date) + '</div>';
                html +=     '</div>';
                // 남/여 헤더 (실제 참여자 수만 표시 — 모집중 더미는 제외)
                // (남/여 띠는 세로로 넉넉하게, 가운데 흰 구분선)
                html +=     '<div style="display:flex; font-size:' + headSize + '; font-weight:900; text-align:center;">';
                html +=       '<div style="width:50%; padding:14px 4px; background:#4a6fa5; color:#ffffff; border-right:2px solid #ffffff;">👨 남 ' + maleHeadCount + '</div>';
                html +=       '<div style="width:50%; padding:14px 4px; background:#e8708a; color:#ffffff;">👩 여 ' + femaleHeadCount + '</div>';
                html +=     '</div>';
                // 남/여 명단
                html +=     '<div style="display:flex;" data-roster-cols="1">';
                html +=       '<div style="width:50%; box-sizing:border-box; border-right:1px solid #d8e2ef; background:#f1f5fb;">';
                html +=         columnCells(males, maleTotal, '#d8e2ef');
                html +=       '</div>';
                html +=       '<div style="width:50%; box-sizing:border-box; background:#fff3f5;">';
                html +=         columnCells(females, femaleTotal, '#f6d5dc');
                html +=       '</div>';
                html +=     '</div>';
                // 남/여 나이·키 분포도 (명단 값으로 자동 집계)
                html +=     distributionHtml('📊 나이 분포', males, females, function(p){ return ageBucket(p.age); });
                html +=     distributionHtml('📏 키 분포', males, females, function(p){ return heightBucket(p.height); });
                html +=   '</div>';
                html += '</div>';
            });

            html += '</div>';
            return html;
        }

        // 편집기 UI 렌더링 (행사 카드별 남/여 표)
        function renderRosterEditor(data){
            var $body = $('#rosterEditorBody').empty();

            data.cards.forEach(function(card, ci){
                var $card = $('<div class="card mb-3 roster-ev-card"></div>');
                // 행사 카드 헤더: 분홍 띠 + 순번 뱃지 + 큰 입력칸 (행사명 · 날짜가 한눈에 보이도록)
                var $head = $('<div class="card-header roster-ev-head"></div>');
                $head.append(
                    '<div class="row g-2 align-items-end w-100 m-0">'
                    +   '<div class="col-12 d-flex align-items-center gap-2 mb-1 px-0">'
                    +     '<span class="badge bg-pink text-white fs-4">' + (ci + 1) + '번째 행사</span>'
                    +     '<button type="button" class="btn btn-sm btn-outline-pink ms-auto roster-import"><i class="ti ti-download me-1"></i>체크인 명단 불러오기</button>'
                    +   '</div>'
                    +   '<div class="col-12 col-md-7 ps-0">'
                    +     '<label class="form-label mb-1 fw-bold"><i class="ti ti-calendar-event text-pink me-1"></i>행사명</label>'
                    +     '<input type="text" class="form-control form-control-lg fw-bold roster-ev-title" placeholder="행사명" value="' + $('<i>').text(card.title).html() + '">'
                    +   '</div>'
                    +   '<div class="col-12 col-md-5 pe-0">'
                    +     '<label class="form-label mb-1 fw-bold"><i class="ti ti-clock text-pink me-1"></i>날짜</label>'
                    +     '<input type="text" class="form-control form-control-lg fw-bold roster-ev-date" placeholder="날짜 · 시간" value="' + $('<i>').text(card.date).html() + '">'
                    +   '</div>'
                    + '</div>'
                );
                $card.append($head);

                var $grid = $('<div class="card-body"><div class="row g-3"></div></div>');
                var $row  = $grid.find('.row');
                $row.append(buildGenderCol('male',   '남성', 'bg-blue-lt',   card.males));
                $row.append(buildGenderCol('female', '여성', 'bg-pink-lt',   card.females));
                $card.append($grid);

                $card.data('card-index', ci);
                $body.append($card);
            });

            initRosterDatePickers();
        }

        // 행사 날짜 입력칸에 달력(flatpickr) 적용. 표기 형식: "2026.11.07(토) 19:30"
        function initRosterDatePickers(){
            if(typeof flatpickr === 'undefined'){ return; }
            var WEEK = ['일','월','화','수','목','금','토'];
            var pad  = function(n){ return String(n).padStart(2, '0'); };

            $('#rosterEditorBody .roster-ev-date').each(function(){
                if(this._flatpickr){ return; }
                flatpickr(this, {
                    enableTime: true,
                    time_24hr:  true,
                    allowInput: true,
                    locale:     'ko',
                    dateFormat: 'Y.m.d(D) H:i',
                    formatDate: function(d){
                        return d.getFullYear() + '.' + pad(d.getMonth() + 1) + '.' + pad(d.getDate())
                             + '(' + WEEK[d.getDay()] + ') ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
                    },
                    parseDate: function(str){
                        var m = /(\d{4})\D+(\d{1,2})\D+(\d{1,2})(?:\D+?(\d{1,2}):(\d{2}))?/.exec(str || '');
                        return m ? new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0)) : undefined;
                    },
                    onReady: function(selectedDates, dateStr, instance){
                        var container = instance && instance.calendarContainer;
                        if(!container || container.querySelector('.fp-custom-controls')){ return; }

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
            });
        }

        // 한 성별 표 컬럼(헤더: 인원수 live, 바디: 행들)
        function buildGenderCol(gender, label, toneClass, people){
            var $col = $('<div class="col-md-6"></div>');
            $col.attr('data-gender', gender);
            $col.append(
                '<div class="d-flex align-items-center justify-content-between mb-2">'
                + '<span class="badge ' + toneClass + ' fs-3">' + label + ' <b class="roster-count">0</b>명</span>'
                + '<div class="btn-group">'
                +   '<button type="button" class="btn btn-sm btn-outline-secondary roster-add"><i class="ti ti-plus"></i> 참여자 추가</button>'
                +   '<button type="button" class="btn btn-sm btn-outline-pink roster-add-recruit"><i class="ti ti-heart"></i> 모집중 추가</button>'
                + '</div>'
                + '</div>'
            );
            var $table = $(
                '<table class="table table-sm table-vcenter mb-0">'
                + '<thead><tr>'
                + '<th style="width:64px;">#</th><th>키(cm)</th><th>직업</th><th>나이</th><th style="width:1%;"></th>'
                + '</tr></thead><tbody class="roster-rows"></tbody></table>'
            );
            var $tbody = $table.find('.roster-rows');
            (people || []).forEach(function(p){ $tbody.append(rosterRow(p)); });
            $col.append($table);
            return $col;
        }

        // 행 앞쪽: 드래그 핸들 + 번호(N호)
        var ROSTER_NO_CELL = '<td class="text-nowrap">'
            + '<i class="ti ti-grip-vertical roster-handle me-1" title="드래그해서 순서 변경" aria-hidden="true"></i>'
            + '<span class="roster-no text-secondary"></span></td>';
        // 행 뒤쪽: 위/아래 이동 + 삭제
        var ROSTER_ACTION_CELL = '<td class="text-nowrap"><div class="btn-group">'
            + '<button type="button" class="btn btn-sm btn-icon btn-ghost-secondary roster-up" title="위로" aria-label="위로 이동"><i class="ti ti-chevron-up"></i></button>'
            + '<button type="button" class="btn btn-sm btn-icon btn-ghost-secondary roster-down" title="아래로" aria-label="아래로 이동"><i class="ti ti-chevron-down"></i></button>'
            + '<button type="button" class="btn btn-sm btn-icon btn-ghost-danger roster-del" title="삭제" aria-label="삭제"><i class="ti ti-trash"></i></button>'
            + '</div></td>';

        function rosterRow(p){
            p = p || {};
            // "모집중" 자리 행: 입력칸 대신 '모집중' 표시만 둔다. (data-recruit 로 구분)
            if (p.recruit) {
                return $(
                    '<tr data-recruit="1" class="bg-pink-lt">'
                    + ROSTER_NO_CELL
                    + '<td colspan="3" class="fw-bold text-pink"><i class="ti ti-heart-filled me-1"></i>모집중 자리 💗</td>'
                    + ROSTER_ACTION_CELL
                    + '</tr>'
                );
            }
            return $(
                '<tr>'
                + ROSTER_NO_CELL
                + '<td><input type="text" class="form-control form-control-sm roster-height" value="' + $('<i>').text(p.height || '').html() + '"></td>'
                + '<td><input type="text" class="form-control form-control-sm roster-job" value="' + $('<i>').text(p.job || '').html() + '"></td>'
                + '<td><input type="text" class="form-control form-control-sm roster-age" value="' + $('<i>').text(p.age || '').html() + '"></td>'
                + ROSTER_ACTION_CELL
                + '</tr>'
            );
        }

        // 각 컬럼의 번호(N호)와 인원수 뱃지를 다시 매긴다.
        // 번호(N호)는 모집중 자리 포함 전체 순번, 인원수 뱃지는 실제 참여자(모집중 제외)만 센다.
        function renumberRoster($scope){
            ($scope || $('#rosterEditorBody')).find('[data-gender]').each(function(){
                var $col = $(this);
                var $rows = $col.find('.roster-rows > tr');
                $rows.each(function(i){
                    $(this).find('.roster-no').text((i + 1) + '호');
                    $(this).find('.roster-up').prop('disabled', i === 0);
                    $(this).find('.roster-down').prop('disabled', i === $rows.length - 1);
                });
                var realCount = $rows.filter(function(){ return !$(this).attr('data-recruit'); }).length;
                $col.find('.roster-count').text(realCount);
            });
        }

        // 편집기 → 데이터 수집
        function collectRoster(baseConfig){
            var cards = [];
            $('#rosterEditorBody > .card').each(function(){
                var $card = $(this);
                function readCol(gender){
                    var arr = [];
                    $card.find('[data-gender="' + gender + '"] .roster-rows > tr').each(function(){
                        // "모집중" 자리 행은 입력칸이 없으므로 recruit 플래그만 담는다.
                        if ($(this).attr('data-recruit')) {
                            arr.push({ recruit: true });
                            return;
                        }
                        arr.push({
                            height: $(this).find('.roster-height').val().trim(),
                            job:    $(this).find('.roster-job').val().trim(),
                            age:    $(this).find('.roster-age').val().trim()
                        });
                    });
                    return arr;
                }
                cards.push({
                    title:   $card.find('.roster-ev-title').val(),
                    date:    $card.find('.roster-ev-date').val(),
                    males:   readCol('male'),
                    females: readCol('female')
                });
            });
            return { config: baseConfig, cards: cards };
        }

        var rosterBaseConfig = {};

        // 명단 편집 섹션을 현재 에디터 HTML 기준으로 채운다. (통합 모달이 열릴 때 호출)
        function populateRosterEditor(){
            var loc  = locateRosterGrid(getHtml());
            var data = loc ? parseRoster(loc.grid) : null;

            if (!data || !data.cards.length) {
                $('#rosterEditorBody').empty();
                $('#rosterEditorEmpty').show();
            } else {
                $('#rosterEditorEmpty').hide();
                rosterBaseConfig = data.config || {};
                renderRosterEditor(data);
                initRosterSortable();
                renumberRoster();
            }
        }

        // 각 성별 표(tbody)에 드래그 정렬을 건다. 같은 표 안에서만 이동(남↔여 섞이지 않음).
        function initRosterSortable(){
            if (typeof Sortable === 'undefined') { return; } // CDN 실패 시 위/아래 버튼만 사용
            $('#rosterEditorBody .roster-rows').each(function(){
                Sortable.create(this, {
                    handle: '.roster-handle',
                    animation: 150,
                    ghostClass: 'roster-ghost',
                    chosenClass: 'roster-chosen',
                    onEnd: function(evt){ renumberRoster($(evt.to).closest('.card')); }
                });
            });
        }

        // ─── [적용] 시 명단 자동 정렬: ① 나이 오름차순 → ② 키 내림차순 (모집중 자리는 맨 아래) ───
        // 나이 표기를 비교용 숫자(나이)로 바꾼다. 해석 불가/빈 값은 Infinity(맨 뒤).
        //  - "29", "29세", "만 29"          → 29
        //  - "97년생", "1997", "97"(2자리 90~99 / 00~현재) → 출생연도 기준 나이
        //  - "20대 초반/중반/후반", "20대"   → 22 / 25 / 28 / 25
        //  - "20후", "30초" 같은 줄임말도 인식
        function ageSortValue(raw){
            var v = String(raw || '').replace(/\s+/g, '');
            if (!v) { return Infinity; }
            var nowY = new Date().getFullYear();

            var dec = v.match(/(\d)0대?(초반|중반|후반|초|중|후)?/);
            if (dec && /대|초|중|후/.test(v)) {
                var base = +dec[1] * 10;
                var part = dec[2] || '';
                var off  = /초/.test(part) ? 2 : (/후/.test(part) ? 8 : 5);
                return base + off;
            }

            var yr = v.match(/(\d{4}|\d{2})년생?/) || v.match(/^(\d{4})$/);
            if (yr) {
                var y = +yr[1];
                if (yr[1].length === 2) { y += (y > nowY % 100 ? 1900 : 2000); }
                return nowY - y;
            }

            var n = v.match(/\d+/);
            if (n) {
                var num = +n[0];
                // 2자리지만 나이 범위를 벗어나면(예: 95) 출생연도로 본다.
                if (num >= 60 && num <= 99) { return nowY - (1900 + num); }
                return num;
            }
            return Infinity;
        }
        // 키 → 숫자(cm). 빈 값/해석 불가는 -Infinity (내림차순에서 맨 뒤)
        function heightSortValue(raw){
            var m = String(raw || '').match(/\d+(\.\d+)?/);
            return m ? parseFloat(m[0]) : -Infinity;
        }
        // 편집기의 행사 카드별 남/여 표를 정렬한다. (같은 값이면 기존 순서 유지)
        function sortRosterEditor(){
            $('#rosterEditorBody [data-gender]').each(function(){
                var $tbody = $(this).find('.roster-rows');
                var rows = $tbody.children('tr').get().map(function(tr, i){
                    var $tr = $(tr);
                    var recruit = !!$tr.attr('data-recruit');
                    return {
                        el: tr, i: i, recruit: recruit,
                        age: recruit ? Infinity : ageSortValue($tr.find('.roster-age').val()),
                        h:   recruit ? -Infinity : heightSortValue($tr.find('.roster-height').val())
                    };
                });
                rows.sort(function(a, b){
                    if (a.recruit !== b.recruit) { return a.recruit ? 1 : -1; }
                    if (a.age !== b.age) { return a.age < b.age ? -1 : 1; }
                    if (a.h !== b.h) { return a.h > b.h ? -1 : 1; }
                    return a.i - b.i;
                });
                rows.forEach(function(r){ $tbody.append(r.el); });
            });
            renumberRoster();
        }

        // 출생연도 → 명단 나이 표기. 한국식 나이(올해 - 출생연도 + 1) 기준으로 대(代)를 계산한다.
        //  - decade: "30대" / half: "30대 초반·중반·후반"(끝자리 0~3 초반, 4~6 중반, 7~9 후반) / year: "97년생"
        function rosterAgeText(birthYear, type, fallback){
            var by = parseInt(birthYear, 10);
            if (isNaN(by)) { return fallback || ''; }
            if (type === 'year') { return String(by).slice(-2) + '년생'; }
            var age    = new Date().getFullYear() - by + 1;
            var decade = Math.floor(age / 10) * 10;
            if (type === 'half') {
                var d = age % 10;
                return decade + '대 ' + (d <= 3 ? '초반' : (d <= 6 ? '중반' : '후반'));
            }
            return decade + '대';
        }

        // 체크인 명단 불러오기 — 선택한 행사의 체크인 명단(키·년생·직업)으로 이 행사 카드의 남/여 표를 채운다.
        //  - 행사명·날짜도 함께 채운다. 모집중 자리(수동)는 그대로 둔다.
        var ROSTER_SOURCE_EVENTS_URL = "{{ route('admin.rotation.detail.rosterSource.events') }}";
        var ROSTER_SOURCE_PEOPLE_TPL = "{{ route('admin.rotation.detail.rosterSource.people', ['id' => '__ID__']) }}";

        $('#rosterEditorBody').on('click', '.roster-import', async function(){
            var $card = $(this).closest('.roster-ev-card');
            var events;
            try {
                events = (await $.getJSON(ROSTER_SOURCE_EVENTS_URL)).items || [];
            } catch (e) {
                await salert({text: '행사 목록을 불러오지 못했습니다.', cancel: false});
                return;
            }
            if (!events.length) {
                await salert({text: '불러올 행사가 없습니다.', cancel: false});
                return;
            }
            var esc2 = function(s){ return $('<i>').text(s == null ? '' : s).html(); };
            var opts = events.map(function(ev){
                return '<option value="' + ev.id + '">' + esc2(ev.title) + ' · ' + esc2(ev.date)
                     + ' (명단 ' + ev.total + '명 / 프로필 ' + ev.filled + '명)</option>';
            }).join('');
            var pick = await Swal.fire({
                title: '체크인 명단 불러오기',
                html: '<div style="text-align:left;font-size:.85rem;color:#475569;margin-bottom:6px;">'
                    + '선택한 행사의 체크인 명단으로 이 카드의 남/여 명단을 <b>덮어씁니다.</b><br>(나이 → 키 순 정렬 · 모집중 자리 성별당 2개 추가)</div>'
                    + '<select id="swal-roster-event" class="swal2-select" style="width:100%;margin:0;">' + opts + '</select>'
                    + '<div style="text-align:left;font-size:.85rem;color:#475569;font-weight:700;margin:12px 0 4px;">나이 표기</div>'
                    + '<select id="swal-roster-age" class="swal2-select" style="width:100%;margin:0;">'
                    +   '<option value="decade" selected>30대 (기본)</option>'
                    +   '<option value="half">30대 초반 / 중반 / 후반</option>'
                    +   '<option value="year">97년생</option>'
                    + '</select>',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: '불러오기',
                cancelButtonText: '취소',
                preConfirm: function(){
                    return {
                        eventId: document.getElementById('swal-roster-event').value,
                        ageType: document.getElementById('swal-roster-age').value
                    };
                }
            });
            if (!pick.isConfirmed) { return; }

            var data;
            try {
                data = await $.getJSON(ROSTER_SOURCE_PEOPLE_TPL.replace('__ID__', pick.value.eventId));
            } catch (e) {
                await salert({text: '명단을 불러오지 못했습니다.', cancel: false});
                return;
            }

            $card.find('.roster-ev-title').val(data.title || '');
            var dateEl = $card.find('.roster-ev-date').get(0);
            if (dateEl && dateEl._flatpickr && data.date) {
                dateEl._flatpickr.setDate(data.date, false);
            } else {
                $card.find('.roster-ev-date').val(data.date || '');
            }

            // 남/여 각각: 나이 어린 순 → 키 큰 순으로 정렬해 채우고, 끝에 여유 "모집중" 자리를 2개씩 둔다.
            var RECRUIT_SPARE = 2;
            [['male', data.males], ['female', data.females]].forEach(function(pair){
                var $rows = $card.find('[data-gender="' + pair[0] + '"] .roster-rows');
                $rows.empty();
                var people = (pair[1] || []).map(function(p, i){
                    var row = Object.assign({}, p, { age: rosterAgeText(p.birth_year, pick.value.ageType, p.age) });
                    return { row: row, i: i, age: ageSortValue(p.age || row.age), h: heightSortValue(p.height) };
                });
                people.sort(function(a, b){
                    if (a.age !== b.age) { return a.age < b.age ? -1 : 1; }
                    if (a.h !== b.h) { return a.h > b.h ? -1 : 1; }
                    return a.i - b.i;
                });
                people.forEach(function(x){ $rows.append(rosterRow(x.row)); });
                for (var k = 0; k < RECRUIT_SPARE; k++) { $rows.append(rosterRow({ recruit: true })); }
            });
            renumberRoster($card);
            await salert({text: '체크인 명단을 불러왔어요. 나이 → 키 순으로 정렬했고, 모집중 자리를 성별마다 2개씩 넣었어요.', cancel: false});
        });

        // 위/아래 버튼으로 한 칸씩 이동
        $('#rosterEditorBody').on('click', '.roster-up, .roster-down', function(){
            var $tr = $(this).closest('tr');
            if ($(this).hasClass('roster-up')) {
                $tr.prev('tr').before($tr);
            } else {
                $tr.next('tr').after($tr);
            }
            renumberRoster($tr.closest('.card'));
        });

        // 행 추가/삭제 (이벤트 위임)
        // 참여자 추가: "모집중" 자리가 있으면 그 자리들 위(앞)에 끼워 넣고, 없으면 맨 뒤에 추가.
        $('#rosterEditorBody').on('click', '.roster-add', function(){
            var $rows = $(this).closest('[data-gender]').find('.roster-rows');
            var $firstRecruit = $rows.find('> tr[data-recruit]').first();
            var $newRow = rosterRow({});
            if ($firstRecruit.length) {
                $firstRecruit.before($newRow);   // 모집중 자리 위에 생성
            } else {
                $rows.append($newRow);           // 모집중 자리 없으면 맨 아래
            }
            renumberRoster($(this).closest('.card'));
        });
        // "모집중" 자리 행 추가 (실제 참여자 대신 모집중 더미 자리를 수동으로 끼워 넣는다)
        $('#rosterEditorBody').on('click', '.roster-add-recruit', function(){
            $(this).closest('[data-gender]').find('.roster-rows').append(rosterRow({recruit: true}));
            renumberRoster($(this).closest('.card'));
        });
        $('#rosterEditorBody').on('click', '.roster-del', function(){
            var $card = $(this).closest('.card');
            $(this).closest('tr').remove();
            renumberRoster($card);
        });

        // 명단 편집 섹션 → 현재 HTML 의 그리드를 재생성해 반영한 새 HTML 을 돌려준다.
        // 명단 영역이 없으면 입력 HTML 을 그대로 반환한다. (설정만 바꾸는 경우 안전)
        function applyRosterToHtml(html){
            var loc = locateRosterGrid(html);
            if (!loc) { return html; }
            // 성비 자동 맞춤은 제거됨: 자동 채움 없이 명단 그대로 반영한다.
            // (부족한 쪽은 '모집중 추가' 버튼으로 직접 끼워 넣는다.)
            var cfg = Object.assign({}, rosterBaseConfig, {
                balance:      false,
                recruitLabel: (rosterBaseConfig.recruitLabel || '모집중').trim() || '모집중'
            });
            var data    = collectRoster(cfg);
            var newGrid = buildRosterGrid(data);
            // 분리된 DOM 에서 기존 그리드 요소를 새 그리드로 교체한 뒤 전체 HTML 직렬화.
            loc.grid.replaceWith(newGrid);
            return loc.root.html();
        }

        // 유형 전환: 현재 작업 내용을 활성 유형에 보관하고, 대상 유형 저장본을 에디터에 로드
        $('input[name="sampleType"]').on('change', function(){
            var next = currentType();
            if (next === activeType) { return; }
            SAVED[activeType] = getHtml();   // 현재 편집 내용 보관
            setHtml(SAVED[next] || '');      // 대상 유형 내용 로드
            activeType = next;
            renderSavedInfo();               // 선택 유형의 저장 정보로 갱신
            syncUrl(true);                   // 주소 쿼리파라미터(type/saveId) 갱신
        });

        // 샘플 HTML 의 명단을 "현재 에디터에 작성된 명단"으로 바꿔서 돌려준다.
        // - 현재 에디터에 명단(data-roster-grid)이 있으면 그 남/여 인원 + 행사명/날짜를 추출해 새 샘플에 채운다.
        // - 행사명/날짜(시간)는 같은 위치(카드 인덱스)의 현재 값을 유지하고, 없으면 첫 카드 값으로 채운다.
        // - 현재 명단이 없거나 비어 있으면 샘플 기본(더미) 명단을 그대로 둔다.
        function applyCurrentRosterToSample(sampleHtml){
            // 1) 현재 에디터 명단 추출
            var $cur = $('<div></div>').html(getHtml());
            var curData = parseRoster($cur.find('[data-roster-grid]').first());
            var curCards = (curData && curData.cards && curData.cards.length) ? curData.cards : null;
            var curCard  = curCards ? curCards[0] : null;
            // 어느 카드든 인원이 한 명이라도 있으면 "현재 명단 있음"
            var hasCurrent = !!curCards && curCards.some(function(c){
                return (c.males && c.males.length) || (c.females && c.females.length);
            });
            if (!hasCurrent) { return sampleHtml; } // 현재 명단 없으면 샘플 그대로

            // 2) 샘플 HTML 파싱
            var $sample = $('<div></div>').html(sampleHtml);
            var $grid = $sample.find('[data-roster-grid]').first();
            if ($grid.length === 0) { return sampleHtml; } // 샘플에 명단 영역 없으면 그대로

            var sampleData = parseRoster($grid);
            if (!sampleData || !sampleData.cards.length) { return sampleHtml; }

            // 3) 샘플의 각 카드에 "같은 위치(인덱스)의 현재 카드" 값을 통째로 채운다. (명단·행사명·날짜)
            //    - 현재 카드가 그 위치에 없을 때만(예: 1열 → 2열) 첫 카드 값으로 채워 빈 카드가 생기지 않게 한다.
            //    ※ 예전엔 명단을 항상 첫 카드로 덮어써서, 2열 샘플을 불러오면 두 행사 명단이 같아지는 문제가 있었다.
            sampleData.cards.forEach(function(card, i){
                var src = curCards[i] || curCard;
                card.males   = (src.males   || []).slice();
                card.females = (src.females || []).slice();
                card.title   = src.title != null ? src.title : card.title;
                card.date    = src.date  != null ? src.date  : card.date;
            });

            // 4) 새 그리드로 다시 그려 샘플 HTML 의 명단 영역만 치환
            $grid.replaceWith(buildRosterGrid(sampleData));
            return $sample.html();
        }

        /* =====================================================================
         * 페이지 설정 (행사별 남/녀 나이 · 남/녀 키) — 방안 B
         * - 에디터 HTML 안의 data-detail-settings(JSON = {events:[...]}) 로 현재 설정을 읽고,
         *   data-setting-block="age|maleHeight|femaleHeight" 컨테이너를 행사 수에 맞춰 다시 그린다.
         * - 2열(행사 2개)이면 각 블록에 행사별 한 줄씩 노출("N회 · " 라벨).
         * - 구버전 flat 캐리어는 events[0] 로 승격(하위호환).
         * - 참여자 명단과 동일하게 "요소/data 속성 기준"이라 주석이 지워져도 안전.
         * - 샘플을 다시 불러와도 현재 설정이 그대로 따라오게 한다. (applyCurrentSettingsToSample)
         * =================================================================== */
        var settingsModal = null;
        // 한 행사(event)의 설정 키 목록과 기본값. (data-detail-settings 가 없을 때의 폴백)
        // 나이는 숫자(세) 최소/최대로 보관하고, 표기용 년생 텍스트(maleAgeText/femaleAgeText)는 파생값이다.
        var SETTING_EVENT_KEYS = ['maleAgeMin', 'maleAgeMax', 'femaleAgeMin', 'femaleAgeMax',
                                  'maleAgeText', 'femaleAgeText', 'maleHeight', 'femaleHeight'];

        // 남성 키 기본값 — 빈 값이면 항상 이 값으로 보이게 한다. (요구사항: 디폴트 173cm)
        var DEFAULT_MALE_HEIGHT = '173cm';

        // 이전 로테이블 기록(records) 한 건의 키. (페이지 전역 데이터 — 행사별 events 와 별개)
        var RECORD_KEYS = ['date', 'title', 'people', 'couples'];

        // 기록 배열 정규화: 각 행의 누락 키를 빈 문자열로 채우고, 완전히 빈 행은 버린다.
        function normalizeRecords(list){
            if (!Array.isArray(list)) { return []; }
            return list.map(function(r){
                var o = (r && typeof r === 'object') ? r : {};
                var out = {};
                RECORD_KEYS.forEach(function(k){ out[k] = (o[k] == null ? '' : String(o[k])).trim(); });
                return out;
            }).filter(function(r){
                return RECORD_KEYS.some(function(k){ return r[k] !== ''; });
            });
        }

        // 한 행사 설정 객체의 누락 키를 빈 문자열로 채운다.
        //  - 단, 남성 키(maleHeight)는 비면 기본값(173cm)으로 채워 "항상 노출"되게 한다.
        function normalizeEventSetting(e){
            var s = (e && typeof e === 'object') ? Object.assign({}, e) : {};
            SETTING_EVENT_KEYS.forEach(function(k){ if (s[k] == null) { s[k] = ''; } });
            if (String(s.maleHeight || '').trim() === '') { s.maleHeight = DEFAULT_MALE_HEIGHT; }
            return s;
        }

        // 임의의 설정 객체를 "행사 배열({events:[...]})" 형태로 표준화한다.
        //  - 신버전: { events:[ {...}, {...} ] } 그대로.
        //  - 구버전(flat): { maleAgeMin, ... } → events:[그 객체] 로 승격(하위호환).
        //  - 그 외/비어있음: events:[] (호출부에서 행사 수에 맞춰 채움).
        function toEventsModel(raw){
            var obj = (raw && typeof raw === 'object') ? raw : {};
            var records = normalizeRecords(obj.records);
            if (Array.isArray(obj.events)) {
                return { events: obj.events.map(normalizeEventSetting), records: records };
            }
            // flat 구버전: 나이/키 키가 하나라도 있으면 단일 행사로 승격.
            var hasFlat = SETTING_EVENT_KEYS.some(function(k){ return obj[k] != null && obj[k] !== ''; });
            if (hasFlat) { return { events: [normalizeEventSetting(obj)], records: records }; }
            return { events: [], records: records };
        }

        // 행사 수(count)에 맞춰 events 배열 길이를 맞춘다. (부족하면 첫 설정 복제, 없으면 빈 설정)
        function fitEventsToCount(model, count){
            var evs = (model && Array.isArray(model.events)) ? model.events.slice() : [];
            count = Math.max(1, count || 1);
            var base = evs.length ? normalizeEventSetting(evs[0]) : normalizeEventSetting({});
            while (evs.length < count) { evs.push(Object.assign({}, base)); }
            if (evs.length > count) { evs = evs.slice(0, count); }
            return { events: evs.map(normalizeEventSetting), records: normalizeRecords(model && model.records) };
        }

        // 한국식 나이(세) → 출생연도 뒤 2자리. (Blade $yy 와 동일: 올해 - 나이 + 1)
        var SETTINGS_THIS_YEAR = {{ (int) now()->year }};
        function settingYy(age){
            var n = parseInt(age, 10);
            if (isNaN(n)) { return ''; }
            var yr = (SETTINGS_THIS_YEAR - n + 1) % 100;
            if (yr < 0) { yr += 100; }
            return (yr < 10 ? '0' : '') + yr;
        }
        // 최소/최대 나이(세) → "OO~OO년생". (나이 많을수록 출생연도 작음 → 최대나이~최소나이)
        function settingAgeText(minAge, maxAge){
            var lo = parseInt(minAge, 10);
            var hi = parseInt(maxAge, 10);
            if (isNaN(lo) || isNaN(hi)) { return ''; }
            if (lo > hi) { var t = lo; lo = hi; hi = t; }
            return settingYy(hi) + '~' + settingYy(lo) + '년생';
        }

        // 출생연도 뒤 2자리(yy) → 나이(세) 역산. (올해 기준 한국식)
        //  - yy 는 1900/2000 어느 쪽인지 모호하므로 "결과 나이가 15~80 범위"가 되는 쪽을 택한다.
        function settingAgeFromYy(yy){
            var n = parseInt(yy, 10);
            if (isNaN(n)) { return null; }
            // 2000년대 출생으로 가정한 나이 / 1900년대 출생으로 가정한 나이
            var age2000 = SETTINGS_THIS_YEAR - (2000 + n) + 1;
            var age1900 = SETTINGS_THIS_YEAR - (1900 + n) + 1;
            var ok = function(a){ return a >= 15 && a <= 80; };
            if (ok(age2000) && !ok(age1900)) { return age2000; }
            if (ok(age1900) && !ok(age2000)) { return age1900; }
            // 둘 다 유효하면 더 'young' 쪽(2000년대)을 우선.
            return ok(age2000) ? age2000 : (ok(age1900) ? age1900 : age2000);
        }

        // "88~02년생" 같은 텍스트 → {min, max} 나이(세). 파싱 실패 시 빈 문자열.
        //  - 표기는 "최대나이(=작은 yy) ~ 최소나이(=큰 yy)" 순이므로, 역산 후 min/max 로 정렬한다.
        function settingAgesFromText(text){
            var m = String(text || '').match(/(\d{1,2})\s*~\s*(\d{1,2})/);
            if (!m) { return { min: '', max: '' }; }
            var a = settingAgeFromYy(m[1]);
            var b = settingAgeFromYy(m[2]);
            if (a == null || b == null) { return { min: '', max: '' }; }
            return { min: String(Math.min(a, b)), max: String(Math.max(a, b)) };
        }

        // 캐리어/화면에서 "행사 배열 설정"을 복원한다.
        //  - 1순위: data-detail-settings 캐리어(JSON) → toEventsModel 로 표준화(구버전 flat 자동 승격).
        //  - 2순위(캐리어 없음/비었음): 화면의 행사별 블록(data-setting-block)에서 보이는 값으로 복원.
        //  - 입력: 분리된 DOM($root). 출력: { events:[ {...}, ... ] }
        function readSettingsFromDom($root){
            // 1) 캐리어 우선
            var raw = {};
            var $carrier = $root.find('[data-detail-settings]').first();
            if ($carrier.length) {
                try { raw = JSON.parse($carrier.attr('data-detail-settings') || '{}'); } catch(e){ raw = {}; }
            }
            var model = toEventsModel(raw);
            if (model.events.length) {
                // 구 저장본: 캐리어에 events 는 있으나 records 가 없을 수 있음 → 화면 기록표에서 보강.
                if (!model.records || !model.records.length) {
                    model.records = readRecordsFromDom($root);
                }
                return model;
            }

            // 캐리어에 records 가 없었다면(또는 캐리어 자체가 없으면) 화면 기록표에서 복원.
            if (!model.records || !model.records.length) {
                model.records = readRecordsFromDom($root);
            }

            // 2) 캐리어가 없거나 비었으면 화면 블록에서 복원.
            //    나이 블록의 각 줄(행사 1줄씩)을 파싱해 행사 수를 추정한다.
            var evs = [];
            // 줄 앞의 "- " 와 "행사명 · " 라벨(있으면)을 떼어 실제 값만 남긴다.
            //  - 멀티 행사일 때만 "행사명 · " 접두가 붙으므로, 첫 " · " 앞부분을 라벨로 보고 제거.
            var stripPrefix = function(t){
                var s = String(t || '').replace(/^\s*-\s*/, '');
                var idx = s.indexOf(' · ');
                if (idx >= 0) { s = s.slice(idx + 3); }
                return s.trim();
            };
            var $ageBlock = $root.find('[data-setting-block="age"]').first();
            var $ageLines = $ageBlock.children('div');
            var $maleBlock = $root.find('[data-setting-block="maleHeight"]').first();
            var $femaleBlock = $root.find('[data-setting-block="femaleHeight"]').first();
            var stripHeight = function(t){
                return stripPrefix(t).replace(/^\s*신장\s*/, '').replace(/\s*이상\s*$/, '').trim();
            };
            var maleHeights = $maleBlock.children('div').map(function(){ return stripHeight($(this).text()); }).get();
            var femaleHeights = $femaleBlock.children('div').map(function(){ return stripHeight($(this).text()); }).get();

            $ageLines.each(function(i){
                var line = stripPrefix($(this).text());  // "남성 88~02년생 / 여성 88~02년생"
                var parts = line.split('/');
                var ma = settingAgesFromText(parts[0] || '');
                var fa = settingAgesFromText(parts[1] || '');
                evs.push(normalizeEventSetting({
                    maleAgeMin: ma.min, maleAgeMax: ma.max,
                    femaleAgeMin: fa.min, femaleAgeMax: fa.max,
                    maleHeight:  maleHeights[i] || '',
                    femaleHeight: femaleHeights[i] || ''
                }));
            });

            if (!evs.length) { evs.push(normalizeEventSetting({})); }
            return { events: evs, records: model.records || [] };
        }

        // 화면의 기록표 블록([data-setting-block="records"])에서 기록 행들을 복원한다.
        //  - 각 행(data-record-row)의 4개 셀(날짜/행사명/인원/매칭) 텍스트를 읽는다.
        function readRecordsFromDom($root){
            var $block = $root.find('[data-setting-block="records"]').first();
            if (!$block.length) { return []; }
            var rows = [];
            $block.children('[data-record-row]').each(function(){
                var cells = $(this).children('div').map(function(){ return $(this).text().trim(); }).get();
                rows.push({
                    date:    cells[0] || '',
                    title:   cells[1] || '',
                    people:  cells[2] || '',
                    couples: cells[3] || ''
                });
            });
            return normalizeRecords(rows);
        }

        function getSettingsModal(){
            if (settingsModal) { return settingsModal; }
            var el = document.getElementById('detailSettingsModal');
            var ModalClass =
                (window.tabler && window.tabler.Modal) ||
                (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                (window.bootstrap && window.bootstrap.Modal) ||
                (window.coreui && window.coreui.Modal) ||
                window.Modal;
            if(!ModalClass){ console.error('Modal 클래스를 찾을 수 없습니다.'); return null; }
            settingsModal = new ModalClass(el);
            return settingsModal;
        }

        // HTML 전체를 분리된 DOM 으로 파싱해, 설정 캐리어([data-detail-settings])를 찾는다.
        // 반환: { root: 분리된 래퍼 jQuery, carrier: 캐리어 요소 jQuery } | null
        function locateSettings(html){
            var $root = $('<div></div>').html(html);
            var $carrier = $root.find('[data-detail-settings]').first();
            if ($carrier.length === 0) { return null; }
            return { root: $root, carrier: $carrier };
        }

        // 캐리어의 data-detail-settings(JSON) → 행사 배열 설정 모델로 표준화.
        function parseSettings($carrier){
            var raw = {};
            try { raw = JSON.parse($carrier.attr('data-detail-settings') || '{}'); } catch(e){ raw = {}; }
            return toEventsModel(raw);
        }

        // 행사 라벨: 참여 조건에 "정확한 행사명 전체"를 그대로 쓴다. (제목 없으면 "N회")
        function settingEventLabel(title, i){
            var t = String(title || '').trim();
            return t !== '' ? t : ((i + 1) + '회');
        }

        // $root(분리된 DOM) 안 명단 그리드에서 행사 제목 배열을 뽑는다. (설정 블록 라벨/카드 매칭용)
        function eventTitlesFromDom($root){
            var titles = [];
            $root.find('[data-roster-grid]').first().find('[data-roster-card]').each(function(){
                titles.push($(this).attr('data-event-title') || '');
            });
            return titles;
        }

        // $root 안 명단 그리드에서 행사 날짜 배열을 뽑는다. (일정 요약 블록용)
        function eventDatesFromDom($root){
            var dates = [];
            $root.find('[data-roster-grid]').first().find('[data-roster-card]').each(function(){
                dates.push($(this).attr('data-event-date') || '');
            });
            return dates;
        }

        // 분리된 DOM($root) 에 "행사 배열 설정"을 반영한다.
        //  - 캐리어 data-detail-settings 갱신 (= {events:[...]})
        //  - 참여 조건 나이 블록 / 남·여 키 블록을 행사 수에 맞춰 다시 그린다.
        //  - model.events 길이는 화면 행사(명단 카드) 수에 맞춘다.
        function applySettingsToDom($root, model){
            var titles = eventTitlesFromDom($root);
            var count  = Math.max(1, titles.length || (model.events ? model.events.length : 1));
            model = fitEventsToCount(model, count);
            var multi = count > 1;

            // 나이 표기 텍스트는 숫자(세) 최소/최대에서 파생해 항상 최신값으로 재계산.
            model.events.forEach(function(s){
                s.maleAgeText   = settingAgeText(s.maleAgeMin, s.maleAgeMax);
                s.femaleAgeText = settingAgeText(s.femaleAgeMin, s.femaleAgeMax);
            });

            // 1) 캐리어 JSON 갱신 — 없으면 새로 만들어 콘텐츠 맨 앞에 끼운다.
            var $carrier = $root.find('[data-detail-settings]').first();
            if ($carrier.length === 0) {
                var $wrapper = $root.children('div').first();
                var $target  = $wrapper.length ? $wrapper : $root;
                $carrier = $('<div data-detail-settings style="display:none;">\u200b</div>');
                $target.prepend($carrier);
            }
            $carrier.attr('data-detail-settings', JSON.stringify({ events: model.events, records: normalizeRecords(model.records) }));
            if (($carrier.text() || '').length === 0) { $carrier.text('\u200b'); }

            var esc = function(s){
                return String(s == null ? '' : s)
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            };
            var label = function(i){ return multi ? (esc(settingEventLabel(titles[i], i)) + ' · ') : ''; };

            // 키 블록 채우기. 입력이 하나도 없으면 블록을 숨긴다.
            //  빈 div 를 그대로 두면 에디터(TinyMCE)가 <br>/&nbsp; 를 채워 넣어 "한 줄 띄어진 것"처럼 보이고,
            //  아예 지우면 다음 [적용] 때 다시 그릴 자리가 사라지므로, 숨김 + 보이지 않는 문자로 자리만 유지한다.
            function setHeightBlock($block, html){
                if (html === '') {
                    $block.html('&#8203;').css('display', 'none');
                } else {
                    $block.html(html).css('display', '');
                }
            }

            // 2) 나이 블록 — 행사마다 한 줄.
            var $ageBlock = $root.find('[data-setting-block="age"]').first();
            if ($ageBlock.length) {
                var ageHtml = model.events.map(function(s, i){
                    return '<div>- ' + label(i)
                        + '남성 ' + esc(s.maleAgeText || '') + ' / 여성 ' + esc(s.femaleAgeText || '') + '</div>';
                }).join('');
                $ageBlock.html(ageHtml);
            }

            // 3) 남성 키 블록 — 입력된 행사만 한 줄.
            var $maleBlock = $root.find('[data-setting-block="maleHeight"]').first();
            if ($maleBlock.length) {
                var maleHtml = model.events.map(function(s, i){
                    var h = (s.maleHeight || '').trim();
                    if (h === '') { return ''; }
                    return '<div>- ' + label(i) + '신장 <b>' + esc(h) + ' 이상</b></div>';
                }).join('');
                setHeightBlock($maleBlock, maleHtml);
            }

            // 4) 여성 키 블록 — 입력된 행사만 한 줄. (전부 비면 빈 블록)
            var $femaleBlock = $root.find('[data-setting-block="femaleHeight"]').first();
            if ($femaleBlock.length) {
                var femaleHtml = model.events.map(function(s, i){
                    var h = (s.femaleHeight || '').trim();
                    if (h === '') { return ''; }
                    return '<div>- ' + label(i) + '신장 <b>' + esc(h) + ' 이상</b></div>';
                }).join('');
                setHeightBlock($femaleBlock, femaleHtml);
            }

            // 4-1) 이번 모집 일정 요약 — 명단 카드(행사명·날짜) + 행사별 나이로 행사마다 한 칸. (_detailSample 과 동일 마크업)
            var $scheduleBlock = $root.find('[data-setting-block="schedule"]').first();
            if ($scheduleBlock.length) {
                var dates = eventDatesFromDom($root);
                var scheduleHtml = model.events.map(function(s, i){
                    var last = i === model.events.length - 1;
                    return '<div style="background:#ffffff; border:1px solid #f2d4dc; border-radius:12px; padding:16px 14px; text-align:center;' + (last ? '' : ' margin-bottom:12px;') + '">'
                        +   '<div style="font-size:32px; font-weight:900; color:#7a1f3d; word-break:keep-all;">🗓 ' + esc(dates[i] || '') + '</div>'
                        +   '<div style="font-size:28px; font-weight:800; color:#374151; margin-top:6px; word-break:keep-all;">' + esc(settingEventLabel(titles[i], i)) + '</div>'
                        +   '<div style="font-size:27px; font-weight:800; margin-top:6px; word-break:keep-all;">'
                        +     '<span style="color:#4a6fa5;">남 ' + esc(s.maleAgeText || '') + '</span> · '
                        +     '<span style="color:#e8708a;">여 ' + esc(s.femaleAgeText || '') + '</span>'
                        +   '</div>'
                        + '</div>';
                }).join('');
                $scheduleBlock.html(scheduleHtml);
            }

            // 5) 이전 로테이블 기록 블록 — records 배열로 행을 다시 그린다. (페이지 전역)
            var $recBlock = $root.find('[data-setting-block="records"]').first();
            if ($recBlock.length) {
                var recs = normalizeRecords(model.records);
                var recHtml = recs.map(function(r, i){
                    var border = (i < recs.length - 1) ? ' border-bottom:1px solid #d7e3ef;' : '';
                    return '<div data-record-row style="display:flex; font-size:29px; color:#374151; padding:14px 0;' + border + '">'
                        +   '<div style="width:28%; border-right:1px solid #d7e3ef;">' + esc(r.date) + '</div>'
                        +   '<div style="width:27%; border-right:1px solid #d7e3ef;">' + esc(r.title) + '</div>'
                        +   '<div style="width:25%; border-right:1px solid #d7e3ef;">' + esc(r.people) + '</div>'
                        +   '<div style="width:20%; color:#be185d; font-weight:800;">' + esc(r.couples) + '</div>'
                        + '</div>';
                }).join('');
                $recBlock.html(recHtml);
            }

            return $root;
        }

        // 현재 에디터 HTML 에 설정 모델을 반영한 새 HTML 문자열을 돌려준다.
        function applySettingsToHtml(html, model){
            var $root = $('<div></div>').html(html || '');
            applySettingsToDom($root, model);
            return $root.html();
        }

        // 설정 편집 카드 한 장(행사 1개)의 HTML. (명단 편집 카드와 시각적으로 맞춤)
        function settingsEventCard(title, i, s){
            s = s || {};
            var titleText = $('<i>').text(title || ((i + 1) + '번째 행사')).html();
            return ''
              + '<div class="card mb-3 roster-ev-card" data-setting-card="' + i + '">'
              +   '<div class="card-header roster-ev-head d-flex align-items-center flex-wrap gap-2">'
              +     '<span class="badge bg-pink text-white fs-4">' + (i + 1) + '번째 행사</span>'
              +     '<i class="ti ti-calendar-event text-pink fs-2"></i>'
              +     '<b class="roster-ev-title-echo fs-2">' + titleText + '</b>'
              +   '</div>'
              +   '<div class="card-body">'
              +     '<div class="row g-3">'
              +       '<div class="col-md-6">'
              +         '<label class="form-label">남성 나이 (최소 ~ 최대)</label>'
              +         '<div class="input-group">'
              +           '<input type="number" class="form-control s-male-age-min" placeholder="최소" min="19" max="99" value="' + (s.maleAgeMin || '') + '">'
              +           '<span class="input-group-text">~</span>'
              +           '<input type="number" class="form-control s-male-age-max" placeholder="최대" min="19" max="99" value="' + (s.maleAgeMax || '') + '">'
              +           '<span class="input-group-text">세</span>'
              +         '</div>'
              +       '</div>'
              +       '<div class="col-md-6">'
              +         '<label class="form-label">여성 나이 (최소 ~ 최대)</label>'
              +         '<div class="input-group">'
              +           '<input type="number" class="form-control s-female-age-min" placeholder="최소" min="19" max="99" value="' + (s.femaleAgeMin || '') + '">'
              +           '<span class="input-group-text">~</span>'
              +           '<input type="number" class="form-control s-female-age-max" placeholder="최대" min="19" max="99" value="' + (s.femaleAgeMax || '') + '">'
              +           '<span class="input-group-text">세</span>'
              +         '</div>'
              +       '</div>'
              +     '</div>'
              +     '<small class="form-hint text-muted d-block mt-1 mb-3">'
              +       '나이(세) → <b>"OO~OO년생"</b> 자동 환산. <span class="s-age-preview text-azure fw-bold"></span>'
              +     '</small>'
              +     '<div class="row g-3">'
              +       '<div class="col-md-6">'
              +         '<label class="form-label">남성 키 (최소 신장)</label>'
              +         '<input type="text" class="form-control s-male-height" placeholder="미입력 시 173cm" value="' + $('<i>').text(s.maleHeight || '').html() + '">'
              +         '<small class="form-hint text-muted">비우면 자동으로 <b>173cm</b> 로 표기돼요.</small>'
              +       '</div>'
              +       '<div class="col-md-6">'
              +         '<label class="form-label">여성 키 (최소 신장)</label>'
              +         '<input type="text" class="form-control s-female-height" placeholder="미입력 시 표기 안 함" value="' + $('<i>').text(s.femaleHeight || '').html() + '">'
              +       '</div>'
              +     '</div>'
              +   '</div>'
              + '</div>';
        }

        // 설정 모델 + 행사 제목 배열 → 행사 카드들을 #settingsEventsBody 에 렌더.
        function renderSettingsForm(model, titles){
            titles = titles || [];
            var count = Math.max(1, titles.length || (model.events ? model.events.length : 1));
            model = fitEventsToCount(model, count);
            var $body = $('#settingsEventsBody').empty();
            for (var i = 0; i < count; i++) {
                $body.append(settingsEventCard(titles[i], i, model.events[i]));
            }
            updateAllAgePreviews();
        }

        // #settingsEventsBody 의 입력들을 모아 설정 모델({events:[...]})로 반환.
        function collectSettingsForm(){
            var events = [];
            $('#settingsEventsBody [data-setting-card]').each(function(){
                var $c = $(this);
                events.push(normalizeEventSetting({
                    maleAgeMin:   ($c.find('.s-male-age-min').val()   || '').trim(),
                    maleAgeMax:   ($c.find('.s-male-age-max').val()   || '').trim(),
                    femaleAgeMin: ($c.find('.s-female-age-min').val() || '').trim(),
                    femaleAgeMax: ($c.find('.s-female-age-max').val() || '').trim(),
                    maleHeight:   ($c.find('.s-male-height').val()    || '').trim(),
                    femaleHeight: ($c.find('.s-female-height').val()  || '').trim()
                }));
            });
            if (!events.length) { events.push(normalizeEventSetting({})); }
            return { events: events };
        }

        /* ---- 이전 로테이블 기록 편집 ---- */
        // 기록 한 행의 편집 UI(입력 4칸 + 삭제 버튼).
        function recordEditorRow(r){
            r = r || {};
            var v = function(s){ return $('<i>').text(s == null ? '' : s).html(); };
            return ''
              + '<div class="row g-2 mb-2 align-items-center" data-record-edit>'
              +   '<div class="col"><input type="text" class="form-control form-control-sm r-date" placeholder="날짜 (예: 2026.11.07)" value="' + v(r.date) + '"></div>'
              +   '<div class="col"><input type="text" class="form-control form-control-sm r-title" placeholder="행사명 (예: 1회차 와인파티)" value="' + v(r.title) + '"></div>'
              +   '<div class="col"><input type="text" class="form-control form-control-sm r-people" placeholder="인원 (예: 22명 (12/10))" value="' + v(r.people) + '"></div>'
              +   '<div class="col"><input type="text" class="form-control form-control-sm r-couples" placeholder="매칭 (예: 6커플)" value="' + v(r.couples) + '"></div>'
              +   '<div class="col-auto"><button type="button" class="btn btn-outline-danger btn-record-del"><i class="ti ti-trash me-1"></i> 삭제</button></div>'
              + '</div>';
        }

        // 기록 날짜 입력칸(.r-date)에 달력(flatpickr) 적용. 표기 형식: "2026.11.07" (날짜만)
        //  - 이미 적용된 칸은 건너뛰므로 행 렌더/추가 후 매번 호출해도 안전하다.
        function initRecordDatePickers(){
            if(typeof flatpickr === 'undefined'){ return; }
            var pad = function(n){ return String(n).padStart(2, '0'); };
            $('#recordsEditorBody .r-date').each(function(){
                if(this._flatpickr){ return; }
                flatpickr(this, {
                    allowInput: true,
                    locale:     'ko',
                    dateFormat: 'Y.m.d',
                    formatDate: function(d){
                        return d.getFullYear() + '.' + pad(d.getMonth() + 1) + '.' + pad(d.getDate());
                    },
                    parseDate: function(str){
                        var m = /(\d{4})\D+(\d{1,2})\D+(\d{1,2})/.exec(str || '');
                        return m ? new Date(+m[1], +m[2] - 1, +m[3]) : undefined;
                    }
                });
            });
        }

        // 기록 배열 → #recordsEditorBody 에 행들을 렌더.
        function renderRecordsEditor(records){
            var list = normalizeRecords(records);
            var $body = $('#recordsEditorBody').empty();
            list.forEach(function(r){ $body.append(recordEditorRow(r)); });
            initRecordDatePickers();
        }

        // #recordsEditorBody 의 입력들을 모아 records 배열로 반환.
        function collectRecordsEditor(){
            var rows = [];
            $('#recordsEditorBody [data-record-edit]').each(function(){
                var $r = $(this);
                rows.push({
                    date:    ($r.find('.r-date').val()    || '').trim(),
                    title:   ($r.find('.r-title').val()   || '').trim(),
                    people:  ($r.find('.r-people').val()  || '').trim(),
                    couples: ($r.find('.r-couples').val() || '').trim()
                });
            });
            return normalizeRecords(rows);
        }

        // 기록 추가/삭제 (이벤트 위임)
        $('.btn-record-add').on('click', function(){
            $('#recordsEditorBody').append(recordEditorRow({}));
            initRecordDatePickers();
        });
        $('#recordsEditorBody').on('click', '.btn-record-del', function(){
            $(this).closest('[data-record-edit]').remove();
        });

        // 한 카드의 나이 미리보기 갱신.
        function updateAgePreviewFor($card){
            var male   = settingAgeText($card.find('.s-male-age-min').val(), $card.find('.s-male-age-max').val());
            var female = settingAgeText($card.find('.s-female-age-min').val(), $card.find('.s-female-age-max').val());
            var parts = [];
            if (male)   { parts.push('남성 ' + male); }
            if (female) { parts.push('여성 ' + female); }
            $card.find('.s-age-preview').text(parts.length ? ('→ ' + parts.join(' / ')) : '');
        }
        function updateAllAgePreviews(){
            $('#settingsEventsBody [data-setting-card]').each(function(){ updateAgePreviewFor($(this)); });
        }
        // 나이 입력칸 변경 시 해당 카드 미리보기 갱신 (이벤트 위임).
        $('#settingsEventsBody').on('input',
            '.s-male-age-min, .s-male-age-max, .s-female-age-min, .s-female-age-max',
            function(){ updateAgePreviewFor($(this).closest('[data-setting-card]')); });

        // '페이지 설정 · 명단 편집' 열기 — 현재 에디터의 설정/명단을 읽어 폼·표에 채운다.
        // 캐리어가 없어도(최초 접근·예전 저장본 등) 화면에 보이는 값(참여조건/자격요건)에서 복원해 채운다.
        $('.btn-open-settings').on('click', function(){
            var html = getHtml();
            if (!html || !html.trim()) {
                // 아예 내용이 없을 때만 안내.
                $('#settingsEventsBody').empty();
                $('#settingsEmpty').show();
            } else {
                $('#settingsEmpty').hide();
                var $root   = $('<div></div>').html(html);
                var titles  = eventTitlesFromDom($root);
                var model   = readSettingsFromDom($root);
                renderSettingsForm(model, titles);
                renderRecordsEditor(model.records);
            }
            // 명단 편집 섹션도 같은 모달 안에서 함께 채운다.
            populateRosterEditor();
            var m = getSettingsModal();
            if (m) { m.show(); }
        });

        // [적용] → 페이지 설정 + 참여자 명단을 한 번의 HTML 갱신으로 반영한다.
        $('.btn-settings-apply').on('click', async function(){
            var html = getHtml();
            if (!html || !html.trim()) {
                await salert({text: '먼저 샘플을 불러오거나 내용을 작성해 주세요.', cancel: false});
                return;
            }
            // 1) 명단 반영(행사 제목/개수 확정) → 2) 그 결과에 행사별 설정 반영 → 3) 업데이트 시각.
            var model   = collectSettingsForm();
            model.records = collectRecordsEditor();
            sortRosterEditor();  // 명단 자동 정렬 (나이 어린 순 → 키 큰 순)
            var withR   = applyRosterToHtml(html);
            var withRS  = applySettingsToHtml(withR, model);
            setHtml(applyUpdatedAtToSample(withRS));

            var m = getSettingsModal();
            if (m) { m.hide(); }
            await salert({text: '설정과 명단을 반영했어요. 명단은 나이 어린 순 → 키 큰 순으로 자동 정렬됐어요. 내용을 확인한 뒤 [저장]을 눌러 주세요.', cancel: false});
        });

        // 샘플 HTML 에 "현재 에디터의 설정값"을 이식해서 돌려준다. (참여자 명단과 동일 원리)
        // - 캐리어가 없어도 화면 마커에서 복원(readSettingsFromDom)해 새 샘플에 그대로 반영한다.
        function applyCurrentSettingsToSample(sampleHtml){
            var html = getHtml();
            if (!html || !html.trim()) { return sampleHtml; }
            var $root = $('<div></div>').html(html);
            var curSettings = readSettingsFromDom($root);
            // 현재 화면에 기록이 하나도 없으면, 새 샘플의 기본 기록을 그대로 둔다.
            // (빈 records 로 샘플 기본 기록을 지워버리지 않도록)
            if (!curSettings.records || !curSettings.records.length) {
                var $sampleRoot = $('<div></div>').html(sampleHtml);
                curSettings.records = readSettingsFromDom($sampleRoot).records;
            }
            return applySettingsToHtml(sampleHtml, curSettings);
        }

        // 샘플/저장본 HTML 의 '마지막 업데이트' 문구를 "지금 이 페이지에 접속한 시각"으로 교체한다.
        //  - 1순위: data-updated-at 마커 span 의 텍스트를 교체.
        //  - 2순위(마커 없는 기존 저장본 호환): '마지막 업데이트 : ... 기준' 텍스트 패턴을 직접 치환.
        //  - 포맷은 'n월 j일 H:i' (예: 11월 5일 14:49). 접속 시점 값으로 박혀 저장/다운로드에도 유지된다.
        function applyUpdatedAtToSample(sampleHtml){
            if (!sampleHtml) { return sampleHtml; }
            var d = new Date();
            var p = function(n){ return (n < 10 ? '0' : '') + n; };
            var stamp = (d.getMonth() + 1) + '월 ' + d.getDate() + '일 '
                + p(d.getHours()) + ':' + p(d.getMinutes());
            var label = '마지막 업데이트 : ' + stamp + ' 기준';

            var $root = $('<div></div>').html(sampleHtml);
            var $marked = $root.find('[data-updated-at]');
            if ($marked.length) {
                $marked.text(label);
                return $root.html();
            }
            // 마커가 없으면(구 저장본) 텍스트 패턴으로 치환. '마지막 업데이트 : {무엇이든} 기준'
            return sampleHtml.replace(
                /마지막 업데이트\s*:\s*[^<]*?기준/g,
                label
            );
        }

        // 1열 샘플 (선택한 유형 기준)
        $('.btn-sample-one').on('click', async function(){
            var type = currentType();
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            if(await salert({text: '현재 작성 내용을 [' + label + '] 1열 샘플로 교체하시겠습니까?\n(현재 참여자 명단·행사명/시간·페이지 설정은 그대로 유지됩니다)'})){
                // 참여자 명단에 이어 페이지 설정(행사명·나이·키)도 현재 값으로 이식한다.
                // '마지막 업데이트' 문구는 지금 접속한 시각으로 교체한다.
                setHtml(applyUpdatedAtToSample(applyCurrentSettingsToSample(applyCurrentRosterToSample(SAMPLES[type][1]))));
            }
        });

        // 2열 샘플 (선택한 유형 기준)
        $('.btn-sample-two').on('click', async function(){
            var type = currentType();
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            if(await salert({text: '현재 작성 내용을 [' + label + '] 2열 샘플로 교체하시겠습니까?\n(현재 참여자 명단·행사명/시간·페이지 설정은 그대로 유지됩니다)'})){
                // 참여자 명단에 이어 페이지 설정(행사명·나이·키)도 현재 값으로 이식한다.
                // '마지막 업데이트' 문구는 지금 접속한 시각으로 교체한다.
                setHtml(applyUpdatedAtToSample(applyCurrentSettingsToSample(applyCurrentRosterToSample(SAMPLES[type][2]))));
            }
        });

        // 서버 응답(item)으로 현재 유형의 CURRENT/META 상태와 배지를 갱신한다.
        function applySavedItem(type, item){
            if (!item) { return; }
            CURRENT[type] = { id: item.id, title: item.title };
            META[type] = { saved_by: item.saved_by, saved_at: item.saved_at };
            SAVED[type] = getHtml();
            if (type === currentType()) { renderSavedInfo(); syncUrl(true); }
        }

        // 현재 HTML 을 "새 저장본"으로 만든다. (이름 입력받음) → 성공 시 CURRENT 를 그 항목으로.
        async function createSnapshot(type, html, defaultTitle){
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            var res = await Swal.fire({
                title: '[' + label + '] 새 상세페이지 저장',
                input: 'text',
                inputLabel: '저장 이름',
                inputValue: defaultTitle || '',
                inputPlaceholder: '예: 11월 와인파티 2차 버전',
                inputAttributes: { maxlength: 100 },
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: '저장',
                cancelButtonText: '취소',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                inputValidator: function(v){ if (!v || !v.trim()) { return '저장 이름을 입력해 주세요.'; } }
            });
            if (!res.isConfirmed) { return false; }
            return new Promise(function(resolve){
                $.ajax({
                    url: "{{ route('admin.rotation.detail.saves.store') }}",
                    type: 'POST',
                    data: { title: res.value.trim(), html: html, type: type },
                    success: function(r){ applySavedItem(type, r && r.item); alert(r?.msg); resolve(true); },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); resolve(false); }
                });
            });
        }

        // [저장] — 현재 편집 중 저장본이 있으면 그걸 업데이트, 없으면 새로 저장(이름 입력).
        $('.btn-detail-save').on('click', async function(){
            var type = currentType();
            var html = getHtml();
            var cur  = CURRENT[type] || {};

            if (cur.id) {
                // 기존 저장본 업데이트
                if (!(await salert({text: "'" + (cur.title || '') + "' 저장본을 수정 저장하시겠습니까?"}))) { return; }
                $.ajax({
                    url: "{{ url('admin/rotation/detail/saves') }}/" + cur.id,
                    type: 'POST',
                    data: { html: html },
                    success: function(r){ applySavedItem(type, r && r.item); alert(r?.msg); },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
                });
            } else {
                // 저장 목록에 없는 새 내용 → 새로 저장
                if (!html || !html.trim()) {
                    await salert({text: '저장할 내용이 없습니다. 먼저 내용을 작성해 주세요.', cancel: false});
                    return;
                }
                await createSnapshot(type, html, '');
            }
        });

        /* =====================================================================
         * 저장 목록 (이름 붙여 저장) — 유형별 다중 스냅샷
         *  - 다른 이름으로 저장: 제목 입력받아 현재 HTML 을 목록에 새로 저장
         *  - 저장 목록: 현재 유형의 목록 조회 → 불러오기/삭제
         * =================================================================== */
        var savesModal = null;
        function getSavesModal(){
            if (savesModal) { return savesModal; }
            var el = document.getElementById('detailSavesModal');
            var ModalClass =
                (window.tabler && window.tabler.Modal) ||
                (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                (window.bootstrap && window.bootstrap.Modal) ||
                (window.coreui && window.coreui.Modal) ||
                window.Modal;
            if(!ModalClass){ console.error('Modal 클래스를 찾을 수 없습니다.'); return null; }
            savesModal = new ModalClass(el);
            return savesModal;
        }

        function savesEsc(s){
            return $('<i>').text(s == null ? '' : s).html();
        }

        // 목록 데이터 → 테이블 렌더
        function renderSavesList(items){
            var $body = $('#savesListBody');
            if (!items || !items.length) {
                $body.empty();
                $('#savesEmpty').show();
                return;
            }
            $('#savesEmpty').hide();
            var rows = items.map(function(it){
                var typeLabel = it.type === 'solo' ? '1:1 소개팅' : '와인파티';
                var typeTone  = it.type === 'solo' ? 'bg-azure-lt' : 'bg-pink-lt';
                return ''
                  + '<tr data-id="' + it.id + '" data-type="' + savesEsc(it.type || 'wine') + '">'
                  +   '<td class="w-100">'
                  +       '<div class="fw-bold">'
                  +         '<span class="badge ' + typeTone + ' me-1">' + typeLabel + '</span>'
                  +         savesEsc(it.title)
                  +       '</div>'
                  +       '<div class="text-secondary small">' + savesEsc(it.saved_by || '') + ' · ' + savesEsc(it.saved_at || '') + '</div></td>'
                  +   '<td class="text-nowrap text-end">'
                  +     '<button type="button" class="btn btn-sm btn-primary saves-load"><i class="ti ti-folder-open me-1"></i>불러오기</button> '
                  +     '<button type="button" class="btn btn-sm btn-ghost-danger saves-del"><i class="ti ti-trash"></i></button>'
                  +   '</td>'
                  + '</tr>';
            }).join('');
            $body.html('<table class="table table-vcenter card-table"><tbody>' + rows + '</tbody></table>');
        }

        // 저장 목록을 서버에서 불러와 렌더 — 행사 타입 상관없이 "전체"를 보여준다.
        function reloadSavesList(){
            $('#savesTypeBadge').text('전체');
            $('#savesListBody').html('<div class="text-center text-secondary py-4"><span class="spinner-border spinner-border-sm me-2"></span>불러오는 중…</div>');
            $('#savesEmpty').hide();
            $.ajax({
                url: "{{ route('admin.rotation.detail.saves.list') }}",
                type: 'GET',
                data: { type: 'all' },
                success: function(res){ renderSavesList(res && res.items ? res.items : []); },
                error: function(){ $('#savesListBody').html('<div class="text-danger text-center py-4">목록을 불러오지 못했습니다.</div>'); }
            });
        }

        // 다른 이름으로 저장 (제목 입력받아 새 저장본 생성 → 현재 편집 대상이 그 새 저장본이 됨)
        $('.btn-detail-save-as').on('click', async function(){
            var type = currentType();
            var html = getHtml();
            if (!html || !html.trim()) {
                await salert({text: '저장할 내용이 없습니다. 먼저 내용을 작성해 주세요.', cancel: false});
                return;
            }
            var ok = await createSnapshot(type, html, (CURRENT[type] && CURRENT[type].title) ? (CURRENT[type].title + ' (복사)') : '');
            if (ok && document.getElementById('detailSavesModal').classList.contains('show')) {
                reloadSavesList();
            }
        });

        // 저장 목록 열기
        $('.btn-open-saves').on('click', function(){
            reloadSavesList();
            var m = getSavesModal();
            if (m) { m.show(); }
        });

        // 불러오기 (선택한 저장본을 에디터에 로드 → 그 저장본이 현재 편집 대상이 됨)
        // 목록이 '전체' 유형을 보여주므로, 다른 유형을 불러오면 에디터 유형도 그 유형으로 전환한다.
        $('#savesListBody').on('click', '.saves-load', async function(){
            var $tr     = $(this).closest('tr');
            var id      = $tr.data('id');
            var rowType = ($tr.data('type') === 'solo') ? 'solo' : 'wine'; // 서버 res.type 우선, 폴백용
            if (!(await salert({text: '이 저장본으로 현재 작성 내용을 교체하시겠습니까?'}))) { return; }
            $.ajax({
                url: "{{ url('admin/rotation/detail/saves') }}/" + id,
                type: 'GET',
                success: async function(res){
                    // apiRes 는 200 응답에서 isSuccess 를 제거하므로, success 콜백이면 로드 성공으로 본다.
                    var type = (res && res.type === 'solo') ? 'solo' : (res && res.type === 'wine' ? 'wine' : rowType);

                    // 불러온 저장본의 유형으로 에디터 유형 라디오를 전환한다. (change 핸들러가 SAVED 보관/activeType 갱신)
                    if (type !== currentType()) {
                        $('input[name="sampleType"][value="' + type + '"]').prop('checked', true).trigger('change');
                    }

                    setHtml((res && res.html) ? res.html : '');
                    // 불러온 저장본을 "현재 편집 중"으로 설정 → 이후 [저장] 은 이 항목을 덮어쓴다.
                    CURRENT[type] = { id: (res && res.id) ? res.id : id, title: (res && res.title) || '' };
                    SAVED[type] = getHtml();
                    activeType = type;
                    renderSavedInfo();
                    syncUrl(false); // 주소에 type/saveId 반영 (뒤로가기로 이전 선택 복귀 가능)
                    var m = getSavesModal();
                    if (m) { m.hide(); }
                    await salert({text: "'" + ((res && res.title) || '') + "' 를 불러왔어요. 수정 후 [저장]을 누르면 이 저장본이 갱신됩니다.", cancel: false});
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '불러오지 못했습니다.'); }
            });
        });

        // 삭제 (현재 편집 중이던 저장본을 지우면 '미저장 새 문서' 상태로 전환)
        $('#savesListBody').on('click', '.saves-del', async function(){
            var $tr = $(this).closest('tr');
            var id  = parseInt($tr.data('id'), 10);
            if (!(await salert({text: '이 저장본을 삭제하시겠습니까?'}))) { return; }
            var rowType = ($tr.data('type') === 'solo') ? 'solo' : 'wine';
            $.ajax({
                url: "{{ url('admin/rotation/detail/saves') }}/" + id,
                type: 'DELETE',
                success: function(res){
                    alert(res?.msg);
                    // 삭제한 저장본이 '현재 편집 중'이었다면(유형 무관) 미저장 상태로 전환.
                    if (CURRENT[rowType] && parseInt(CURRENT[rowType].id, 10) === id) {
                        CURRENT[rowType] = { id: null, title: null };
                        renderSavedInfo();
                    }
                    reloadSavesList();
                },
                error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '삭제하지 못했습니다.'); }
            });
        });

        /* =====================================================================
         * 이미지 "분할" 다운로드 — 블록 경계 기준으로 여러 장(고화질)으로 저장
         * - 긴 상세페이지를 한 장으로 캡처하면 캔버스 한계 때문에 배율(선명도)이 깎여
         *   모바일에서 눌려/흐려 보인다. 최상위 블록 단위로 "목표 높이"를 넘지 않게 묶어
         *   여러 장으로 나누면 각 장이 짧아져 4배 선명도를 유지할 수 있다.
         * - 블록(최상위 자식 요소) 경계에서만 자르므로 글자/명단 카드/이미지가 중간에 잘리지 않는다.
         * - 캐리어 등 숨은 요소(높이 0)는 자연히 첫 장에 묶여 영향 없음.
         * =================================================================== */

        // 최상위 블록들을 "목표 높이(targetH)"를 넘지 않게 순서대로 묶어 페이지 그룹을 만든다.
        //  - 한 블록이 혼자서 targetH 보다 크면 그 블록만으로 한 페이지가 된다.
        //  - maxPages 가 주어지면 그 장수를 넘지 않도록 목표 높이를 자동으로 키운다.
        //    (전체 높이를 maxPages 로 나눈 값 이상으로 targetH 를 올려 페이지 수를 제한)
        //  - 반환: 블록 요소 배열의 배열. 예) [[b0,b1],[b2],[b3,b4]]
        function groupBlocksByHeight(blocks, targetH, maxPages){
            var heights = blocks.map(function(el){
                return el.getBoundingClientRect().height || el.offsetHeight || 0;
            });
            // 장수 상한이 있으면, "전체 높이 / maxPages" 보다 목표 높이를 작게 두지 않는다.
            if (maxPages && maxPages > 0) {
                var total = heights.reduce(function(a, b){ return a + b; }, 0);
                targetH = Math.max(targetH, Math.ceil(total / maxPages));
            }

            var pages = [];
            var cur = [];
            var curH = 0;
            blocks.forEach(function(el, i){
                var h = heights[i];
                // 현재 페이지에 내용이 있고, 더 넣으면 목표를 넘기면 페이지를 끊는다.
                // 단, 이미 마지막 허용 장(maxPages)에 도달했으면 더 끊지 않고 모두 담는다.
                var atLastPage = maxPages && pages.length >= (maxPages - 1);
                if (cur.length && !atLastPage && (curH + h) > targetH) {
                    pages.push(cur);
                    cur = [];
                    curH = 0;
                }
                cur.push(el);
                curH += h;
            });
            if (cur.length) { pages.push(cur); }
            return pages;
        }

        /* ---------------------------------------------------------------
         * 고정 5장 레이아웃 (방안 A — 장 번호를 "의미 기준"으로 고정)
         *  - 1장: 메인 이미지 ~ "참가자 명단 인트로" 직전까지(상단 인트로/배너).
         *  - 2장: "명단이 적어 보여도~" 인트로(data-section="roster-intro") ~ 참가자 명단 그리드까지.
         *         → 자주 교체하는 장. 인트로 블록이 없으면 명단 그리드만으로 2장 구성.
         *  - 3~5장: 명단 이후 나머지 블록을 높이 기준으로 최대 3장 분배.
         *  → 내용이 바뀌어도 "2장 = 항상 (명단 인트로 ~) 참가자 명단"이라 특정 번호만 교체가 안전하다.
         *  - 명단 블록을 못 찾으면(커스텀 HTML 등) 전체를 높이 기준 5장으로 폴백.
         * 반환: { pages: 블록배열의 배열, labels: 각 장 라벨 }
         * ------------------------------------------------------------- */
        function buildFixedPages(blocks){
            // 참가자 명단(프로필) 블록의 인덱스 — 자신 또는 자손에 data-roster-grid 가 있는 최상위 블록.
            var rosterIdx = -1;
            for (var i = 0; i < blocks.length; i++) {
                var el = blocks[i];
                if (el.matches && (el.matches('[data-roster-grid]') || el.querySelector('[data-roster-grid]'))) {
                    rosterIdx = i;
                    break;
                }
            }

            // 명단을 못 찾으면: 기존 높이 기준 5장 폴백.
            if (rosterIdx < 0) {
                var fb = groupBlocksByHeight(blocks, 1080 * 2.2, 5);
                return { pages: fb, labels: fb.map(function(_, k){ return '상세페이지'; }) };
            }

            // 2장 시작점: "명단 인트로"(data-section="roster-intro") 블록. 없으면 명단 그리드 자체.
            //  - 인트로가 명단보다 앞에 있을 때만 유효(그 사이 블록도 2장에 포함).
            // 2장 시작점 탐색: 명단보다 앞에 있는 블록 중 "명단 인트로"를 찾는다.
            //  우선순위: ① data-section="roster-intro" 마커(신규 샘플)
            //           ② data-updated-at 마커(LIVE 모집 블록 안에만 있음 — 구 저장본 호환)
            //           ③ "명단이 적어" 문구 포함 블록(최후 폴백)
            //  아무것도 없으면 introStart = rosterIdx (명단 그리드만 2장).
            var introStart = rosterIdx;
            for (var j = 0; j < rosterIdx; j++) {
                var b = blocks[j];
                var isIntro =
                    (b.matches && b.matches('[data-section="roster-intro"]')) ||
                    (b.querySelector && b.querySelector('[data-section="roster-intro"]')) ||
                    (b.querySelector && b.querySelector('[data-updated-at]')) ||
                    (/명단이\s*적어/.test(b.textContent || ''));
                if (isIntro) { introStart = j; break; }
            }

            var pages = [];
            var labels = [];

            // 1장: 2장 시작점 직전까지(상단 인트로/메인 이미지).
            var intro = blocks.slice(0, introStart);
            if (intro.length) { pages.push(intro); labels.push('메인'); }

            // 2장: (명단 인트로 ~) 참가자 명단 그리드까지.
            pages.push(blocks.slice(introStart, rosterIdx + 1)); labels.push('참가자명단');

            // 3~5장: 명단 이후 나머지를 높이 기준으로 (남은 장수만큼) 분배.
            var rest = blocks.slice(rosterIdx + 1);
            if (rest.length) {
                var remainSlots = Math.max(1, 5 - pages.length); // 남은 허용 장수(보통 3)
                var restPages = groupBlocksByHeight(rest, 1080 * 2.2, remainSlots);
                restPages.forEach(function(p){ pages.push(p); labels.push('안내'); });
            }

            return { pages: pages, labels: labels };
        }

        // 실제 "블록들"이 들어있는 콘텐츠 컨테이너를 찾는다.
        //  - 상세페이지 HTML 은 보통 바깥 wrapper div 하나(폰트/색 상속) 안에 섹션들이 들어있다.
        //  - 블록 자식이 1개뿐인 wrapper 는 계속 더 안으로 내려가며, 블록이 여러 개인 곳을 컨테이너로 본다.
        //  - 반환: { container: 블록들을 가진 요소, wrapperChain: 바깥→안 순서의 wrapper 요소 배열 }
        function findBlockContainer(root){
            var chain = [];
            var node = root;
            while (true) {
                var elChildren = Array.prototype.filter.call(node.children, function(c){
                    return c.tagName !== 'STYLE' && c.tagName !== 'SCRIPT';
                });
                if (elChildren.length === 1 && elChildren[0].children && elChildren[0].children.length) {
                    chain.push(elChildren[0]);
                    node = elChildren[0];
                    continue;
                }
                break;
            }
            return { container: node, wrapperChain: chain };
        }

        // onlyPage: 특정 장만 저장. 숫자(1-base) 또는 라벨 문자열('참가자명단' 등). 비우면 전체.
        async function runDetailDownload(onlyPage){
            if (typeof modernScreenshot === 'undefined') {
                await salert({text: '이미지 변환 모듈을 불러오지 못했습니다. 페이지를 새로고침해주세요.', cancel: false});
                return;
            }

            var html = getHtml();
            if (!html || !html.trim()) {
                await salert({text: '다운로드할 내용이 없습니다. 먼저 내용을 작성해주세요.', cancel: false});
                return;
            }

            showExportOverlay('이미지 분할 준비 중…');
            await new Promise(function(r){ setTimeout(r, 30); });

            // 측정 전용 holder 에 전체 HTML 을 1080px 로 렌더해 블록 높이를 잰다.
            var holder = document.getElementById('detailCaptureHolder');
            if (!holder) {
                holder = document.createElement('div');
                holder.id = 'detailCaptureHolder';
                holder.style.position = 'fixed';
                holder.style.left = '-10000px';
                holder.style.top = '0';
                holder.style.background = '#ffffff';
                document.body.appendChild(holder);
            }
            holder.style.width = '1080px';
            holder.innerHTML = html;

            try {
                if (document.fonts && document.fonts.ready) {
                    try { await document.fonts.ready; } catch (fe) { /* 무시 */ }
                }

                // 폰트 @import 등 <style> 은 모든 페이지에 공통으로 넣어야 폰트가 유지된다.
                var styleHtml = Array.prototype.map.call(
                    holder.querySelectorAll('style'),
                    function(s){ return s.outerHTML; }
                ).join('');

                // 실제 섹션들이 있는 컨테이너와 바깥 wrapper 체인을 찾는다.
                var found = findBlockContainer(holder);
                var container = found.container;
                var blocks = Array.prototype.slice.call(container.children).filter(function(c){
                    return c.tagName !== 'STYLE' && c.tagName !== 'SCRIPT';
                });

                // 고정 5장 레이아웃: 1=메인, 2=참가자명단, 3~5=나머지(안내).
                var built  = buildFixedPages(blocks);
                var pages  = built.pages;
                var labels = built.labels;
                if (!pages.length) {
                    await salert({text: '분할할 내용이 없습니다.', cancel: false});
                    return;
                }

                // onlyPage 가 라벨 문자열이면 해당 라벨의 장 번호로 변환. (숫자면 그대로)
                var onlyPageNo = null;
                if (onlyPage != null && onlyPage !== '') {
                    if (typeof onlyPage === 'number') {
                        onlyPageNo = onlyPage;
                    } else {
                        var idx = labels.indexOf(String(onlyPage));
                        onlyPageNo = idx >= 0 ? (idx + 1) : NaN;
                    }
                    if (isNaN(onlyPageNo) || onlyPageNo < 1 || onlyPageNo > pages.length) {
                        await salert({text: '해당 장을 찾지 못했습니다. (현재 ' + pages.length + '장)', cancel: false});
                        return;
                    }
                }

                var baseName = buildDownloadBaseName('rotable_상세페이지');

                // 각 페이지 그룹의 블록들을, "바깥 wrapper(폰트/색 상속)를 그대로 복제한" 구조 안에 넣어 캡처한다.
                for (var i = 0; i < pages.length; i++) {
                    var pageNo = i + 1;
                    if (onlyPageNo && pageNo !== onlyPageNo) { continue; } // 특정 장만 모드면 건너뜀

                    showExportOverlay('이미지 생성 중… (' + pageNo + '/' + pages.length + ')');
                    await new Promise(function(r){ setTimeout(r, 20); });

                    var pageHolder = document.createElement('div');
                    pageHolder.style.position = 'fixed';
                    pageHolder.style.left = '-10000px';
                    pageHolder.style.top = '0';
                    pageHolder.style.width = '1080px';
                    pageHolder.style.background = '#ffffff';
                    // 공통 <style> 먼저.
                    pageHolder.innerHTML = styleHtml;

                    // 바깥 wrapper 체인을 "속이 빈" 상태로 복제해 중첩한 뒤, 가장 안쪽에 이 페이지 블록을 넣는다.
                    var innermost = pageHolder;
                    found.wrapperChain.forEach(function(w){
                        var clone = w.cloneNode(false); // 속성/스타일만, 자식은 비움
                        innermost.appendChild(clone);
                        innermost = clone;
                    });
                    pages[i].forEach(function(el){ innermost.appendChild(el.cloneNode(true)); });

                    // 하단 여백 스페이서 — 최소한의 안전 여백만. (너무 크면 장 사이가 떠 보임)
                    var spacer = document.createElement('div');
                    spacer.setAttribute('data-capture-spacer', '1');
                    spacer.style.cssText = 'width:100%; height:12px; background:#ffffff;';
                    pageHolder.appendChild(spacer);
                    document.body.appendChild(pageHolder);

                    // (DOM 삽입 후) 장 상·하단 빈 공간 축소 — 구분마다 여백이 커 보이는 문제 해소.
                    //  - "보이는" 첫 블록의 위 마진 / 마지막 블록의 아래 마진을 0 으로.
                    //  - 숨은 요소(설정 캐리어 등 display:none)는 제외해야 실제 첫/끝 블록을 잡는다.
                    //  - getComputedStyle 은 DOM 삽입 이후라야 정확하므로 여기서 처리한다.
                    var kids = Array.prototype.filter.call(innermost.children, function(c){
                        if (c.tagName === 'STYLE' || c.tagName === 'SCRIPT') { return false; }
                        var cs = window.getComputedStyle(c);
                        if (cs.display === 'none' || cs.visibility === 'hidden') { return false; }
                        return true;
                    });
                    if (kids.length) {
                        kids[0].style.marginTop = '0';
                        kids[kids.length - 1].style.marginBottom = '0';
                    }

                    try {
                        var capW = pageHolder.offsetWidth || 1080;
                        var capH = pageHolder.scrollHeight || pageHolder.offsetHeight || 1080;
                        var scale = computeSafeScale(capW, capH, 4);

                        var canvas = await modernScreenshot.domToCanvas(pageHolder, {
                            backgroundColor: '#ffffff',
                            width: capW,
                            height: capH,
                            scale: scale,
                            fetch: { requestInit: { mode: 'cors', cache: 'no-cache' } }
                        });

                        // 파일명: 여러 장이면 _1, _2 … suffix. (한 장이면 suffix 없이)
                        // 파일명: 여러 장이면 순서 번호를 "앞에" 붙인다(1_, 2_ …). 한 장이면 접두 없음.
                        // 파일명: "번호_라벨_기본이름" (예: 2_참가자명단_rotable_상세페이지_...).
                        //  - 번호를 앞에 붙여 정렬 순서를 보장하고, 라벨로 어떤 장인지 식별.
                        var label = labels[i] || '상세페이지';
                        var fileName = (pages.length > 1 || onlyPageNo)
                            ? (pageNo + '_' + label + '_' + baseName)
                            : baseName;
                        await exportCanvasUnder10MB(canvas, fileName);
                    } finally {
                        document.body.removeChild(pageHolder);
                    }
                }
            } catch (e) {
                await salert({text: '이미지 생성 중 오류가 발생했습니다: ' + (e?.message || e), cancel: false});
            } finally {
                hideExportOverlay();
            }
        }

        // 전체 다운로드(드롭다운/미리보기 버튼) — 5장 모두.
        $('.btn-detail-download').on('click', function(){ runDetailDownload(null); });
        // 특정 장만 다운로드 — data-page-only 가 숫자면 그 번호, 문자열이면 그 라벨의 장.
        $('.btn-detail-download-page').on('click', function(){
            var raw = $(this).attr('data-page-only');
            var num = parseInt(raw, 10);
            runDetailDownload((raw != null && String(num) === String(raw)) ? num : raw);
        });

        /* =====================================================================
         * 피드 이미지 다운로드
         * - rotable_banner.png + "현재 에디터의 참여자 명단"(참여자 명단 편집과 동일한 소스)을
         *   합쳐 1장의 이미지로 저장한다.
         * - 명단은 에디터 HTML 안의 [data-roster-grid] 를 parseRoster() 로 추출해 사용한다.
         *   (DB 직접 조회가 아니라, 편집 화면에서 쓰는 바로 그 값)
         * =================================================================== */
        var feedModal = null;

        function getFeedModal(){
            if (feedModal) { return feedModal; }
            var el = document.getElementById('detailFeedModal');
            var ModalClass =
                (window.tabler && window.tabler.Modal) ||
                (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                (window.bootstrap && window.bootstrap.Modal) ||
                (window.coreui && window.coreui.Modal) ||
                window.Modal;
            if(!ModalClass){ console.error('Modal 클래스를 찾을 수 없습니다.'); return null; }
            feedModal = new ModalClass(el);
            return feedModal;
        }

        function feedEsc(s){
            return $('<i>').text(s == null ? '' : String(s)).html();
        }

        // ── 1:1 정사각(1080x1080) 피드 이미지 렌더 ──────────────────────────
        // 로테이블 로고 + 현재 참여자 명단을 SNS 피드용 정사각 썸네일로 구성한다.
        var FEED_LOGO = "{{ asset('images/logo.png') }}";

        // 피드 헤더에 쓸 행사 타입 라벨 (현재 선택된 유형 기준).
        function feedTypeLabel(){
            return currentType() === 'solo' ? '1:1 로테이션 소개팅' : '로테이션 와인파티';
        }

        // 한 사람 셀 (compact): N호 + (옵션)프로필
        function feedPersonCell(p, idx, showProfile){
            var profile = '';
            if (showProfile) {
                var bits = [
                    p.height ? (p.height + 'cm') : '',
                    p.job || '',
                    p.age || ''
                ].filter(Boolean);
                profile = '<div style="color:#1f2937; font-weight:800; font-size:21px; margin-top:3px; word-break:keep-all;">'
                        + feedEsc(bits.join(' · ')) + '</div>';
            }
            return ''
              + '<div style="padding:9px 8px; border-bottom:1px solid #e5e7eb; text-align:center;">'
              +   '<div style="font-weight:900; font-size:24px; color:#111827;">' + (idx + 1) + '호</div>'
              +   profile
              + '</div>';
        }

        // 한 성별 컬럼
        function feedColumn(label, bg, people, showProfile){
            var rows = (people || []).map(function(p, i){ return feedPersonCell(p, i, showProfile); }).join('')
                || '<div style="padding:20px; text-align:center; color:#9ca3af; font-weight:700;">모집중</div>';
            return ''
              + '<div style="flex:1 1 0; min-width:0;">'
              +   '<div style="padding:10px; text-align:center; font-weight:900; font-size:26px; color:#fff; background:' + bg + ';">'
              +     feedEsc(label) + ' <span style="font-weight:900;">' + ((people || []).length) + '</span></div>'
              +   '<div>' + rows + '</div>'
              + '</div>';
        }

        // 피드 본문 조립 = 로고 헤더 + '현재 에디터 명단 그대로'(buildRosterGrid 재사용).
        // buildRosterGrid 를 쓰므로 모집중(성비 자동 맞춤) 등 에디터 명단과 100% 동일하게 나온다.
        function feedSquareHtml(data){
            var card0 = (data && data.cards && data.cards[0]) ? data.cards[0] : null;
            return ''
            + '<div style="width:100%; box-sizing:border-box;'
            +            ' background:linear-gradient(160deg,#fff1f6 0%,#f7f0ff 55%,#eef4ff 100%); padding:44px 44px;'
            +            " font-family:'Gothic A1','Pretendard','Apple SD Gothic Neo',sans-serif;\">"

            // ── 상단: 로고 + 브랜드를 "액자"처럼 감싼 헤더 카드 ──
            +   '<div style="position:relative; margin-bottom:30px; padding:6px;'
            +            ' border-radius:28px; background:linear-gradient(135deg,#f9a8d4 0%,#c4b5fd 50%,#93c5fd 100%);">'
            +     '<div style="position:relative; border-radius:24px; background:#fffdfe; padding:34px 28px 30px; text-align:center;'
            +              ' border:2px solid #fde4ef;">'

            // 상단 작은 리본 뱃지
            +       '<div style="position:absolute; top:-20px; left:50%; transform:translateX(-50%);'
            +                ' background:linear-gradient(135deg,#ec4899,#a855f7); color:#ffffff;'
            +                ' font-size:24px; font-weight:900; padding:8px 26px; border-radius:999px; white-space:nowrap;">'
            +         '✨ 오늘, 운명 같은 만남 ✨'
            +       '</div>'

            // 로고 + ROTABLE (가로 나란히)
            +       '<div style="display:flex; align-items:center; justify-content:center; gap:16px; margin-top:12px;">'
            +         '<img src="' + FEED_LOGO + '" crossorigin="anonymous" alt="ROTABLE" style="width:88px; height:88px; object-fit:contain;">'
            +         '<div style="font-family:\'Jua\',sans-serif; font-size:66px; font-weight:400; color:#e8488f; letter-spacing:0.5px; line-height:1;">ROTABLE</div>'
            +       '</div>'

            // 설렘 카피
            +       '<div style="font-family:\'Jua\',sans-serif; font-size:36px; font-weight:400; color:#be185d; margin-top:16px; line-height:1.3;">'
            +         '한 자리에서, 모두와 설레는 만남 💘'
            +       '</div>'
            +       '<div style="font-size:24px; font-weight:800; color:#8b5cf6; margin-top:8px; line-height:1.5;">'
            +         '두근거리는 오늘, 당신의 인연이 이 안에 있을지도 몰라요'
            +       '</div>'

            // 타입 + 참여자 라벨 (구분선 위)
            +       '<div style="display:inline-block; margin-top:20px; padding-top:16px; border-top:2px dashed #f3c6dc;">'
            +         '<span style="display:inline-block; background:#fdf2f8; color:#be185d; border:2px solid #fbcfe8;'
            +                  ' border-radius:999px; padding:8px 26px; font-size:27px; font-weight:900;">'
            +           '💗 ' + feedEsc(feedTypeLabel()) + ' · 이번 회차 참여자'
            +         '</span>'
            +       '</div>'

            +     '</div>'
            +   '</div>'

            // ── 명단: 현재 에디터 명단 그대로 (내용 길이만큼) ──
            +   buildRosterGrid(data)
            + '</div>';
        }

        // 에디터에서 추출한 roster 데이터 → 피드 영역 렌더. (명단 없으면 false)
        function renderFeedRoster(){
            var loc  = locateRosterGrid(getHtml());
            var data = loc ? parseRoster(loc.grid) : null;

            if (!data || !data.cards.length) {
                $('#feedRosterArea').empty();
                $('#feedEmpty').show();
                $('.btn-feed-download').prop('disabled', true);
                updateFeedPreviewHeight();
                return false;
            }

            $('#feedEmpty').hide();
            $('#feedRosterArea').html(feedSquareHtml(data));
            $('.btn-feed-download').prop('disabled', false);
            updateFeedPreviewHeight();
            return true;
        }

        // 미리보기 래퍼 높이를 캡처 내용(scale 0.45) 에 맞춰 조정한다.
        // 아직 숨김(scrollHeight=0)이면 건드리지 않는다. (0px 로 접혀 안 보이는 문제 방지)
        function updateFeedPreviewHeight(){
            var cap = document.getElementById('feedCapture');
            if (!cap || !cap.parentElement) { return; }
            var h = cap.scrollHeight || cap.offsetHeight || 0;
            if (h > 0) {
                cap.parentElement.style.height = Math.ceil(h * 0.45) + 'px';
            } else {
                cap.parentElement.style.height = 'auto';
            }
        }

        // '피드 이미지 다운' 버튼 → 모달 먼저 열고, 완전히 보인 뒤 현재 명단으로 미리보기 렌더.
        // (모달이 숨겨진 상태에서 렌더하면 scrollHeight 가 0 이라 미리보기 높이가 0 이 된다.)
        $('.btn-open-feed').on('click', function(){
            var m = getFeedModal();
            var el = document.getElementById('detailFeedModal');
            if (el) {
                el.addEventListener('shown.bs.modal', function onShown(){
                    el.removeEventListener('shown.bs.modal', onShown);
                    renderFeedRoster();
                });
            }
            if (m) { m.show(); }
            // show 이벤트가 안 잡히는 환경 대비 폴백.
            setTimeout(renderFeedRoster, 250);
        });

        // 피드 이미지(로고 + 명단) 다운로드
        $('.btn-feed-download').on('click', async function(){
            if (typeof modernScreenshot === 'undefined') {
                await salert({text: '이미지 변환 모듈을 불러오지 못했습니다. 페이지를 새로고침해주세요.', cancel: false});
                return;
            }
            if (!renderFeedRoster()) {
                await salert({text: '참여자 명단 영역을 찾지 못했습니다. 샘플을 불러오거나 명단을 먼저 채워 주세요.', cancel: false});
                return;
            }

            var target = document.getElementById('feedCapture');

            // 화면 전체 로딩 오버레이로 진행 표시 (드롭다운/모달과 무관하게 보임)
            showExportOverlay('피드 이미지 생성 중…');
            await new Promise(function(r){ setTimeout(r, 30); });

            // 미리보기 축소(transform)와 폭을 캡처 직전 1080px 로 고정했다가 끝나면 원복.
            var prevWidth = target.style.width;
            var prevMaxWidth = target.style.maxWidth;
            var prevTransform = target.style.transform;
            target.style.width = '1080px';
            target.style.maxWidth = 'none';
            target.style.transform = 'none';

            try {
                if (document.fonts && document.fonts.ready) {
                    try { await document.fonts.ready; } catch (fe) { /* 무시 */ }
                }

                var feedW = target.offsetWidth || 1080;
                var feedH = target.scrollHeight || target.offsetHeight || 1080;
                var feedScale = computeSafeScale(feedW, feedH, 4);

                var canvas = await modernScreenshot.domToCanvas(target, {
                    backgroundColor: '#ffffff',
                    width: feedW,
                    height: feedH,
                    scale: feedScale,
                    fetch: { requestInit: { mode: 'cors', cache: 'no-cache' } }
                });

                // 화질 최우선 + 상한 (상세페이지 다운로드와 동일 로직)
                await exportCanvasUnder10MB(canvas, buildDownloadBaseName('rotable_피드'));
            } catch (e) {
                await salert({text: '이미지 생성 중 오류가 발생했습니다: ' + (e?.message || e), cancel: false});
            } finally {
                target.style.width = prevWidth;
                target.style.maxWidth = prevMaxWidth;
                target.style.transform = prevTransform;
                hideExportOverlay();
            }
        });

        /* =====================================================================
         * 선택 상태 복원 — 초기 진입 / 뒤로·앞으로 가기
         * =================================================================== */
        // 에디터(TinyMCE)가 준비된 뒤 콜백을 실행한다. (setHtml/유형전환이 에디터에 의존)
        //  - detailEditorReady 는 TinyMCE init 콜백(또는 폴백)에서 true 로 바뀐다.
        //  - 모든 핸들러(유형전환 change 등)가 바인딩된 이 시점 이후에 복원해야 안전하다.
        function whenEditorReady(cb){
            if (detailEditorReady) { cb(); return; }
            var tries = 0;
            var timer = setInterval(function(){
                tries++;
                if (detailEditorReady || tries > 200) { // 최대 ~10초 대기
                    clearInterval(timer);
                    cb();
                }
            }, 50);
        }

        // 뒤로/앞으로 가기: 주소의 type/saveId 로 선택을 다시 맞춘다.
        window.addEventListener('popstate', function(){
            whenEditorReady(restoreFromUrl);
        });

        // 최초 진입: 주소에 type/saveId 가 있으면 그 선택을 복원한다.
        //  - 아무 파라미터도 없으면, 현재(기본) 상태를 주소에 한 번 심어 둔다(replaceState).
        whenEditorReady(function(){
            var q = new URLSearchParams(window.location.search);
            if (q.get('type') || q.get('saveId')) {
                restoreFromUrl();
            } else {
                syncUrl(true);
            }
        });
    });
</script>
@endsection
