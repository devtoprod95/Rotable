<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME') }} ERD</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --primary-color: #2563eb;
            --active-bg: #dbeafe;
            --active-text: #1e40af;
            --bg-gray: #f1f5f9;
            --border-color: #e2e8f0;
            --loading-bar-color: #3b82f6;
        }

        body { font-family: 'Pretendard', -apple-system, sans-serif; background-color: var(--bg-gray); padding: 20px; color: #334155; }

        /* 헤더 및 타이틀 */
        .erd-header { text-align: center; margin-bottom: 25px; }
        .erd-header h1 { font-size: 1.8rem; font-weight: 800; color: #1e293b; margin-bottom: 10px; }

        /* 메뉴 UI */
        .erd-nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-bottom: 20px; padding: 0; list-style: none; }
        .erd-nav li a {
            text-decoration: none; color: #64748b; background: #fff; padding: 8px 14px;
            border-radius: 8px; border: 1px solid var(--border-color); font-size: 13.5px;
            transition: all 0.2s ease;
        }
        .erd-nav li a.active { background: var(--active-bg); color: var(--active-text); border-color: var(--active-text); font-weight: 700; }

        /* 컨트롤바 */
        .controls-wrapper {
            display: flex; justify-content: space-between; align-items: center;
            background: #fff; padding: 12px 20px; border-radius: 12px 12px 0 0;
            border: 1px solid var(--border-color); border-bottom: none;
        }
        .size-controls { display: flex; align-items: center; gap: 6px; }
        .size-controls button {
            padding: 6px 14px; border: 1px solid var(--border-color); background: #fff;
            border-radius: 6px; cursor: pointer; font-size: 12px; transition: 0.2s;
        }
        .size-controls button.active { background-color: var(--primary-color); color: white; border-color: var(--primary-color); }

        /* 다이어그램 컨테이너 부모 (로딩바 포함) */
        .mermaid-container-outer {
            position: relative; width: 100%; height: 75vh;
            background: white; border: 1px solid var(--border-color);
            border-radius: 0 0 12px 12px; overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* 컨테이너 내부 로딩바 */
        #loading-zone {
            position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: #f1f5f9; z-index: 10;
        }
        #loading-bar {
            width: 0%; height: 100%; background: var(--loading-bar-color);
            box-shadow: 0 1px 5px rgba(59, 130, 246, 0.5);
            transition: width 0.3s ease;
        }

        /* 다이어그램 본체 및 최적화 */
        #mermaid-wrapper {
            width: 100%; height: 100%;
            opacity: 0; transform: scale(0.98);
            transition: all 0.5s ease-out;
        }
        #mermaid-wrapper.loaded { opacity: 1; transform: scale(1); }

        #mermaid-wrapper svg {
            will-change: transform; /* 하드웨어 가속 */
            backface-visibility: hidden;
        }

        pre.mermaid { margin: 0; height: 100%; display: flex; justify-content: center; align-items: center; }

        #tabContent,
        .tab-pane,
        pre.mermaid {
            height: 100%;
            margin: 0;
        }

        /* 탭 전환 시 부드럽게 넘어가도록 설정 */
        .tab-content > .tab-pane {
            display: block !important;
            visibility: hidden;
            height: 0;
            overflow: hidden;
        }
        .tab-content > .active {
            visibility: visible;
            height: 100%;
        }

        /* 1. 컨트롤러 위치 이동 (우측 하단 -> 좌측 상단) */
        #svg-pan-zoom-controls {
            /* 탭 이동 시에도 좌측 상단에 고정되도록 설정 */
            transform: translate(15px, 15px) scale(0.8);
        }

        /* 2. 컨트롤러 배경 스타일 (더 잘 보이게) */
        .svg-pan-zoom-control-background {
            fill: #ffffff !important;
            fill-opacity: 0.9 !important;
            stroke: var(--border-color) !important;
            stroke-width: 1px;
        }

        /* 3. 아이콘 심볼 색상 */
        .svg-pan-zoom-control-element {
            fill: #64748b !important;
            transition: fill 0.2s;
        }

        /* 4. 호버 시 색상 변경 */
        .svg-pan-zoom-control-element:hover {
            fill: var(--primary-color) !important;
        }

        /* 5. 컨테이너 밖으로 아이콘이 나가지 않도록 보정 */
        .mermaid-container-outer {
            /* overflow: hidden 때문에 아이콘이 잘린다면 이 옵션을 체크하세요 */
            overflow: hidden;
        }
    </style>
</head>
<body>

    <div class="erd-header">
        <h1>{{ env('APP_NAME') }} ERD <small style="font-weight: 400; color: #94a3b8;">({{ $title ?? "전체" }})</small></h1>
        <ul class="erd-nav" id="main-nav">
            <li><a href="{{ route('erd.adRequest') }}">게재신청서</a></li>
            <li><a href="{{ route('erd.inventory') }}">지면</a></li>
            <li><a href="{{ route('erd.campaign') }}">캠페인</a></li>
            <li><a href="{{ route('erd.sojae') }}">소재</a></li>
            <li><a href="{{ route('erd.admin') }}">관리자</a></li>
        </ul>
    </div>

    <div class="controls-wrapper">
        <div class="size-controls" id="zoom-controls">
            <span style="font-size:13px; font-weight:600; margin-right:5px;">확대 배율:</span>
            <button onclick="zoomManual(1, this)" class="active">100%</button>
            <button onclick="zoomManual(2, this)">200%</button>
            <button onclick="zoomManual(3, this)">300%</button>
            <button onclick="zoomManual(4, this)">400%</button>
            <button onclick="zoomManual(5, this)">500%</button>
        </div>
        <div style="font-size: 12px; color: #94a3b8;">Ctrl+휠로 확대/축소, 드래그로 이동</div>
    </div>

    <div class="mermaid-container-outer">
        <div id="loading-zone"><div id="loading-bar"></div></div>
        <div id="mermaid-wrapper">
            @yield("content")
        </div>
    </div>

    <script>
        // ===== 1. 전역 변수 최소화 및 캐싱 =====
        const AppState = {
            panZoom: null,
            cachedElements: {
                loadingBar: null,
                loadingZone: null,
                wrapper: null,
                zoomButtons: []
            },
            flags: {
                isInitializing: false,
                isResizing: false
            }
        };

        // ===== 2. DOM 요소 캐싱 (한 번만 쿼리) =====
        function cacheElements() {
            AppState.cachedElements = {
                loadingBar: document.getElementById('loading-bar'),
                loadingZone: document.getElementById('loading-zone'),
                wrapper: document.getElementById('mermaid-wrapper'),
                zoomButtons: document.querySelectorAll('#zoom-controls button')
            };
        }

        // ===== 로딩바 업데이트 함수 추가 =====
        function updateLoadingBar(percent) {
            const { loadingBar } = AppState.cachedElements;
            if (loadingBar) {
                requestAnimationFrame(() => {
                    loadingBar.style.width = `${percent}%`;
                });
            }
        }

        // ===== 3. 메뉴 활성화 =====
        function setActiveMenu() {
            const currentPath = window.location.pathname.replace(/\/+$/, '');
            let matched = false;
            document.querySelectorAll('#main-nav a').forEach(link => {
                if (new URL(link.href).pathname.replace(/\/+$/, '') === currentPath) {
                    link.classList.add('active');
                    matched = true;
                }
            });
            if (!matched && document.querySelectorAll('#main-nav a').length > 0) {
                document.querySelectorAll('#main-nav a')[0].classList.add('active');
            }
        }

        // ===== 4. Mermaid 초기 설정 =====
        mermaid.initialize({
            startOnLoad: true,
            theme: 'default',
            flowchart: { useMaxWidth: false, htmlLabels: true },
            securityLevel: 'loose',
            er: { useMaxWidth: false }
        });

        // ===== 커스텀 휠 이벤트 핸들러 (Ctrl+휠만 줌, 일반 휠은 팬) =====
        function customWheelHandler(e) {
            if (!AppState.panZoom) return;

            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                e.stopPropagation();

                const delta = e.deltaY || e.detail || e.wheelDelta;
                const currentZoom = AppState.panZoom.getZoom();
                const scale = delta > 0 ? 0.9 : 1.1;
                const newZoom = currentZoom * scale;

                const point = {
                    x: e.clientX,
                    y: e.clientY
                };

                AppState.panZoom.zoomAtPoint(newZoom, point);
                updateZoomButtons(newZoom);
            } else {
                e.preventDefault();
                e.stopPropagation();

                const pan = AppState.panZoom.getPan();
                const deltaX = e.deltaX || 0;
                const deltaY = e.deltaY || 0;
                const panSpeed = 0.3;

                AppState.panZoom.pan({
                    x: pan.x - (deltaX * panSpeed),
                    y: pan.y - (deltaY * panSpeed)
                });
            }
        }

        // ===== 5. PanZoom 초기화 =====
        function initPanZoom(targetSvg) {
            if (AppState.flags.isInitializing) return;
            AppState.flags.isInitializing = true;

            updateLoadingBar(70);

            if (AppState.panZoom) {
                AppState.panZoom.destroy();
                AppState.panZoom = null;
            }

            const svgElement = targetSvg ||
                document.querySelector('.tab-pane.active svg') ||
                document.querySelector('#mermaid-wrapper svg');

            if (!svgElement) {
                AppState.flags.isInitializing = false;
                return;
            }

            svgElement.style.cssText = `
                width: 100% !important;
                height: 100% !important;
                max-width: none;
                will-change: transform;
                transform: translate3d(0,0,0);
            `;

            updateLoadingBar(85);

            requestAnimationFrame(() => {
                AppState.panZoom = svgPanZoom(svgElement, {
                    zoomEnabled: true,
                    controlIconsEnabled: true,
                    fit: true,
                    center: true,
                    minZoom: 0.01,
                    maxZoom: 50,
                    zoomScaleSensitivity: 0.3,
                    mouseWheelZoomEnabled: false,
                    onZoom: throttle((newZoom) => {
                        updateZoomButtons(newZoom);
                    }, 16)
                });

                const textElements = svgElement.querySelectorAll('text');
                textElements.forEach(text => {
                    text.addEventListener('mousedown', function(e) { e.stopPropagation(); }, true);
                    text.addEventListener('dblclick', function(e) { e.stopPropagation(); }, true);
                    text.addEventListener('click', function(e) { e.stopPropagation(); }, true);
                });

                const container = svgElement.parentElement;
                if (container) {
                    container.addEventListener('wheel', customWheelHandler, { passive: false });
                }

                updateLoadingBar(95);
                finishLoading();
                AppState.flags.isInitializing = false;
            });
        }

        // ===== 6. 로딩 완료 처리 =====
        function finishLoading() {
            const { loadingZone, wrapper } = AppState.cachedElements;
            updateLoadingBar(100);

            setTimeout(() => {
                requestAnimationFrame(() => {
                    if (wrapper) wrapper.classList.add('loaded');
                    if (loadingZone) {
                        loadingZone.style.opacity = '0';
                        setTimeout(() => {
                            loadingZone.style.display = 'none';
                        }, 500);
                    }
                });
            }, 200);
        }

        // ===== 7. Throttle 유틸 =====
        function throttle(func, delay) {
            let lastCall = 0;
            return function(...args) {
                const now = Date.now();
                if (now - lastCall >= delay) {
                    lastCall = now;
                    func.apply(this, args);
                }
            };
        }

        // ===== 8. 줌 버튼 업데이트 =====
        function updateZoomButtons(currentZoom) {
            const { zoomButtons } = AppState.cachedElements;
            if (!zoomButtons.length) return;

            requestAnimationFrame(() => {
                zoomButtons.forEach(btn => {
                    const targetScale = parseFloat(btn.textContent.replace('%', '')) / 100;
                    const isActive = Math.abs(currentZoom - targetScale) < 0.08;
                    btn.classList.toggle('active', isActive);
                });
            });
        }

        // ===== 9. 수동 줌 =====
        function zoomManual(level, btn) {
            if (!AppState.panZoom) return;

            const { zoomButtons } = AppState.cachedElements;
            requestAnimationFrame(() => {
                zoomButtons.forEach(b => b.classList.remove('active'));
                if (btn) btn.classList.add('active');
            });

            AppState.panZoom.zoom(level);
            AppState.panZoom.center();
        }

        // ===== 10. SVG 감지 (MutationObserver 사용) =====
        function waitForSVG() {
            return new Promise((resolve) => {
                const targetNode = AppState.cachedElements.wrapper || document.body;

                const observer = new MutationObserver((mutations, obs) => {
                    const svg = document.querySelector('#mermaid-wrapper svg');
                    if (svg) {
                        obs.disconnect();
                        updateLoadingBar(60);
                        resolve(svg);
                    }
                });

                observer.observe(targetNode, {
                    childList: true,
                    subtree: true
                });

                const existingSvg = document.querySelector('#mermaid-wrapper svg');
                if (existingSvg) {
                    observer.disconnect();
                    updateLoadingBar(60);
                    resolve(existingSvg);
                }

                setTimeout(() => {
                    observer.disconnect();
                    resolve(null);
                }, 5000);
            });
        }

        // ===== 11. 페이지 로드 =====
        window.addEventListener('load', async () => {
            updateLoadingBar(10);
            cacheElements();
            updateLoadingBar(30);
            setActiveMenu();
            updateLoadingBar(40);

            const svg = await waitForSVG();
            if (svg) {
                initPanZoom(svg);
            }
        }, { once: true, passive: true });

        // ===== 12. Resize 핸들러 =====
        const handleResize = throttle(() => {
            if (AppState.flags.isResizing) return;
            AppState.flags.isResizing = true;

            requestAnimationFrame(() => {
                if (AppState.panZoom) {
                    AppState.panZoom.resize();
                    AppState.panZoom.center();
                }
                AppState.flags.isResizing = false;
            });
        }, 150);

        window.addEventListener('resize', handleResize, { passive: true });

        // ===== 13. 메모리 정리 =====
        window.addEventListener('beforeunload', () => {
            if (AppState.panZoom) {
                AppState.panZoom.destroy();
                AppState.panZoom = null;
            }
        }, { once: true });
    </script>

</body>
</html>
