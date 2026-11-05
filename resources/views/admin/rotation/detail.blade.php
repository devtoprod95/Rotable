@extends('layouts.app')

@section('title', '상세페이지 제작')

{{-- 제목(상세페이지 제작) 옆에 표시: 마지막 저장(누가·언제). 현재 선택 유형 기준. --}}
@section('pageTitleExtra')
    <span id="detailSavedInfo"
          class="badge bg-dark text-white fw-semibold align-middle"
          style="display:none; font-size:0.8125rem; padding:0.4em 0.7em;"></span>
@endsection

@section('content')
{{-- 이미지 다운로드용 modern-screenshot (html-to-image 계열, SVG foreignObject 렌더).
     html2canvas 와 달리 브라우저가 그리는 그대로(여백·패딩·height:auto 포함) 캡처하므로
     미리보기/에디터와 결과물의 여백 차이가 생기지 않는다. UMD 전역: modernScreenshot --}}
<script src="https://cdn.jsdelivr.net/npm/modern-screenshot@4.7.0/dist/index.js"></script>
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

                    {{-- 편집 그룹: 페이지 설정 / 참여자 명단 편집 / 샘플 불러오기 --}}
                    <button type="button" class="btn btn-pink btn-open-settings">
                        <i class="ti ti-adjustments me-1"></i> 페이지 설정
                    </button>
                    <button type="button" class="btn btn-pink btn-open-roster">
                        <i class="ti ti-users-group me-1"></i> 참여자 명단 편집
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
                                <i class="ti ti-download me-2 fs-2"></i> 이미지 다운로드
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
{{-- 에디터 HTML 안의 data-detail-settings(현재 설정)을 읽어 폼에 채우고,
     [설정 적용] 시 data-setting 마커가 붙은 노출 지점의 값을 갈아끼운다.
     (참여자 명단 편집과 동일하게 "요소 기준"이라 주석이 지워져도 안전하게 동작한다.) --}}
<div class="modal modal-blur fade" id="detailSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-adjustments me-1"></i> 페이지 설정
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <div class="text-secondary">
                        <b>남/녀 나이 · 남/녀 키</b>를 입력하고 <b>[설정 적용]</b> 을 누르면
                        상세페이지의 해당 부분(참여 조건·자격요건)에 바로 반영돼요.
                        <br>샘플을 다시 불러와도 <b>설정값은 참여자 명단처럼 그대로 따라옵니다.</b>
                    </div>
                </div>

                <div id="settingsFormBody">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">남성 나이 (최소 ~ 최대)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="settingMaleAgeMin" placeholder="최소" min="19" max="99">
                                <span class="input-group-text">~</span>
                                <input type="number" class="form-control" id="settingMaleAgeMax" placeholder="최대" min="19" max="99">
                                <span class="input-group-text">세</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">여성 나이 (최소 ~ 최대)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="settingFemaleAgeMin" placeholder="최소" min="19" max="99">
                                <span class="input-group-text">~</span>
                                <input type="number" class="form-control" id="settingFemaleAgeMax" placeholder="최대" min="19" max="99">
                                <span class="input-group-text">세</span>
                            </div>
                        </div>
                    </div>
                    <small class="form-hint text-muted d-block mt-1 mb-3">
                        나이(세)를 입력하면 <b>자동으로 "OO~OO년생"</b> 으로 환산돼 참여 조건에 "남성 OOO / 여성 OOO" 로 표기돼요.
                        <span id="settingAgePreview" class="text-azure fw-bold"></span>
                    </small>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">남성 키 (최소 신장)</label>
                            <input type="text" class="form-control" id="settingMaleHeight" placeholder="예: 175cm">
                            <small class="form-hint text-muted">남성 참가 자격요건에 "신장 OOO 이상" 으로 표기돼요.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">여성 키 (최소 신장)</label>
                            <input type="text" class="form-control" id="settingFemaleHeight" placeholder="미입력 시 표기 안 함">
                            <small class="form-hint text-muted">입력하면 여성 참가 자격요건에 "신장 OOO 이상" 이 추가돼요. (비우면 미표기)</small>
                        </div>
                    </div>
                </div>

                <div id="settingsEmpty" class="text-center text-secondary py-5" style="display:none;">
                    <i class="ti ti-alert-triangle fs-1 d-block mb-2"></i>
                    현재 내용에서 설정 영역을 찾지 못했어요.<br>
                    먼저 <b>1열 / 2열 샘플</b>을 불러온 뒤 다시 시도해 주세요.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary btn-settings-apply">
                    <i class="ti ti-check me-1"></i> 설정 적용
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 참여자 명단 편집 모달 --}}
<div class="modal modal-blur fade" id="detailRosterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-users-group me-1"></i> 참여자 명단 편집
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <div class="text-secondary">
                        표에 <b>키 · 직업 · 나이</b>만 입력하면 됩니다. <b>번호(N호)와 상단 인원수(남/여)</b>는 자동으로 매겨져요.
                        HTML 을 직접 건드릴 필요 없이, 아래에서 행을 추가/삭제한 뒤 <b>[명단 적용]</b> 을 누르세요.
                    </div>
                </div>

                {{-- 성비 자동 맞춤 토글 --}}
                <div class="card mb-3">
                    <div class="card-body py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <label class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="rosterBalanceToggle" checked>
                            <span class="form-check-label fw-bold">
                                성비 자동 맞춤
                                <span class="text-secondary fw-normal">— 부족한 쪽을 "모집중" 자리로 채워 N:N 으로 보이게</span>
                            </span>
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary small">표시 문구</span>
                            <input type="text" class="form-control form-control-sm" id="rosterRecruitLabel"
                                   style="width:120px;" value="모집중" maxlength="10">
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary btn-roster-apply">
                    <i class="ti ti-check me-1"></i> 명단 적용
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
                    setHtml((res && res.html) ? res.html : '');
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
        function computeSafeScale(cssWidth, cssHeight, desired){
            var MAX_SIDE = 12000;             // 한 변 최대 안전 픽셀 (여유 있게)
            var MAX_AREA = 40 * 1000 * 1000;  // 최대 면적(px^2) — 모바일 메모리 안전치
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
         * - PNG 가 10MB 를 넘으면 JPEG 로 품질을 떨어뜨리는 대신, 해상도만 완만히(0.92배씩)
         *   줄여 10MB 이하가 되는 가장 큰 PNG 를 저장한다. (텍스트 뭉개짐 없이 선명 유지)
         * - 아주 극단적으로 못 맞추는 경우에만 최후수단으로 고품질 JPEG 로 저장한다.
         * =================================================================== */
        var EXPORT_MAX_BYTES = 20 * 1024 * 1024; // 20MB (선명도 우선, 상한)

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

            await salert({text: '이미지를 20MB 이하로 저장하지 못했습니다. 내용을 줄인 뒤 다시 시도해주세요.', cancel: false});
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
                // 빈 블록으로 취급돼 삭제되지 않도록, 캐리어/스페이서 류를 non-empty 로 등록.
                non_empty_elements: 'div[data-detail-settings],div[data-roster-grid]',
                keep_styles: true,
                verify_html: false,
                content_style: "@import url('https://fonts.googleapis.com/css2?family=Jua&family=Gothic+A1:wght@400;500;700;800;900&display=swap'); body{font-family:'Gothic A1','Pretendard','Apple SD Gothic Neo',sans-serif; font-weight:700; font-size:14px;}"
            });
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
         * =================================================================== */
        var rosterModal = null;

        function getRosterModal(){
            if (rosterModal) { return rosterModal; }
            var el = document.getElementById('detailRosterModal');
            var ModalClass =
                (window.tabler && window.tabler.Modal) ||
                (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                (window.bootstrap && window.bootstrap.Modal) ||
                (window.coreui && window.coreui.Modal) ||
                window.Modal;
            if(!ModalClass){ console.error('Modal 클래스를 찾을 수 없습니다.'); return null; }
            rosterModal = new ModalClass(el);
            return rosterModal;
        }

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
                var $cols  = $inner.children('div').last().children('div');
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
            var cellHeadSize  = c.cellHeadSize  || '21px';
            var cellNoSize    = c.cellNoSize    || '23px';
            var cellInfoSize  = c.cellInfoSize  || '26px';
            var cellHeight    = c.cellHeight    || '108px';
            var cellPadding   = c.cellPadding   || '10px';
            var balance       = c.balance !== false; // 기본 ON
            var recruitLabel  = (c.recruitLabel && String(c.recruitLabel).trim()) || '모집중';

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
                html +=   '<div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; height:100%;">';
                // 행사명 + 날짜
                html +=     '<div style="background:#eef2f7; padding:7px 6px; border-bottom:1px solid #e5e7eb; text-align:center;">';
                html +=       '<div style="font-size:' + cellTitleSize + '; font-weight:900; word-break:keep-all;">' + esc(card.title) + '</div>';
                html +=       '<div style="font-size:' + cellDateSize + '; color:#374151; font-weight:800; margin-top:1px;">' + esc(card.date) + '</div>';
                html +=     '</div>';
                // 남/여 헤더 (실제 참여자 수만 표시 — 모집중 더미는 제외)
                html +=     '<div style="display:flex; font-size:' + cellHeadSize + '; font-weight:900; text-align:center; border-bottom:1px solid #eee;">';
                html +=       '<div style="width:50%; padding:4px; background:#3b82f6; color:#ffffff;">남 ' + maleHeadCount + '</div>';
                html +=       '<div style="width:50%; padding:4px; background:#ec4899; color:#ffffff;">여 ' + femaleHeadCount + '</div>';
                html +=     '</div>';
                // 남/여 명단
                html +=     '<div style="display:flex;">';
                html +=       '<div style="width:50%; box-sizing:border-box; border-right:1px solid #c7d7ee; background:#eff6ff;">';
                html +=         columnCells(males, maleTotal, '#d7e3ef');
                html +=       '</div>';
                html +=       '<div style="width:50%; box-sizing:border-box; background:#fdf2f8;">';
                html +=         columnCells(females, femaleTotal, '#f6d9e6');
                html +=       '</div>';
                html +=     '</div>';
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
                var $card = $('<div class="card mb-3"></div>');
                var $head = $('<div class="card-header d-flex flex-wrap gap-2 align-items-end"></div>');
                $head.append(
                    '<div><label class="form-label mb-1 small">행사명</label>'
                    + '<input type="text" class="form-control form-control-sm roster-ev-title" style="min-width:200px;" value="' + $('<i>').text(card.title).html() + '"></div>'
                );
                $head.append(
                    '<div><label class="form-label mb-1 small">날짜</label>'
                    + '<input type="text" class="form-control form-control-sm roster-ev-date" style="min-width:200px;" value="' + $('<i>').text(card.date).html() + '"></div>'
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
        }

        // 한 성별 표 컬럼(헤더: 인원수 live, 바디: 행들)
        function buildGenderCol(gender, label, toneClass, people){
            var $col = $('<div class="col-md-6"></div>');
            $col.attr('data-gender', gender);
            $col.append(
                '<div class="d-flex align-items-center justify-content-between mb-2">'
                + '<span class="badge ' + toneClass + ' fs-3">' + label + ' <b class="roster-count">0</b>명</span>'
                + '<div class="btn-group">'
                +   '<button type="button" class="btn btn-sm btn-outline-secondary roster-add"><i class="ti ti-plus"></i> 추가</button>'
                +   '<button type="button" class="btn btn-sm btn-outline-pink roster-add-recruit"><i class="ti ti-heart"></i> 모집중 추가</button>'
                + '</div>'
                + '</div>'
            );
            var $table = $(
                '<table class="table table-sm table-vcenter mb-0">'
                + '<thead><tr>'
                + '<th style="width:40px;">#</th><th>키(cm)</th><th>직업</th><th>나이</th><th style="width:44px;"></th>'
                + '</tr></thead><tbody class="roster-rows"></tbody></table>'
            );
            var $tbody = $table.find('.roster-rows');
            (people || []).forEach(function(p){ $tbody.append(rosterRow(p)); });
            $col.append($table);
            return $col;
        }

        function rosterRow(p){
            p = p || {};
            // "모집중" 자리 행: 입력칸 대신 '모집중' 표시만 둔다. (data-recruit 로 구분)
            if (p.recruit) {
                return $(
                    '<tr data-recruit="1" class="bg-pink-lt">'
                    + '<td class="roster-no text-secondary"></td>'
                    + '<td colspan="3" class="fw-bold text-pink"><i class="ti ti-heart-filled me-1"></i>모집중 자리 💗</td>'
                    + '<td><button type="button" class="btn btn-sm btn-ghost-danger roster-del"><i class="ti ti-trash"></i></button></td>'
                    + '</tr>'
                );
            }
            return $(
                '<tr>'
                + '<td class="roster-no text-secondary"></td>'
                + '<td><input type="text" class="form-control form-control-sm roster-height" value="' + $('<i>').text(p.height || '').html() + '"></td>'
                + '<td><input type="text" class="form-control form-control-sm roster-job" value="' + $('<i>').text(p.job || '').html() + '"></td>'
                + '<td><input type="text" class="form-control form-control-sm roster-age" value="' + $('<i>').text(p.age || '').html() + '"></td>'
                + '<td><button type="button" class="btn btn-sm btn-ghost-danger roster-del"><i class="ti ti-trash"></i></button></td>'
                + '</tr>'
            );
        }

        // 각 컬럼의 번호(N호)와 인원수 뱃지를 다시 매긴다.
        // 번호(N호)는 모집중 자리 포함 전체 순번, 인원수 뱃지는 실제 참여자(모집중 제외)만 센다.
        function renumberRoster($scope){
            ($scope || $('#rosterEditorBody')).find('[data-gender]').each(function(){
                var $col = $(this);
                var $rows = $col.find('.roster-rows > tr');
                $rows.each(function(i){ $(this).find('.roster-no').text((i + 1) + '호'); });
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

        // '참여자 명단 편집' 열기
        $('.btn-open-roster').on('click', function(){
            var loc  = locateRosterGrid(getHtml());
            var data = loc ? parseRoster(loc.grid) : null;

            if (!data || !data.cards.length) {
                $('#rosterEditorBody').empty();
                $('#rosterEditorEmpty').show();
            } else {
                $('#rosterEditorEmpty').hide();
                rosterBaseConfig = data.config || {};
                // 성비 자동 맞춤 토글/문구를 config 값으로 초기화 (기본 ON, '모집중')
                $('#rosterBalanceToggle').prop('checked', rosterBaseConfig.balance !== false);
                $('#rosterRecruitLabel').val(rosterBaseConfig.recruitLabel || '모집중');
                renderRosterEditor(data);
                renumberRoster();
            }
            var m = getRosterModal();
            if (m) { m.show(); }
        });

        // 행 추가/삭제 (이벤트 위임)
        $('#rosterEditorBody').on('click', '.roster-add', function(){
            $(this).closest('[data-gender]').find('.roster-rows').append(rosterRow({}));
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

        // [명단 적용] → 그리드 재생성해 에디터에 반영
        $('.btn-roster-apply').on('click', async function(){
            var loc = locateRosterGrid(getHtml());
            if (!loc) {
                await salert({text: '명단 영역을 찾지 못했습니다. 샘플을 먼저 불러와 주세요.', cancel: false});
                return;
            }
            // 성비 자동 맞춤 토글/문구를 config 에 반영한다.
            var cfg = Object.assign({}, rosterBaseConfig, {
                balance:      $('#rosterBalanceToggle').is(':checked'),
                recruitLabel: ($('#rosterRecruitLabel').val() || '모집중').trim() || '모집중'
            });
            var data    = collectRoster(cfg);
            var newGrid = buildRosterGrid(data);
            // 분리된 DOM 에서 기존 그리드 요소를 새 그리드로 교체한 뒤 전체 HTML 직렬화.
            loc.grid.replaceWith(newGrid);
            setHtml(loc.root.html());

            var m = getRosterModal();
            if (m) { m.hide(); }
            await salert({text: '명단을 반영했어요. 내용을 확인한 뒤 [저장]을 눌러 주세요.', cancel: false});
        });

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
            var hasCurrent = curCard && ((curCard.males && curCard.males.length) || (curCard.females && curCard.females.length));
            if (!hasCurrent) { return sampleHtml; } // 현재 명단 없으면 샘플 그대로

            // 2) 샘플 HTML 파싱
            var $sample = $('<div></div>').html(sampleHtml);
            var $grid = $sample.find('[data-roster-grid]').first();
            if ($grid.length === 0) { return sampleHtml; } // 샘플에 명단 영역 없으면 그대로

            var sampleData = parseRoster($grid);
            if (!sampleData || !sampleData.cards.length) { return sampleHtml; }

            // 3) 샘플의 각 카드에 현재 값을 채운다.
            //    - 명단(males/females): 첫 현재 카드 기준 (기존 동작 유지)
            //    - 행사명/날짜(title/date): 같은 인덱스의 현재 카드 값 우선, 없으면 첫 카드 값으로 유지
            sampleData.cards.forEach(function(card, i){
                var src = curCards[i] || curCard; // 같은 위치 현재 카드 없으면 첫 카드로 폴백
                card.males   = curCard.males.slice();
                card.females = curCard.females.slice();
                card.title   = (src && src.title != null) ? src.title : card.title;
                card.date    = (src && src.date  != null) ? src.date  : card.date;
            });

            // 4) 새 그리드로 다시 그려 샘플 HTML 의 명단 영역만 치환
            $grid.replaceWith(buildRosterGrid(sampleData));
            return $sample.html();
        }

        /* =====================================================================
         * 페이지 설정 (남/녀 나이 · 남/녀 키)
         * - 에디터 HTML 안의 data-detail-settings(JSON) 로 현재 설정을 읽고,
         *   data-setting="키" 로 마킹된 노출 지점의 값을 갈아끼운다.
         * - 참여자 명단과 동일하게 "요소/data 속성 기준"이라 주석이 지워져도 안전.
         * - 샘플을 다시 불러와도 현재 설정이 그대로 따라오게 한다. (applyCurrentSettingsToSample)
         * =================================================================== */
        var settingsModal = null;
        // 설정 키 목록과 기본값. (data-detail-settings 가 없을 때의 폴백)
        // 나이는 숫자(세) 최소/최대로 보관하고, 표기용 년생 텍스트(maleAgeText/femaleAgeText)는 파생값이다.
        var SETTING_KEYS = ['maleAgeMin', 'maleAgeMax', 'femaleAgeMin', 'femaleAgeMax',
                            'maleAgeText', 'femaleAgeText', 'maleHeight', 'femaleHeight'];

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

        // 캐리어가 없거나 숫자 나이가 비었을 때, 화면에 보이는 마커(data-setting)에서 설정을 복원한다.
        //  - 최초 접근 시 에디터가 캐리어를 지웠어도, 참여 조건/자격요건에 "보이는 값"으로 폼을 채운다.
        //  - 입력: 분리된 DOM($root). 출력: 폼 채우기용 설정 객체.
        function readSettingsFromDom($root){
            var s = {};
            // 1) 캐리어가 있으면 우선 사용.
            var $carrier = $root.find('[data-detail-settings]').first();
            if ($carrier.length) {
                try { s = JSON.parse($carrier.attr('data-detail-settings') || '{}'); } catch(e){ s = {}; }
            }

            // 2) 숫자 나이(min/max)가 비어 있으면 참여 조건 라인에서 역산해 채운다.
            var needAges = (s.maleAgeMin == null || s.maleAgeMin === '')
                        || (s.femaleAgeMin == null || s.femaleAgeMin === '');
            if (needAges) {
                var line = $root.find('[data-setting="ageConditionLine"]').first().text() || '';
                // "남성 88~02년생 / 여성 88~02년생" → 남/녀 구간 분리
                var parts = line.split('/');
                var maleSeg   = parts[0] || '';
                var femaleSeg = parts[1] || '';
                var ma = settingAgesFromText(maleSeg);
                var fa = settingAgesFromText(femaleSeg);
                if (s.maleAgeMin == null || s.maleAgeMin === '')   { s.maleAgeMin = ma.min; }
                if (s.maleAgeMax == null || s.maleAgeMax === '')   { s.maleAgeMax = ma.max; }
                if (s.femaleAgeMin == null || s.femaleAgeMin === ''){ s.femaleAgeMin = fa.min; }
                if (s.femaleAgeMax == null || s.femaleAgeMax === ''){ s.femaleAgeMax = fa.max; }
            }

            // 3) 키(신장)도 비어 있으면 마커 텍스트("175cm 이상")에서 숫자/단위만 뽑아 채운다.
            var stripHeight = function(t){
                return String(t || '').replace(/\s*이상\s*$/, '').trim();
            };
            if (s.maleHeight == null || s.maleHeight === '') {
                s.maleHeight = stripHeight($root.find('[data-setting="maleHeight"]').first().text());
            }
            if (s.femaleHeight == null || s.femaleHeight === '') {
                s.femaleHeight = stripHeight($root.find('[data-setting="femaleHeight"]').first().text());
            }

            SETTING_KEYS.forEach(function(k){ if (s[k] == null) { s[k] = ''; } });
            return s;
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

        // 캐리어의 data-detail-settings(JSON) → 설정 객체. 누락 키는 빈 문자열로 채운다.
        function parseSettings($carrier){
            var s = {};
            try { s = JSON.parse($carrier.attr('data-detail-settings') || '{}'); } catch(e){ s = {}; }
            SETTING_KEYS.forEach(function(k){ if (s[k] == null) { s[k] = ''; } });
            return s;
        }

        // 분리된 DOM($root) 에 설정값을 반영한다.
        //  - 캐리어 data-detail-settings 갱신 (다음 번 읽기/샘플 이식용 단일 소스)
        //  - data-setting 마커 지점의 텍스트/표시 갱신
        function applySettingsToDom($root, s){
            // 나이 표기 텍스트는 숫자(세) 최소/최대에서 파생해 항상 최신값으로 재계산한다.
            s.maleAgeText   = settingAgeText(s.maleAgeMin, s.maleAgeMax);
            s.femaleAgeText = settingAgeText(s.femaleAgeMin, s.femaleAgeMax);

            // 1) 캐리어 JSON 갱신 — 없으면 새로 만들어 콘텐츠 맨 앞에 끼운다.
            //    (예전 저장본이거나 에디터가 캐리어를 지운 경우에도 설정이 HTML 에 반드시 실리도록)
            var $carrier = $root.find('[data-detail-settings]').first();
            if ($carrier.length === 0) {
                // 콘텐츠 최상위 래퍼(첫 div)가 있으면 그 안 맨 앞에, 없으면 root 맨 앞에 삽입.
                var $wrapper = $root.children('div').first();
                var $target  = $wrapper.length ? $wrapper : $root;
                // zero-width space 로 non-empty 유지 (TinyMCE 가 빈 요소로 보고 지우지 못하게).
                $carrier = $('<div data-detail-settings style="display:none;">\u200b</div>');
                $target.prepend($carrier);
            }
            $carrier.attr('data-detail-settings', JSON.stringify(s));
            if (($carrier.text() || '').length === 0) { $carrier.text('\u200b'); }

            // 2) 참여 조건 나이 라인 — "남성 OOO / 여성 OOO" (한쪽이라도 비면 그쪽은 빈칸)
            var ageLine = '남성 ' + (s.maleAgeText || '') + ' / 여성 ' + (s.femaleAgeText || '');
            $root.find('[data-setting="ageConditionLine"]').text(ageLine);

            // 3) 남성 키 — "신장 OOO 이상" (비우면 "이상"만 남지 않게 빈 문자열)
            $root.find('[data-setting="maleHeight"]').text((s.maleHeight || '').trim() !== '' ? (s.maleHeight + ' 이상') : '');

            // 4) 여성 키 — 입력 시 "신장 OOO 이상" 표시, 비우면 줄 숨김
            var hasFemaleHeight = (s.femaleHeight || '').trim() !== '';
            $root.find('[data-setting="femaleHeight"]').text(hasFemaleHeight ? (s.femaleHeight + ' 이상') : '');
            $root.find('[data-setting-row="femaleHeight"]').css('display', hasFemaleHeight ? '' : 'none');

            return $root;
        }

        // 현재 에디터 HTML 에 설정 s 를 반영한 새 HTML 문자열을 돌려준다.
        // 캐리어가 없어도 applySettingsToDom 이 새로 만들어 끼우므로 항상 설정이 실린다.
        function applySettingsToHtml(html, s){
            var $root = $('<div></div>').html(html || '');
            applySettingsToDom($root, s);
            return $root.html();
        }

        // 폼 입력 → 설정 객체
        function collectSettingsForm(){
            return {
                maleAgeMin:    ($('#settingMaleAgeMin').val()    || '').trim(),
                maleAgeMax:    ($('#settingMaleAgeMax').val()    || '').trim(),
                femaleAgeMin:  ($('#settingFemaleAgeMin').val()  || '').trim(),
                femaleAgeMax:  ($('#settingFemaleAgeMax').val()  || '').trim(),
                maleHeight:    ($('#settingMaleHeight').val()   || '').trim(),
                femaleHeight:  ($('#settingFemaleHeight').val() || '').trim()
            };
        }

        // 설정 객체 → 폼 입력 채우기
        function fillSettingsForm(s){
            $('#settingMaleAgeMin').val(s.maleAgeMin || '');
            $('#settingMaleAgeMax').val(s.maleAgeMax || '');
            $('#settingFemaleAgeMin').val(s.femaleAgeMin || '');
            $('#settingFemaleAgeMax').val(s.femaleAgeMax || '');
            $('#settingMaleHeight').val(s.maleHeight || '');
            $('#settingFemaleHeight').val(s.femaleHeight || '');
            updateAgePreview();
        }

        // 입력한 나이(세)를 년생으로 환산한 결과를 폼 아래 실시간 미리보기로 보여준다.
        function updateAgePreview(){
            var male   = settingAgeText($('#settingMaleAgeMin').val(), $('#settingMaleAgeMax').val());
            var female = settingAgeText($('#settingFemaleAgeMin').val(), $('#settingFemaleAgeMax').val());
            var parts = [];
            if (male)   { parts.push('남성 ' + male); }
            if (female) { parts.push('여성 ' + female); }
            $('#settingAgePreview').text(parts.length ? ('→ ' + parts.join(' / ')) : '');
        }
        // 나이 입력칸을 바꿀 때마다 미리보기 갱신.
        $('#settingMaleAgeMin, #settingMaleAgeMax, #settingFemaleAgeMin, #settingFemaleAgeMax')
            .on('input', updateAgePreview);

        // '페이지 설정' 열기 — 현재 에디터의 설정을 읽어 폼에 채운다.
        // 캐리어가 없어도(최초 접근·예전 저장본 등) 화면에 보이는 값(참여조건/자격요건)에서 복원해 채운다.
        $('.btn-open-settings').on('click', function(){
            var html = getHtml();
            if (!html || !html.trim()) {
                // 아예 내용이 없을 때만 안내.
                $('#settingsFormBody').hide();
                $('#settingsEmpty').show();
            } else {
                $('#settingsEmpty').hide();
                $('#settingsFormBody').show();
                var $root = $('<div></div>').html(html);
                fillSettingsForm(readSettingsFromDom($root));
            }
            var m = getSettingsModal();
            if (m) { m.show(); }
        });

        // [설정 적용] → 마커 지점 값을 갈아끼워 에디터에 반영 (캐리어 없으면 생성)
        $('.btn-settings-apply').on('click', async function(){
            var html = getHtml();
            if (!html || !html.trim()) {
                await salert({text: '먼저 샘플을 불러오거나 내용을 작성해 주세요.', cancel: false});
                return;
            }
            var s = collectSettingsForm();
            setHtml(applySettingsToHtml(html, s));

            var m = getSettingsModal();
            if (m) { m.hide(); }
            await salert({text: '설정을 반영했어요. 내용을 확인한 뒤 [저장]을 눌러 주세요.', cancel: false});
        });

        // 샘플 HTML 에 "현재 에디터의 설정값"을 이식해서 돌려준다. (참여자 명단과 동일 원리)
        // - 캐리어가 없어도 화면 마커에서 복원(readSettingsFromDom)해 새 샘플에 그대로 반영한다.
        function applyCurrentSettingsToSample(sampleHtml){
            var html = getHtml();
            if (!html || !html.trim()) { return sampleHtml; }
            var $root = $('<div></div>').html(html);
            var curSettings = readSettingsFromDom($root);
            return applySettingsToHtml(sampleHtml, curSettings);
        }

        // 1열 샘플 (선택한 유형 기준)
        $('.btn-sample-one').on('click', async function(){
            var type = currentType();
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            if(await salert({text: '현재 작성 내용을 [' + label + '] 1열 샘플로 교체하시겠습니까?\n(현재 참여자 명단·행사명/시간·페이지 설정은 그대로 유지됩니다)'})){
                // 참여자 명단에 이어 페이지 설정(행사명·나이·키)도 현재 값으로 이식한다.
                setHtml(applyCurrentSettingsToSample(applyCurrentRosterToSample(SAMPLES[type][1])));
            }
        });

        // 2열 샘플 (선택한 유형 기준)
        $('.btn-sample-two').on('click', async function(){
            var type = currentType();
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            if(await salert({text: '현재 작성 내용을 [' + label + '] 2열 샘플로 교체하시겠습니까?\n(현재 참여자 명단·행사명/시간·페이지 설정은 그대로 유지됩니다)'})){
                // 참여자 명단에 이어 페이지 설정(행사명·나이·키)도 현재 값으로 이식한다.
                setHtml(applyCurrentSettingsToSample(applyCurrentRosterToSample(SAMPLES[type][2])));
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

        // 이미지(PNG) 다운로드 — 모달 상태와 무관하게 오프스크린 영역에 렌더해서 캡처
        $('.btn-detail-download').on('click', async function(){
            if (typeof modernScreenshot === 'undefined') {
                await salert({text: '이미지 변환 모듈을 불러오지 못했습니다. 페이지를 새로고침해주세요.', cancel: false});
                return;
            }

            var html = getHtml();
            if (!html || !html.trim()) {
                await salert({text: '다운로드할 내용이 없습니다. 먼저 내용을 작성해주세요.', cancel: false});
                return;
            }

            // 드롭다운이 닫혀도 보이도록 화면 전체 로딩 오버레이로 진행 표시.
            showExportOverlay('이미지 생성 중…');
            await new Promise(function(r){ setTimeout(r, 30); });

            // 화면 밖(보이지만 레이아웃은 정상)인 캡처 전용 컨테이너에 '진짜 1080px'로 렌더한다.
            // modern-screenshot 은 이 노드를 브라우저가 그리는 그대로(여백·패딩 포함) 캡처한다.
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
            // 콘텐츠 끝에 미리보기와 '동일한' 하단 스페이서를 붙여 아래 여백을 맞춘다.
            holder.innerHTML = html
                + '<div data-capture-spacer="1" style="width:100%; height:48px; background:#ffffff;"></div>';

            try {
                // 폰트가 캡처 전에 완전히 로드되도록 기다린다. (미로드 시 폴백 폰트로 찍히는 문제 방지)
                if (document.fonts && document.fonts.ready) {
                    try { await document.fonts.ready; } catch (fe) { /* 무시 */ }
                }

                // 캡처 대상의 실제 크기 (modern-screenshot 이 그대로 반영).
                var capW = holder.offsetWidth || 1080;
                var capH = holder.scrollHeight || holder.offsetHeight || 1080;

                // 선명도(scale)는 높이되, 캔버스 크기 한계를 넘지 않는 안전값으로.
                var detailScale = computeSafeScale(capW, capH, 3.5);

                var canvas = await modernScreenshot.domToCanvas(holder, {
                    backgroundColor: '#ffffff',
                    width: capW,
                    height: capH,
                    scale: detailScale,
                    // 외부 이미지(폰트/배너/스샷)는 CORS 우회를 위해 fetch 후 data-URI 로 인라인.
                    fetch: { requestInit: { mode: 'cors', cache: 'no-cache' } }
                });

                // ===== 10MB(실제 20MB) 상한 + 화질 최우선 저장 (기존 로직 재사용) =====
                await exportCanvasUnder10MB(canvas, buildDownloadBaseName('rotable_상세페이지'));
            } catch (e) {
                await salert({text: '이미지 생성 중 오류가 발생했습니다: ' + (e?.message || e), cancel: false});
            } finally {
                hideExportOverlay();
            }
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
                var feedScale = computeSafeScale(feedW, feedH, 3.5);

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
        // 뒤로/앞으로 가기: 주소의 type/saveId 로 선택을 다시 맞춘다.
        window.addEventListener('popstate', function(){
            restoreFromUrl();
        });

        // 최초 진입: 주소에 type/saveId 가 있으면 그 선택을 복원한다.
        //  - 아무 파라미터도 없으면, 현재(기본) 상태를 주소에 한 번 심어 둔다(replaceState).
        (function(){
            var q = new URLSearchParams(window.location.search);
            if (q.get('type') || q.get('saveId')) {
                restoreFromUrl();
            } else {
                syncUrl(true);
            }
        })();
    });
</script>
@endsection
