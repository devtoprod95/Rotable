@extends('layouts.app')

@section('title', 'QR코드 설정')

@section('content')

<style>
    /* QR 목록: 모바일에서는 가로 스크롤 대신 '카드형'으로 세로 재배치 */
    @media (max-width: 767.98px){
        .qr-table thead{ display:none; }
        .qr-table, .qr-table tbody, .qr-table tr, .qr-table td{ display:block; width:100%; }
        .qr-table tr{
            border:1px solid #e6e8eb; border-radius:12px;
            padding:10px 12px; margin-bottom:12px; background:#fff;
            box-shadow:0 1px 3px rgba(0,0,0,.05); position:relative;
        }
        .qr-table td{
            border:0 !important; padding:4px 0 !important; text-align:left !important;
            display:flex; gap:10px; align-items:flex-start;
        }
        .qr-table td::before{
            content: attr(data-label);
            flex:0 0 72px; color:#8a92a0; font-weight:700; font-size:0.8rem;
        }
        /* 체크박스 셀: 우상단 모서리에 배치 */
        .qr-table td.qr-check{ position:absolute; top:10px; right:10px; padding:0 !important; width:auto; }
        .qr-table td.qr-check::before{ display:none; }
        /* 이름/주소 셀은 라벨 없이 넓게(카드 제목처럼) */
        .qr-table td.qr-name{ display:block; padding-right:28px !important; border-bottom:1px solid #f1f3f5 !important; padding-bottom:8px !important; margin-bottom:4px; }
        .qr-table td.qr-name::before{ display:none; }
        .qr-table td.qr-target{ max-width:none !important; }
        .qr-table td.qr-target .text-truncate{ white-space:normal; word-break:break-all; }
    }
</style>

@php
    // 공개 리다이렉트 주소의 베이스. JS 에서 code 를 붙여 전체 주소를 만든다. (예: https://site/qr/abcd1234)
    $qrBase = url('/qr');
    // 현재 사이트 도메인. 연결 대상 URL 의 prefix 로 사용한다. (예: https://site)
    $siteDomain = rtrim(url('/'), '/');
@endphp

<div class="row row-cards mt-2">
    {{-- 생성/수정 폼 --}}
    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-qrcode me-1"></i> <span id="formTitle">QR코드 연결 추가</span></h3>
            </div>
            <div class="card-body">
                <small class="form-hint text-muted d-block mb-3">
                    <b>연결 대상 URL</b>을 입력하고 저장하면, 우리 사이트의 공개 주소
                    <code>{{ $qrBase }}/코드</code> 가 발급됩니다.
                    이 공개 주소를 <b>QR 코드</b>로 만들어 배포하세요. 사용자가 QR 을 찍으면 설정한 대상 URL 로 이동합니다.
                    <br>대상 URL 만 바꾸면 <mark>QR 은 그대로 두고</mark> 연결 페이지를 바꿀 수 있습니다.
                </small>

                <input type="hidden" id="qrId" value="">

                <div class="mb-3">
                    <label class="form-label required">관리용 이름</label>
                    <input type="text" class="form-control" id="qrTitle" placeholder="예: 1월 와인파티 신청 링크">
                </div>

                <div class="mb-3">
                    <label class="form-label required">연결 대상 URL</label>
                    <input type="text" class="form-control" id="qrTarget"
                           value="{{ rtrim($siteDomain, '/') }}/" placeholder="https://example.com/apply">
                    <small class="form-hint">
                        기본값으로 현재 사이트 도메인이 채워져 있습니다. 뒤에 경로를 붙이거나,
                        전체를 지우고 <b>외부 URL</b>을 넣어도 됩니다. (<code>https://</code> 를 생략하면 자동으로 붙습니다)
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="qrActive" checked>
                        <span class="form-check-label">활성화 (비활성 시 접근하면 404)</span>
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" id="btnSave">
                        <i class="ti ti-device-floppy me-1"></i> 저장
                    </button>
                    <button type="button" class="btn btn-outline-secondary d-none" id="btnReset">
                        <i class="ti ti-plus me-1"></i> 새로 추가
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 목록 --}}
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0"><i class="ti ti-list me-1"></i> QR코드 목록</h3>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeleteSelected">
                    <i class="ti ti-trash me-1"></i> 선택 삭제
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table qr-table">
                    <thead>
                        <tr>
                            <th style="width:32px;"><input type="checkbox" id="checkAll" class="form-check-input m-0"></th>
                            <th>이름 / 공개 주소</th>
                            <th>대상 URL</th>
                            <th class="text-center" style="width:70px;">상태</th>
                            <th class="text-center" style="width:70px;">이동수</th>
                            <th class="text-center" style="width:150px;">QR / 관리</th>
                        </tr>
                    </thead>
                    <tbody id="qrTbody">
                        @forelse($links as $link)
                            <tr data-id="{{ $link->id }}"
                                data-title="{{ $link->title }}"
                                data-target="{{ $link->target_url }}"
                                data-active="{{ $link->is_active ? 1 : 0 }}"
                                data-code="{{ $link->code }}"
                                data-url="{{ $link->public_url }}">
                                <td class="qr-check"><input type="checkbox" class="form-check-input m-0 rowCheck" value="{{ $link->id }}"></td>
                                <td class="qr-name">
                                    <div class="fw-bold">{{ $link->title ?: '(이름 없음)' }}</div>
                                    <div class="text-muted" style="font-size:0.85em;">
                                        <a href="{{ $link->public_url }}" target="_blank" rel="noopener">{{ $link->public_url }}</a>
                                    </div>
                                </td>
                                <td class="qr-target" data-label="대상 URL" style="max-width:220px;">
                                    <div class="text-truncate" title="{{ $link->target_url }}">{{ $link->target_url }}</div>
                                </td>
                                <td class="text-center" data-label="상태">
                                    @if($link->is_active)
                                        <span class="badge bg-green-lt">활성</span>
                                    @else
                                        <span class="badge bg-secondary-lt">비활성</span>
                                    @endif
                                </td>
                                <td class="text-center" data-label="이동수">{{ number_format($link->hit_count) }}</td>
                                <td class="text-center" data-label="QR / 관리">
                                    <button type="button" class="btn btn-sm btn-outline-dark btnQr"
                                            data-code="{{ $link->code }}" data-url="{{ $link->public_url }}"
                                            data-title="{{ $link->title }}">
                                        <i class="ti ti-qrcode"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary btnEdit">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow"><td colspan="6" class="text-center text-muted py-4">등록된 QR코드가 없습니다.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- QR 미리보기 / 다운로드 모달 --}}
<div class="modal modal-blur fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-qrcode me-1"></i> QR 코드</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="qrCaptureArea">
                <div class="fw-bold mb-2" id="qrModalTitle"></div>
                <div class="d-flex justify-content-center">
                    <div id="qrCanvas" style="padding:12px; background:#fff; display:inline-block;"></div>
                </div>
                <div class="text-muted mt-2" style="font-size:0.85em; word-break:break-all;" id="qrModalUrl"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-dark" id="btnQrDownload">
                    <i class="ti ti-download me-1"></i> 다운로드
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        var storeUrl  = "{{ route('admin.rotation.qr.store') }}";
        var deleteUrl = "{{ route('admin.rotation.qr.delete') }}";
        var qrBase    = "{{ $qrBase }}";
        var siteDomain = "{{ $siteDomain }}";  // 신규 추가 시 입력란 기본값으로 사용

        var qrModal = null;
        var qrInstance = null;
        var currentQrName = '';

        function getModal() {
            if (qrModal) { return qrModal; }
            var modalEl = document.getElementById('qrModal');
            var ModalClass =
                (window.tabler && window.tabler.Modal) ||
                (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                (window.bootstrap && window.bootstrap.Modal) ||
                (window.coreui && window.coreui.Modal) ||
                window.Modal;
            if (!ModalClass) { console.error('Modal 클래스를 찾을 수 없습니다.'); return null; }
            qrModal = new ModalClass(modalEl);
            return qrModal;
        }

        function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

        // 폼 초기화 (신규 추가 모드)
        function resetForm() {
            $('#qrId').val('');
            $('#qrTitle').val('');
            $('#qrTarget').val(siteDomain + '/');
            $('#qrActive').prop('checked', true);
            $('#formTitle').text('QR코드 연결 추가');
            $('#btnReset').addClass('d-none');
        }

        // 저장
        $('#btnSave').on('click', function () {
            var title = ($('#qrTitle').val() || '').trim();
            if (!title) {
                salert({ text: '관리용 이름을 입력해주세요.', cancel: false });
                return;
            }

            var target = ($('#qrTarget').val() || '').trim();
            if (!target) {
                salert({ text: '연결 대상 URL 을 입력해주세요.', cancel: false });
                return;
            }

            $.ajax({
                url: storeUrl,
                type: 'POST',
                data: {
                    id: $('#qrId').val(),
                    title: title,
                    target_url: target,
                    is_active: $('#qrActive').is(':checked') ? 1 : 0
                },
                success: function (res) {
                    salert({ text: res?.msg || '저장되었습니다.', cancel: false }).then(function () {
                        location.reload();
                    });
                },
                error: function (xhr) {
                    salert({ text: xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.', cancel: false });
                }
            });
        });

        $('#btnReset').on('click', resetForm);

        // 목록 → 수정 모드로 폼 채우기
        $(document).on('click', '.btnEdit', function () {
            var $tr = $(this).closest('tr');
            $('#qrId').val($tr.data('id'));
            $('#qrTitle').val($tr.data('title'));
            $('#qrTarget').val(String($tr.data('target')));
            $('#qrActive').prop('checked', String($tr.data('active')) === '1');
            $('#formTitle').text('QR코드 연결 수정');
            $('#btnReset').removeClass('d-none');
            $('html, body').animate({ scrollTop: 0 }, 200);
        });

        // 전체 선택
        $('#checkAll').on('change', function () {
            $('.rowCheck').prop('checked', $(this).is(':checked'));
        });

        // 선택 삭제
        $('#btnDeleteSelected').on('click', async function () {
            var ids = $('.rowCheck:checked').map(function () { return $(this).val(); }).get();
            if (!ids.length) {
                salert({ text: '삭제할 항목을 선택해주세요.', cancel: false });
                return;
            }
            if (!(await salert({ text: '선택한 ' + ids.length + '개를 삭제하시겠습니까?' }))) { return; }

            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: { ids: ids },
                success: function (res) {
                    salert({ text: res?.msg || '삭제되었습니다.', cancel: false }).then(function () {
                        location.reload();
                    });
                },
                error: function (xhr) {
                    salert({ text: xhr?.responseJSON?.error?.message ?? '오류가 발생했습니다.', cancel: false });
                }
            });
        });

        // QR 코드 보기
        $(document).on('click', '.btnQr', function () {
            var url   = $(this).data('url');
            var title = $(this).data('title') || '';
            var code  = $(this).data('code');

            currentQrName = title || code;

            $('#qrModalTitle').text(title || '(이름 없음)');
            $('#qrModalUrl').text(url);

            // 이전 QR 비우고 새로 그린다.
            var holder = document.getElementById('qrCanvas');
            holder.innerHTML = '';
            if (typeof QRCode === 'undefined') {
                salert({ text: 'QR 생성 모듈을 불러오지 못했습니다. 새로고침 후 다시 시도해주세요.', cancel: false });
                return;
            }
            qrInstance = new QRCode(holder, {
                text: url,
                width: 220,
                height: 220,
                correctLevel: QRCode.CorrectLevel.M
            });

            var m = getModal();
            if (m) { m.show(); }
        });

        // 다운로드: 제목 + QR + 주소를 캔버스에 직접 그려 한 장의 이미지로 저장한다.
        // (html2canvas 는 Tabler/부트스트랩의 최신 color() 색상 표기를 파싱 못 해 오류가 나므로 사용하지 않는다.)
        $('#btnQrDownload').on('click', async function () {
            var holder = document.getElementById('qrCanvas');
            var qrSrcEl = holder.querySelector('img') || holder.querySelector('canvas');
            if (!qrSrcEl) {
                salert({ text: 'QR 이미지를 찾을 수 없습니다.', cancel: false });
                return;
            }

            // QR 이 img 라면 로드 완료를 기다린다.
            if (qrSrcEl.tagName === 'IMG' && !(qrSrcEl.complete && qrSrcEl.naturalWidth)) {
                await new Promise(function (resolve) {
                    qrSrcEl.onload = resolve;
                    qrSrcEl.onerror = resolve;
                });
            }

            var title = ($('#qrModalTitle').text() || '').trim();
            var url   = ($('#qrModalUrl').text() || '').trim();

            // ── 레이아웃 치수 ──
            var scale   = 2;                 // 선명도
            var W       = 360;               // 논리 폭
            var pad     = 24;
            var qrSize  = 240;
            var titleH  = title ? 40 : 0;
            var gapTop  = title ? 16 : 0;
            var gapUrl  = 16;

            var ctx0 = document.createElement('canvas').getContext('2d');
            // 주소 줄바꿈 계산용 폰트
            ctx0.font = '500 15px sans-serif';
            var urlLines = wrapText(ctx0, url, W - pad * 2);
            var urlLineH = 20;
            var urlBlockH = urlLines.length * urlLineH;

            var H = pad + titleH + gapTop + qrSize + gapUrl + urlBlockH + pad;

            var canvas = document.createElement('canvas');
            canvas.width  = W * scale;
            canvas.height = H * scale;
            var ctx = canvas.getContext('2d');
            ctx.scale(scale, scale);

            // 배경
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, W, H);

            var y = pad;

            // 제목
            if (title) {
                ctx.fillStyle = '#111827';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                ctx.font = "700 20px 'Gothic A1','Apple SD Gothic Neo',sans-serif";
                ctx.fillText(fitText(ctx, title, W - pad * 2), W / 2, y + 6);
                y += titleH + gapTop;
            }

            // QR
            ctx.drawImage(qrSrcEl, (W - qrSize) / 2, y, qrSize, qrSize);
            y += qrSize + gapUrl;

            // 주소 (여러 줄)
            ctx.fillStyle = '#6b7280';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            ctx.font = '500 15px sans-serif';
            urlLines.forEach(function (line, i) {
                ctx.fillText(line, W / 2, y + i * urlLineH);
            });

            var dataUrl = canvas.toDataURL('image/png');
            var link = document.createElement('a');
            link.download = 'qr_' + (currentQrName || 'code') + '.png';
            link.href = dataUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // 긴 텍스트를 폭(maxW)에 맞게 여러 줄로 쪼갠다. (문자 단위 — URL 대응)
        function wrapText(ctx, text, maxW) {
            var lines = [], cur = '';
            for (var i = 0; i < text.length; i++) {
                var test = cur + text[i];
                if (ctx.measureText(test).width > maxW && cur) {
                    lines.push(cur);
                    cur = text[i];
                } else {
                    cur = test;
                }
            }
            if (cur) { lines.push(cur); }
            return lines.length ? lines : [''];
        }

        // 한 줄 텍스트가 maxW 를 넘으면 … 로 줄인다.
        function fitText(ctx, text, maxW) {
            if (ctx.measureText(text).width <= maxW) { return text; }
            var t = text;
            while (t.length > 1 && ctx.measureText(t + '…').width > maxW) {
                t = t.slice(0, -1);
            }
            return t + '…';
        }
    });
</script>
@endsection
