{{--
    재사용 "입장등록 QR" 모달. 행사별 입장등록 화면(/register/{행사id})으로 바로 이동하는 QR 을 보여준다.
    - 사용법(JS): RegisterQrModal.open(eventId, eventTitle)
    - QR 생성은 레이아웃(app.blade.php)의 전역 QRCode(qrcodejs)를 쓴다.
--}}
@php
    $registerUrlTemplate = route('register', ['eventId' => '__ID__']);
@endphp

<div class="modal fade" id="registerQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-qrcode me-1"></i> 입장등록 QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="fw-bold mb-2" id="registerQrTitle"></div>
                <div class="d-flex justify-content-center">
                    <div id="registerQrCanvas" style="padding:12px; background:#fff; display:inline-block;"></div>
                </div>
                <a href="#" target="_blank" rel="noopener" class="d-block text-muted mt-2" style="font-size:0.85em; word-break:break-all;" id="registerQrUrl"></a>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark" id="btnRegisterQrCopy">
                    <i class="ti ti-copy me-1"></i> 주소 복사
                </button>
                <button type="button" class="btn btn-dark" id="btnRegisterQrDownload">
                    <i class="ti ti-download me-1"></i> 다운로드
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var RegisterQrModal = (function () {
        var urlTemplate = @json($registerUrlTemplate);
        var modal = null;
        var currentName = '';

        function getModal() {
            if (modal) { return modal; }
            var ModalClass =
                (window.tabler && window.tabler.Modal) ||
                (window.tabler && window.tabler.bootstrap && window.tabler.bootstrap.Modal) ||
                (window.bootstrap && window.bootstrap.Modal) ||
                (window.coreui && window.coreui.Modal) ||
                window.Modal;
            if (!ModalClass) { console.error('Modal 클래스를 찾을 수 없습니다.'); return null; }
            modal = new ModalClass(document.getElementById('registerQrModal'));
            return modal;
        }

        function open(eventId, title) {
            var url = urlTemplate.replace('__ID__', encodeURIComponent(eventId));
            currentName = title || ('event_' + eventId);

            document.getElementById('registerQrTitle').textContent = title || '';
            var link = document.getElementById('registerQrUrl');
            link.textContent = url;
            link.href = url;

            var holder = document.getElementById('registerQrCanvas');
            holder.innerHTML = '';
            if (typeof QRCode === 'undefined') {
                alert('QR 생성 모듈을 불러오지 못했습니다. 새로고침 후 다시 시도해주세요.');
                return;
            }
            new QRCode(holder, { text: url, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M });

            var m = getModal();
            if (m) { m.show(); }
        }

        // 긴 텍스트를 폭(maxW)에 맞게 문자 단위로 줄바꿈한다. (URL 대응)
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

        function fitText(ctx, text, maxW) {
            if (ctx.measureText(text).width <= maxW) { return text; }
            var t = text;
            while (t.length > 1 && ctx.measureText(t + '…').width > maxW) { t = t.slice(0, -1); }
            return t + '…';
        }

        // 제목 + QR + 주소를 캔버스에 직접 그려 한 장의 이미지로 저장한다.
        async function download() {
            var holder = document.getElementById('registerQrCanvas');
            var src = holder.querySelector('img') || holder.querySelector('canvas');
            if (!src) { alert('QR 이미지를 찾을 수 없습니다.'); return; }
            if (src.tagName === 'IMG' && !(src.complete && src.naturalWidth)) {
                await new Promise(function (resolve) { src.onload = resolve; src.onerror = resolve; });
            }

            var title = (document.getElementById('registerQrTitle').textContent || '').trim();
            var url   = (document.getElementById('registerQrUrl').textContent || '').trim();

            var scale = 2, W = 360, pad = 24, qrSize = 240;
            var titleH = title ? 40 : 0, gapTop = title ? 16 : 0, gapUrl = 16, urlLineH = 20;

            var measure = document.createElement('canvas').getContext('2d');
            measure.font = '500 15px sans-serif';
            var urlLines = wrapText(measure, url, W - pad * 2);
            var H = pad + titleH + gapTop + qrSize + gapUrl + urlLines.length * urlLineH + pad;

            var canvas = document.createElement('canvas');
            canvas.width = W * scale;
            canvas.height = H * scale;
            var ctx = canvas.getContext('2d');
            ctx.scale(scale, scale);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, W, H);

            var y = pad;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            if (title) {
                ctx.fillStyle = '#111827';
                ctx.font = "700 20px 'Gothic A1','Apple SD Gothic Neo',sans-serif";
                ctx.fillText(fitText(ctx, title, W - pad * 2), W / 2, y + 6);
                y += titleH + gapTop;
            }
            ctx.drawImage(src, (W - qrSize) / 2, y, qrSize, qrSize);
            y += qrSize + gapUrl;

            ctx.fillStyle = '#6b7280';
            ctx.font = '500 15px sans-serif';
            urlLines.forEach(function (line, i) { ctx.fillText(line, W / 2, y + i * urlLineH); });

            var a = document.createElement('a');
            a.download = 'register_qr_' + currentName + '.png';
            a.href = canvas.toDataURL('image/png');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('btnRegisterQrDownload').addEventListener('click', download);
            document.getElementById('btnRegisterQrCopy').addEventListener('click', function () {
                var url = document.getElementById('registerQrUrl').textContent;
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(function () { alert('주소를 복사했습니다.'); });
                } else {
                    window.prompt('주소를 복사하세요.', url);
                }
            });
        });

        return { open: open };
    })();
</script>
