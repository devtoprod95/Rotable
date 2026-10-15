@extends('layouts.app')

@section('title', '상세페이지 제작')

@section('content')
{{-- 이미지 다운로드용 html2canvas (레이아웃의 html2pdf 번들은 전역 html2canvas 를 노출하지 않아 별도 로드) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
{{-- 상세페이지용 웹폰트: @import 보다 빠르게 로드되도록 link 로 선로드 (캡처 시 폰트 적용 보장) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jua&family=Gothic+A1:wght@400;500;700;800;900&display=swap" rel="stylesheet">
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

                    {{-- 열 수 샘플 불러오기 --}}
                    <button type="button" class="btn btn-outline-secondary btn-sample-one">
                        <i class="ti ti-layout-rows me-1"></i> 1열 샘플
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sample-two">
                        <i class="ti ti-layout-columns me-1"></i> 2열 샘플
                    </button>

                    <span class="vr d-none d-md-inline"></span>

                    <button type="button" class="btn btn-azure btn-open-preview">
                        <i class="ti ti-eye me-1"></i> 미리보기
                    </button>
                    <button type="button" class="btn btn-primary btn-detail-save">
                        <i class="ti ti-device-floppy me-1"></i> 저장
                    </button>
                    <button type="button" class="btn btn-dark btn-detail-download">
                        <i class="ti ti-download me-1"></i> 이미지 다운로드
                    </button>
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
                    <div id="detailPreview" style="width: 760px; max-width: 100%; background:#ffffff; padding:12px; border-radius:8px;"></div>
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

        // 현재 에디터에 올라와 있는 유형 (초기값: 와인)
        var activeType = 'wine';

        function currentType(){
            return $('input[name="sampleType"]:checked').val() === 'solo' ? 'solo' : 'wine';
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

        // 유형 전환: 현재 작업 내용을 활성 유형에 보관하고, 대상 유형 저장본을 에디터에 로드
        $('input[name="sampleType"]').on('change', function(){
            var next = currentType();
            if (next === activeType) { return; }
            SAVED[activeType] = getHtml();   // 현재 편집 내용 보관
            setHtml(SAVED[next] || '');      // 대상 유형 내용 로드
            activeType = next;
        });

        // 1열 샘플 (선택한 유형 기준)
        $('.btn-sample-one').on('click', async function(){
            var type = currentType();
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            if(await salert({text: '현재 작성 내용을 [' + label + '] 1열 샘플로 교체하시겠습니까?'})){
                setHtml(SAMPLES[type][1]);
            }
        });

        // 2열 샘플 (선택한 유형 기준)
        $('.btn-sample-two').on('click', async function(){
            var type = currentType();
            var label = type === 'solo' ? '1:1 소개팅' : '와인파티';
            if(await salert({text: '현재 작성 내용을 [' + label + '] 2열 샘플로 교체하시겠습니까?'})){
                setHtml(SAMPLES[type][2]);
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

            // 화면 밖(보이지만 뷰포트 밖)에 캡처 전용 컨테이너를 만든다.
            // display:none 이면 html2canvas 가 크기를 못 재므로, 화면 밖으로 밀어낸다.
            var holder = document.getElementById('detailCaptureHolder');
            if (!holder) {
                holder = document.createElement('div');
                holder.id = 'detailCaptureHolder';
                holder.style.position = 'fixed';
                holder.style.left = '-10000px';
                holder.style.top = '0';
                holder.style.width = '760px';
                holder.style.background = '#ffffff';
                holder.style.padding = '12px';
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

                var canvas = await html2canvas(holder, {
                    backgroundColor: '#ffffff',
                    scale: 2,
                    useCORS: true,
                    allowTaint: false,
                    logging: false
                });

                canvas.toBlob(function(blob){
                    if (!blob) {
                        salert({text: '이미지 생성에 실패했습니다. 상단 이미지의 접근 권한(CORS)을 확인해주세요.', cancel: false});
                        return;
                    }
                    var url = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.download = 'rotable_상세페이지.png';
                    link.href = url;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                }, 'image/png');
            } catch (e) {
                await salert({text: '이미지 생성 중 오류가 발생했습니다: ' + (e?.message || e), cancel: false});
            }
        });
    });
</script>
@endsection
