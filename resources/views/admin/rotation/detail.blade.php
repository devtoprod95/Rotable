@extends('layouts.app')

@section('title', '상세페이지 제작')

{{-- 제목(상세페이지 제작) 옆에 표시: 마지막 저장(누가·언제). 현재 선택 유형 기준. --}}
@section('pageTitleExtra')
    <span id="detailSavedInfo"
          class="badge bg-dark text-white fw-semibold align-middle"
          style="display:none; font-size:0.8125rem; padding:0.4em 0.7em;"></span>
@endsection

@section('content')
{{-- 이미지 다운로드용 html2canvas (레이아웃의 html2pdf 번들은 전역 html2canvas 를 노출하지 않아 별도 로드) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
                    {{-- 샘플 유형 선택 --}}
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

                    {{-- 자주 쓰는 핵심 동작만 밖에 노출 --}}
                    <button type="button" class="btn btn-pink btn-open-roster">
                        <i class="ti ti-users-group me-1"></i> 참여자 명단 편집
                    </button>
                    <button type="button" class="btn btn-azure btn-open-preview">
                        <i class="ti ti-eye me-1"></i> 미리보기
                    </button>
                    <button type="button" class="btn btn-primary btn-detail-save">
                        <i class="ti ti-device-floppy me-1"></i> 저장
                    </button>

                    {{-- 나머지(샘플 불러오기·다운로드 계열)는 더보기 드롭다운으로 접음 --}}
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-dots me-1"></i> 더보기
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">샘플 불러오기</h6>
                            <button type="button" class="dropdown-item btn-sample-one">
                                <i class="ti ti-layout-rows me-2 fs-2"></i> 1열 샘플
                            </button>
                            <button type="button" class="dropdown-item btn-sample-two">
                                <i class="ti ti-layout-columns me-2 fs-2"></i> 2열 샘플
                            </button>
                            <div class="dropdown-divider"></div>
                            <h6 class="dropdown-header">이미지 다운로드</h6>
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
                    <b>유형(와인파티 / 1:1 소개팅)</b>을 먼저 고른 뒤 <b>1열 / 2열 샘플</b>을 누르면 해당 유형의 샘플이 적용됩니다.
                    유형별로 상단 이미지와 로테이블 포인트가 다릅니다. 이후 HTML 에디터로 자유롭게 꾸미고 [저장]하면 유지됩니다.
                    우측 하단 <b>미리보기</b> 버튼으로 결과를 확인하세요.
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
                <div class="d-flex justify-content-center">
                    {{-- 이 영역이 그대로 이미지로 캡처됩니다. --}}
                    <div id="detailPreview" style="width: 1080px; max-width: 100%; background:#ffffff; border-radius:8px;"></div>
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

        // 현재 에디터에 올라와 있는 유형 (초기값: 와인)
        var activeType = 'wine';

        function currentType(){
            return $('input[name="sampleType"]:checked').val() === 'solo' ? 'solo' : 'wine';
        }

        // 제목 옆 "마지막 저장" 배지를 현재 선택한 유형의 메타로 갱신한다.
        function renderSavedInfo(){
            var meta = META[currentType()];
            var $info = $('#detailSavedInfo');
            if (meta && meta.saved_at) {
                var who = meta.saved_by || '알 수 없음';
                $info.html('마지막 저장: '
                    + who + ' · ' + meta.saved_at);
                $info.show();
            } else {
                $info.hide().empty();
            }
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
            $('#detailPreview').html(html || '<div style="padding:40px; text-align:center; color:#9ca3af;">내용이 비어 있습니다.<br>1열/2열 샘플을 눌러보세요.</div>');
        }

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
                        // "모집중" 자리(data-recruit)는 실제 인원이 아니므로 건너뛴다.
                        if ($(this).attr('data-recruit')) { return; }
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
            var cellInfoSize  = c.cellInfoSize  || '19px';
            var cellHeight    = c.cellHeight    || '84px';
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
            // "모집중" 자리 칸 (번호는 이어서, data-recruit 로 표시해 재파싱 때 제외)
            function recruitCell(i, borderColor){
                return ''
                  + '<div data-recruit="1" style="height:' + cellHeight + '; padding:' + cellPadding + '; border-bottom:2px dashed ' + borderColor + '; text-align:center; display:flex; flex-direction:column; justify-content:center; background:repeating-linear-gradient(45deg,#ffffff,#ffffff 8px,#fafafa 8px,#fafafa 16px);">'
                  +   '<div style="font-weight:900; font-size:' + cellNoSize + '; color:#9ca3af;">' + (i + 1) + '호</div>'
                  +   '<div style="color:#f472b6; font-weight:900; font-size:' + cellInfoSize + '; margin-top:4px; word-break:keep-all;">' + esc(recruitLabel) + ' 💗</div>'
                  + '</div>';
            }
            // 한 컬럼 렌더: 실제 인원 + (balance 시) 모집중으로 total 까지 채움
            function columnCells(people, total, borderColor){
                var out = '';
                people.forEach(function(p, i){ out += personCell(p, i, borderColor); });
                for (var i = people.length; i < total; i++) { out += recruitCell(i, borderColor); }
                return out;
            }

            // config 를 속성(홑따옴표로 감쌈)에 안전하게 싣기 위해 작은따옴표를 엔티티로 치환.
            var configAttr = JSON.stringify(c).replace(/'/g, '&#39;');
            var html = '<div style="display:flex; flex-wrap:wrap;" data-roster-grid="1" data-roster-config=\'' + configAttr + '\'>';

            data.cards.forEach(function(card, idx){
                var males = card.males || [], females = card.females || [];
                // balance 켜지면 남/여 중 많은 쪽 수에 맞춰 모집중으로 '셀'을 채운다.
                var total = balance ? Math.max(males.length, females.length) : null;
                var maleTotal   = balance ? total : males.length;
                var femaleTotal = balance ? total : females.length;
                // 헤더에 표시하는 인원수는 '실제 참여자 수'만 센다. (모집중 더미 자리는 제외)
                var maleHeadCount   = males.length;
                var femaleHeadCount = females.length;

                html += '<div style="width:' + cardWidth + '; box-sizing:border-box; padding:3px;"'
                      + ' data-roster-card="' + idx + '"'
                      + ' data-event-title="' + esc(card.title) + '"'
                      + ' data-event-date="' + esc(card.date) + '">';
                html +=   '<div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; height:100%;">';
                // 행사명 + 날짜
                html +=     '<div style="background:#eef2f7; padding:7px 6px; border-bottom:1px solid #e5e7eb; text-align:center;">';
                html +=       '<div style="font-size:' + cellTitleSize + '; font-weight:900; word-break:keep-all;">' + esc(card.title) + '</div>';
                html +=       '<div style="font-size:' + cellDateSize + '; color:#374151; font-weight:600; margin-top:1px;">' + esc(card.date) + '</div>';
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
                + '<span class="badge ' + toneClass + ' fs-3">' + label + ' <b class="roster-count">' + people.length + '</b>명</span>'
                + '<button type="button" class="btn btn-sm btn-outline-secondary roster-add"><i class="ti ti-plus"></i> 추가</button>'
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
        function renumberRoster($scope){
            ($scope || $('#rosterEditorBody')).find('[data-gender]').each(function(){
                var $col = $(this);
                var $rows = $col.find('.roster-rows > tr');
                $rows.each(function(i){ $(this).find('.roster-no').text((i + 1) + '호'); });
                $col.find('.roster-count').text($rows.length);
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
        });

        // 샘플 HTML 의 명단을 "현재 에디터에 작성된 명단"으로 바꿔서 돌려준다.
        // - 현재 에디터에 명단(data-roster-grid)이 있으면 그 남/여 인원을 추출해,
        //   새 샘플의 모든 행사 카드에 동일하게 채운다. (행사명/날짜는 샘플 값 유지)
        // - 현재 명단이 없거나 비어 있으면 샘플 기본(더미) 명단을 그대로 둔다.
        function applyCurrentRosterToSample(sampleHtml){
            // 1) 현재 에디터 명단 추출
            var $cur = $('<div></div>').html(getHtml());
            var curData = parseRoster($cur.find('[data-roster-grid]').first());
            var curCard = (curData && curData.cards && curData.cards[0]) ? curData.cards[0] : null;
            var hasCurrent = curCard && ((curCard.males && curCard.males.length) || (curCard.females && curCard.females.length));
            if (!hasCurrent) { return sampleHtml; } // 현재 명단 없으면 샘플 그대로

            // 2) 샘플 HTML 파싱
            var $sample = $('<div></div>').html(sampleHtml);
            var $grid = $sample.find('[data-roster-grid]').first();
            if ($grid.length === 0) { return sampleHtml; } // 샘플에 명단 영역 없으면 그대로

            var sampleData = parseRoster($grid);
            if (!sampleData || !sampleData.cards.length) { return sampleHtml; }

            // 3) 샘플의 각 카드 명단을 현재 명단으로 교체 (행사명/날짜는 샘플 유지)
            sampleData.cards.forEach(function(card){
                card.males   = curCard.males.slice();
                card.females = curCard.females.slice();
            });

            // 4) 새 그리드로 다시 그려 샘플 HTML 의 명단 영역만 치환
            $grid.replaceWith(buildRosterGrid(sampleData));
            return $sample.html();
        }

        // 1열 샘플 (선택한 유형 기준)
        $('.btn-sample-one').on('click', async function(){
            var type = currentType();
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            if(await salert({text: '현재 작성 내용을 [' + label + '] 1열 샘플로 교체하시겠습니까?\n(현재 참여자 명단은 그대로 옮겨집니다)'})){
                setHtml(applyCurrentRosterToSample(SAMPLES[type][1]));
            }
        });

        // 2열 샘플 (선택한 유형 기준)
        $('.btn-sample-two').on('click', async function(){
            var type = currentType();
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            if(await salert({text: '현재 작성 내용을 [' + label + '] 2열 샘플로 교체하시겠습니까?\n(현재 참여자 명단은 그대로 옮겨집니다)'})){
                setHtml(applyCurrentRosterToSample(SAMPLES[type][2]));
            }
        });

        // 저장 (유형별 storage 파일에 기록)
        $('.btn-detail-save').on('click', async function(){
            var type  = currentType();
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            if(await salert({text: '[' + label + '] 상세페이지를 저장하시겠습니까?'})){
                var html = getHtml();
                $.ajax({
                    url: "{{ route('admin.rotation.detail.save') }}",
                    type: 'POST',
                    data: { html: html, type: type },
                    success: function(res){
                        SAVED[type] = html;   // 저장 성공 시 보관본 갱신
                        // 서버가 돌려준 저장 메타(누가·언제)로 제목 옆 배지 갱신
                        if (res && res.saved) {
                            META[type] = res.saved;
                            if (type === currentType()) { renderSavedInfo(); }
                        }
                        alert(res?.msg);
                    },
                    error: function(xhr){ alert(xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.'); }
                });
            }
        });

        // 이미지(PNG) 다운로드 — 모달 상태와 무관하게 오프스크린 영역에 렌더해서 캡처
        $('.btn-detail-download').on('click', async function(){
            if (typeof html2canvas === 'undefined') {
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

            // 화면 밖(보이지만 뷰포트 밖)에 캡처 전용 컨테이너를 만든다.
            // display:none 이면 html2canvas 가 크기를 못 재므로, 화면 밖으로 밀어낸다.
            var holder = document.getElementById('detailCaptureHolder');
            if (!holder) {
                holder = document.createElement('div');
                holder.id = 'detailCaptureHolder';
                holder.style.position = 'fixed';
                holder.style.left = '-10000px';
                holder.style.top = '0';
                holder.style.width = '1080px';
                holder.style.background = '#ffffff';
                holder.style.padding = '0';
                document.body.appendChild(holder);
            }
            holder.innerHTML = html;

            try {
                // 캡처 대상 안의 이미지들을 CORS-safe 하게 미리 로드한다. (캔버스 오염 방지)
                var imgs = holder.querySelectorAll('img');
                await Promise.all(Array.prototype.map.call(imgs, function(img){
                    return new Promise(function(resolve){
                        img.setAttribute('crossorigin', 'anonymous');
                        if (img.complete && img.naturalWidth) { resolve(); return; }
                        img.onload = resolve;
                        img.onerror = resolve;
                    });
                }));

                // html2canvas(1.4.1) 는 width:100% + height:auto 이미지의 높이를 0/오계산해
                // 세로로 눌린(찌그러진) 이미지를 그리는 버그가 있다.
                // → 렌더된 실제 폭과 natural 비율로 '픽셀 높이'를 명시적으로 고정해 비율을 보존한다.
                fixImageHeights(holder);

                // 폰트가 캡처 전에 완전히 로드되도록 명시적으로 기다린다. (미로드 시 폴백 폰트로 찍히는 문제 방지)
                if (document.fonts && document.fonts.load) {
                    try {
                        await Promise.all([
                            document.fonts.load("400 44px 'Jua'"),
                            document.fonts.load("700 22px 'Gothic A1'"),
                            document.fonts.load("800 22px 'Gothic A1'"),
                            document.fonts.load("900 22px 'Gothic A1'")
                        ]);
                        await document.fonts.ready;
                    } catch (fe) { /* 폰트 로드 실패해도 캡처는 진행 */ }
                }

                // 선명도는 높이되, 캔버스 크기 한계(브라우저 최대 ~16384px)를 넘으면
                // 빈(흰) 이미지가 되므로 안전 범위 내에서 최대 scale 을 계산한다.
                var detailScale = computeSafeScale(holder.offsetWidth || 1080, holder.offsetHeight || 1080, 3.5);

                var canvas = await html2canvas(holder, {
                    backgroundColor: '#ffffff',
                    scale: detailScale,
                    useCORS: true,
                    allowTaint: false,
                    logging: false
                });

                // ===== 10MB 상한 + 화질 최우선 저장 =====
                // PNG(무손실) 를 유지하면서 10MB 를 넘으면 해상도만 완만히 줄여 맞춘다.
                // (JPEG 저품질로 글자를 뭉개는 대신, 선명한 PNG 를 우선한다.)
                await exportCanvasUnder10MB(canvas, 'rotable_상세페이지');
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
                profile = '<div style="color:#1f2937; font-weight:700; font-size:21px; margin-top:3px; word-break:keep-all;">'
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
            +       '<div style="font-size:24px; font-weight:700; color:#8b5cf6; margin-top:8px; line-height:1.5;">'
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
            if (typeof html2canvas === 'undefined') {
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

            try {
                // 배너 등 이미지가 완전히 로드된 뒤 캡처 (CORS-safe)
                var imgs = target.querySelectorAll('img');
                await Promise.all(Array.prototype.map.call(imgs, function(img){
                    return new Promise(function(resolve){
                        img.setAttribute('crossorigin', 'anonymous');
                        if (img.complete && img.naturalWidth) { resolve(); return; }
                        img.onload = resolve;
                        img.onerror = resolve;
                    });
                }));

                if (document.fonts && document.fonts.ready) {
                    try { await document.fonts.ready; } catch (fe) { /* 무시 */ }
                }

                // 모달이 좁은 화면이면 #feedCapture 가 1080 미만으로 줄어 캡처 해상도가 떨어진다.
                // 캡처 직전에 폭을 1080 으로 고정하고 미리보기 축소(transform)를 풀었다가 끝나면 원복한다.
                var prevWidth = target.style.width;
                var prevMaxWidth = target.style.maxWidth;
                var prevTransform = target.style.transform;
                target.style.width = '1080px';
                target.style.maxWidth = 'none';
                target.style.transform = 'none';

                // 폭 고정 '뒤'(렌더 폭 확정 후) 이미지 높이를 픽셀로 고정해 세로 눌림 방지.
                fixImageHeights(target);

                // 높이는 '명단 길이만큼'(실제 콘텐츠 높이). 폭 1080 기준으로 안전 scale 계산.
                var feedW = 1080;
                var feedH = target.scrollHeight || target.offsetHeight || 1080;
                var feedScale = computeSafeScale(feedW, feedH, 3.5);

                var canvas = await html2canvas(target, {
                    backgroundColor: '#ffffff',
                    scale: feedScale,
                    width: feedW,
                    height: feedH,
                    windowWidth: feedW,
                    useCORS: true,
                    allowTaint: false,
                    logging: false
                });

                target.style.width = prevWidth;
                target.style.maxWidth = prevMaxWidth;
                target.style.transform = prevTransform;
                // 박아둔 인라인 높이 제거(모달 DOM 재사용 대비).
                clearImageHeights(target);

                // 화질 최우선 + 10MB 상한 (상세페이지 다운로드와 동일 로직)
                await exportCanvasUnder10MB(canvas, 'rotable_피드');
            } catch (e) {
                await salert({text: '이미지 생성 중 오류가 발생했습니다: ' + (e?.message || e), cancel: false});
            } finally {
                hideExportOverlay();
            }
        });
    });
</script>
@endsection
