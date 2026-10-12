@extends('layouts.app')

@section('title', 'K6 부하 테스트')

@section('content')
<div class="row row-cards justify-content-center mt-2">
    <!-- 도커 환경 경고 얼럿 -->
    @if($isDocker)
        <div class="col-lg-12">
            <div class="alert alert-warning border-0 shadow-sm d-flex gap-3 align-items-center p-3 mb-3" role="alert">
                <span class="avatar bg-warning-lt">
                    <i class="ti ti-alert-triangle fs-2"></i>
                </span>
                <div>
                    <h4 class="alert-title fw-bold mb-1">도커 컨테이너 환경 감지됨</h4>
                    <div class="text-muted fs-5">
                        k6 엔진이 PHP 컨테이너(baseplate-php) 내부에서 실행되므로 <code>localhost</code> 또는 <code>127.0.0.1</code>은 정상 작동하지 않습니다.<br>
                        컨테이너 간 네트워크 통신을 위해 타겟 URL에 <strong><code>http://nginx:80/api</code></strong>를 사용하셔야 합니다.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 1. k6 바이너리 설치 상태 카드 -->
    <div class="col-lg-12">
        <div class="card mb-3">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar bg-primary-lt">
                        <i class="ti ti-activity fs-2"></i>
                    </span>
                    <div>
                        <h3 class="lh-1 mb-1 fw-bold">K6 엔진 설치 상태</h3>
                        <div class="text-muted fs-5">부하 테스트 실행을 위해서는 서버에 k6 엔진이 필요합니다.</div>
                    </div>
                </div>
                <div>
                    @if($isInstalled)
                        <span class="badge bg-success-lt fs-4 px-3 py-2 border border-success">
                            <i class="ti ti-circle-check me-1"></i> 설치됨 (준비 완료)
                        </span>
                    @else
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger-lt fs-4 px-3 py-2 border border-danger">
                                <i class="ti ti-circle-x me-1"></i> 미설치
                            </span>
                            <button type="button" id="btn-install-k6" class="btn btn-primary btn-sm">
                                <i class="ti ti-download me-1"></i> K6 자동 설치
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 설정기 및 스크립트 작성 영역 -->
    <div class="col-lg-6">
        <div class="card" style="min-height: 650px;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-adjustments me-2 text-primary"></i>부하 테스트 옵션 구성
                </h3>
            </div>
            <div class="card-body">
                <!-- HTTP 기본 정보 -->
                <div class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label required">HTTP Method</label>
                            <select class="form-select" id="method">
                                <option value="GET" selected>GET</option>
                                <option value="POST">POST</option>
                                <option value="PUT">PUT</option>
                                <option value="DELETE">DELETE</option>
                                <option value="PATCH">PATCH</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Target URL</label>
                            <input type="text" class="form-control" id="url"
                                   value="{{ $isDocker ? 'http://nginx:80/api' : $appUrl . '/api' }}"
                                   placeholder="http://example.com/api">
                        </div>
                    </div>
                    <!-- 추천 URL 퀵클릭 배지 -->
                    <div class="mt-2 d-flex gap-1 flex-wrap align-items-center" style="font-size: 0.75rem;">
                        <span class="text-muted me-1">URL 자동 입력:</span>
                        @if($isDocker)
                            <button type="button" class="btn btn-outline-secondary py-1 px-2 btn-quick-url" style="font-size: 0.72rem;" data-url="http://nginx:80/api">
                                도커 내부 (http://nginx:80/api)
                            </button>
                        @else
                            <button type="button" class="btn btn-outline-secondary py-1 px-2 btn-quick-url" style="font-size: 0.72rem;" data-url="{{ $appUrl }}/api">
                                현재 웹 도메인 ({{ $appUrl }}/api)
                            </button>
                        @endif
                    </div>
                </div>

                <!-- 부하 조건 방식 선택 -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">부하 설정 모드</label>
                        <label class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="stages-mode-switch">
                            <span class="form-check-label fs-5 fw-semibold text-primary">단계별 부하(Ramping) 사용</span>
                        </label>
                    </div>

                    <!-- 일반 단일 부하 입력 창 -->
                    <div class="row g-3" id="flat-load-inputs">
                        <div class="col-md-6">
                            <label class="form-label required">동시 사용자 수 (VUs)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-users"></i></span>
                                <input type="number" class="form-control" id="vus" min="1" max="200" value="3" placeholder="3">
                                <span class="input-group-text">명</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">테스트 기간 (Duration)</label>
                            <select class="form-select" id="duration">
                                <option value="5s" selected>5초 (빠른 확인)</option>
                                <option value="10s">10초</option>
                                <option value="30s">30초</option>
                                <option value="45s">45초 (최대)</option>
                            </select>
                        </div>
                    </div>

                    <!-- 단계별 점진 부하 (Stages) 입력 창 (기본 숨김) -->
                    <div class="border rounded p-3 bg-light d-none" id="stages-load-inputs">
                        <div class="text-muted mb-2 fs-5"><i class="ti ti-info-circle me-1"></i>시간의 흐름에 따라 사용자를 늘렸다가 줄이는 부하 곡선을 그립니다.</div>
                        <div class="row g-2 align-items-center mb-2">
                            <div class="col-4"><span class="badge bg-blue-lt">1단계: 부하 증가</span></div>
                            <div class="col-4">
                                <input type="text" class="form-control form-control-sm" id="stage-ramp-duration" value="5s" placeholder="기간 (예: 5s)">
                            </div>
                            <div class="col-4">
                                <input type="number" class="form-control form-control-sm" id="stage-ramp-target" value="20" placeholder="목표 인원">
                            </div>
                        </div>
                        <div class="row g-2 align-items-center mb-2">
                            <div class="col-4"><span class="badge bg-success-lt">2단계: 부하 유지</span></div>
                            <div class="col-4">
                                <input type="text" class="form-control form-control-sm" id="stage-maintain-duration" value="10s" placeholder="기간 (예: 10s)">
                            </div>
                            <div class="col-4">
                                <input type="number" class="form-control form-control-sm" id="stage-maintain-target" value="20" placeholder="목표 인원">
                            </div>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-4"><span class="badge bg-danger-lt">3단계: 부하 감소</span></div>
                            <div class="col-4">
                                <input type="text" class="form-control form-control-sm" id="stage-down-duration" value="5s" placeholder="기간 (예: 5s)">
                            </div>
                            <div class="col-4">
                                <input type="number" class="form-control form-control-sm" id="stage-down-target" value="0" placeholder="목표 인원">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 검증 기준 (Thresholds) -->
                <div class="mb-3">
                    <label class="form-label">목표 성능 검증 (Thresholds)</label>
                    <div class="border rounded p-3 bg-light">
                        <label class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="threshold-failed">
                            <span class="form-check-label">요청 실패율 1% 미만 (Error Rate < 1%)</span>
                        </label>
                        <label class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="threshold-p95">
                            <span class="form-check-label">95%의 요청 응답속도가 200ms 이하 (p(95) < 200ms)</span>
                        </label>
                        <label class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="threshold-p99">
                            <span class="form-check-label">99%의 요청 응답속도가 500ms 이하 (p(99) < 500ms)</span>
                        </label>
                    </div>
                </div>

                <!-- 헤더 설정 -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">HTTP 헤더 설정</label>
                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1" id="btn-add-header">
                            <i class="ti ti-plus me-1"></i>헤더 추가
                        </button>
                    </div>
                    <div id="headers-container">
                        <div class="row g-2 mb-2 header-row">
                            <div class="col-5">
                                <input type="text" class="form-control form-control-sm header-key" value="Content-Type" placeholder="Key">
                            </div>
                            <div class="col-5">
                                <input type="text" class="form-control form-control-sm header-val" value="application/json" placeholder="Value">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-header">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 요청 바디 (POST/PUT 등에서만 표시) -->
                <div class="mb-3 d-none" id="body-container">
                    <label class="form-label">요청 바디 (Request Body)</label>
                    <textarea class="form-control font-monospace" id="body-content" rows="3" placeholder='{&#10;  "key": "value"&#10;}'></textarea>
                </div>

                <!-- 아코디언 식 고급 설정 -->
                <div class="accordion border rounded bg-light" id="accordion-advanced">
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header bg-transparent">
                            <button class="accordion-button collapsed py-2 px-3 bg-transparent text-dark fw-semibold" style="font-size: 0.85rem;" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-advanced">
                                <i class="ti ti-settings-automation me-2"></i>고급 추가 옵션
                            </button>
                        </h2>
                        <div id="collapse-advanced" class="accordion-collapse collapse" data-bs-parent="#accordion-advanced">
                            <div class="accordion-body p-3 pt-1">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-check form-switch mb-2 mt-1">
                                            <input class="form-check-input" type="checkbox" id="opt-discard-bodies">
                                            <span class="form-check-label fs-5">응답 바디 무시 (성능 향상)</span>
                                        </label>
                                        <small class="text-muted d-block" style="font-size: 0.72rem; line-height: 1.2;">체크 시 응답 내용(Body)을 메모리에 담지 않아 부하기 성능을 향상시킵니다.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-check form-switch mb-2 mt-1">
                                            <input class="form-check-input" type="checkbox" id="opt-skip-tls" checked>
                                            <span class="form-check-label fs-5">SSL 검증 건너뛰기</span>
                                        </label>
                                        <small class="text-muted d-block" style="font-size: 0.72rem; line-height: 1.2;">로컬 개발 환경의 사설인증서(HTTPS) 사용 시 발생하는 연결 에러를 방지합니다.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-5">요청 타임아웃</label>
                                        <select class="form-select form-select-sm" id="opt-timeout">
                                            <option value="3s">3초</option>
                                            <option value="5s" selected>5초</option>
                                            <option value="10s">10초</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-5">User-Agent 커스텀</label>
                                        <input type="text" class="form-control form-control-sm" id="opt-user-agent" value="k6-stress-test" placeholder="User-Agent명">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- 3. 생성된 k6 스크립트 에디터 -->
    <div class="col-lg-6">
        <div class="card" style="min-height: 650px;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="ti ti-code me-2 text-primary"></i>k6 스크립트 (JavaScript)
                </h3>
                <label class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="custom-mode-switch">
                    <span class="form-check-label fs-5">스크립트 직접 수정</span>
                </label>
            </div>
            <div class="card-body p-0 d-flex flex-column">
                <textarea class="form-control font-monospace border-0 p-3 flex-fill bg-dark text-light"
                          id="script-editor"
                          style="resize: none; font-size: 0.85rem; min-height: 570px; outline: none; box-shadow: none;"
                          readonly></textarea>
            </div>
        </div>
    </div>

    <!-- 4. 실행 결과창 -->
    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="ti ti-terminal me-2 text-primary"></i>실행 결과 및 분석 로그
                </h3>
                <button type="button" id="btn-run-test" class="btn btn-success px-4" @if(!$isInstalled) disabled @endif>
                    <i class="ti ti-play-card me-2"></i>부하 테스트 실행
                </button>
            </div>
            <div class="card-body bg-dark text-light p-0 position-relative">
                <!-- 로딩 오버레이 -->
                <div id="run-loader" class="d-none position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-dark" style="--bs-bg-opacity: .85; z-index: 10;">
                    <div class="spinner-border text-success mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <div class="fs-3 fw-bold text-success">부하 테스트 진행 중...</div>
                    <div class="text-muted mt-2" id="loader-timer">소요 시간: 0초</div>
                </div>

                <pre class="m-0 p-4 font-monospace text-light" id="output-console" style="min-height: 250px; max-height: 600px; overflow-y: auto; white-space: pre-wrap; font-size: 0.85rem;">[대기 중] 좌측 옵션을 구성하거나 스크립트를 작성한 뒤 "부하 테스트 실행" 버튼을 눌러주세요.</pre>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // 1. K6 자동 설치 기능
    $('#btn-install-k6').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> 설치 중...');

        $.ajax({
            url: "{{ route('settings.k6.install') }}",
            type: "POST",
            beforeSend: $.noop,
            complete: $.noop,
            data: { _token: csrfToken },
            success: function(res) {
                if (res.status === 200) {
                    Swal.fire({
                        icon: 'success',
                        title: '설치 완료',
                        text: res.msg,
                        confirmButtonText: '확인'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '설치 실패',
                        text: res.msg,
                        confirmButtonText: '확인'
                    });
                    $btn.prop('disabled', false).html('<i class="ti ti-download me-1"></i> K6 자동 설치');
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: '에러 발생',
                    text: '바이너리 설치 중 예상치 못한 네트워크 에러가 발생했습니다.',
                    confirmButtonText: '확인'
                });
                $btn.prop('disabled', false).html('<i class="ti ti-download me-1"></i> K6 자동 설치');
            }
        });
    });

    // 2. HTTP 메서드 변경 시 바디 영역 숨김/표시 처리
    $('#method').on('change', function() {
        const method = $(this).val();
        if (['POST', 'PUT', 'PATCH'].includes(method)) {
            $('#body-container').removeClass('d-none');
        } else {
            $('#body-container').addClass('d-none');
        }
        generateScript();
    });

    // 3. 퀵 URL 입력 배지 버튼 클릭 이벤트
    $('.btn-quick-url').on('click', function() {
        const url = $(this).data('url');
        $('#url').val(url);
        generateScript();
    });

    // 4. 부하 모드 스위치 이벤트 (단일 vs 단계별)
    $('#stages-mode-switch').on('change', function() {
        if ($(this).is(':checked')) {
            $('#flat-load-inputs').addClass('d-none');
            $('#stages-load-inputs').removeClass('d-none');
        } else {
            $('#flat-load-inputs').removeClass('d-none');
            $('#stages-load-inputs').addClass('d-none');
        }
        generateScript();
    });

    // 5. 헤더 동적 추가/제거
    $('#btn-add-header').on('click', function() {
        const rowHtml = `
            <div class="row g-2 mb-2 header-row">
                <div class="col-5">
                    <input type="text" class="form-control form-control-sm header-key" placeholder="Key">
                </div>
                <div class="col-5">
                    <input type="text" class="form-control form-control-sm header-val" placeholder="Value">
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-header">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#headers-container').append(rowHtml);
        bindHeaderEvents();
        generateScript();
    });

    function bindHeaderEvents() {
        $('.btn-remove-header').off('click').on('click', function() {
            $(this).closest('.header-row').remove();
            generateScript();
        });
        $('.header-key, .header-val').off('input').on('input', function() {
            generateScript();
        });
    }
    bindHeaderEvents();

    // 6. 입력 필드 값 변경 시 스크립트 실시간 반영
    $('#url, #vus, #duration, #body-content, #threshold-failed, #threshold-p95, #threshold-p99').on('input change', function() {
        generateScript();
    });
    $('#stage-ramp-duration, #stage-ramp-target, #stage-maintain-duration, #stage-maintain-target, #stage-down-duration, #stage-down-target').on('input change', function() {
        generateScript();
    });
    $('#opt-discard-bodies, #opt-skip-tls, #opt-timeout, #opt-user-agent').on('input change', function() {
        generateScript();
    });

    // 7. 스크립트 직접 수정 모드 스위치 처리
    $('#custom-mode-switch').on('change', function() {
        const checked = $(this).is(':checked');
        $('#script-editor').prop('readonly', !checked);
        if (!checked) {
            generateScript();
        }
    });

    // 8. 스크립트 생성 로직
    function generateScript() {
        if ($('#custom-mode-switch').is(':checked')) {
            return;
        }

        const method = $('#method').val();
        let defaultUrl = '{{ $isDocker ? "http://nginx:80/api" : $appUrl . "/api" }}';
        const url = $('#url').val().trim() || defaultUrl;

        const isStages = $('#stages-mode-switch').is(':checked');

        let loadOptionsStr = '';
        if (isStages) {
            const rDuration = $('#stage-ramp-duration').val() || '5s';
            const rTarget = $('#stage-ramp-target').val() || 20;
            const mDuration = $('#stage-maintain-duration').val() || '10s';
            const mTarget = $('#stage-maintain-target').val() || 20;
            const dDuration = $('#stage-down-duration').val() || '5s';
            const dTarget = $('#stage-down-target').val() || 0;

            loadOptionsStr = `\n  stages: [\n` +
                             `    { duration: '${rDuration}', target: ${rTarget} }, // 램프업 (부하 증가)\n` +
                             `    { duration: '${mDuration}', target: ${mTarget} }, // 유지\n` +
                             `    { duration: '${dDuration}', target: ${dTarget} }, // 램프다운 (부하 감소)\n` +
                             `  ],`;
        } else {
            const vus = $('#vus').val() || 3;
            const duration = $('#duration').val() || '5s';
            loadOptionsStr = `\n  vus: ${vus},\n  duration: '${duration}',`;
        }

        // 고급 설정
        const discardBodies = $('#opt-discard-bodies').is(':checked');
        const skipTls = $('#opt-skip-tls').is(':checked');

        let advOptionsStr = '';
        if (discardBodies) {
            advOptionsStr += `\n  discardResponseBodies: true,`;
        }
        if (skipTls) {
            advOptionsStr += `\n  insecureSkipTLSVerify: true,`;
        }

        // 검증 조건(Thresholds)
        let thresholds = [];
        if ($('#threshold-failed').is(':checked')) {
            thresholds.push("    'http_req_failed': ['rate<0.01'], // 에러율 1% 미만");
        }
        if ($('#threshold-p95').is(':checked')) {
            thresholds.push("    'http_req_duration': ['p(95)<200'], // 95% 응답속도 200ms 이하");
        }
        if ($('#threshold-p99').is(':checked')) {
            thresholds.push("    'http_req_duration': ['p(99)<500'], // 99% 응답속도 500ms 이하");
        }

        let thresholdsStr = '';
        if (thresholds.length > 0) {
            thresholdsStr = `\n  thresholds: {\n${thresholds.join(',\n')}\n  },`;
        }

        // 헤더 가공
        let headersObj = {};
        $('.header-row').each(function() {
            const key = $(this).find('.header-key').val().trim();
            const val = $(this).find('.header-val').val().trim();
            if (key) {
                headersObj[key] = val;
            }
        });

        // User-Agent 자동 바인딩
        const userAgent = $('#opt-user-agent').val().trim();
        if (userAgent) {
            headersObj['User-Agent'] = userAgent;
        }

        const body = $('#body-content').val().trim() || '';
        let bodyStr = 'null';
        if (['POST', 'PUT', 'PATCH'].includes(method) && body) {
            try {
                JSON.parse(body);
                bodyStr = `JSON.stringify(${body})`;
            } catch (e) {
                bodyStr = `\`${body.replace(/`/g, '\\`').replace(/\${/g, '\\${')}\``;
            }
        }

        let headersStr = '';
        if (Object.keys(headersObj).length > 0) {
            headersStr = '    headers: {\n';
            for (const [k, v] of Object.entries(headersObj)) {
                headersStr += `      '${k}': '${v}',\n`;
            }
            headersStr += '    },\n';
        }

        const reqTimeout = $('#opt-timeout').val() || '5s';

        const scriptLines = [
            "import http from 'k6/http';",
            "import { sleep, check } from 'k6';",
            "",
            `export const options = {${loadOptionsStr}${advOptionsStr}${thresholdsStr}`,
            "};",
            "",
            "export default function () {",
            `  const url = '${url}';`,
            `  const payload = ${bodyStr};`,
            "  const params = {",
            `${headersStr}    timeout: '${reqTimeout}',`,
            "  };",
            "",
            `  const res = http.request('${method}', url, payload, params);`,
            "",
            "  // 응답값 출력용 로그",
            "  if (res.body) {",
            "    console.log(\`[VU:\${__VU}] Response: \${res.body}\`);",
            "  } else {",
            "    console.log(\`[VU:\${__VU}] Status: \${res.status} (Body 없음 또는 '응답 바디 무시' 활성화 상태)\`);",
            "  }",
            "",
            "  check(res, {",
            "    'status is 200 or 409 (정상)': (r) => r.status === 200 || r.status === 409,",
            "    'response duration < 1000ms': (r) => r.timings.duration < 1000,",
            "  });",
            "",
            "  sleep(1);",
            "}"
        ];
        const scriptContent = scriptLines.join('\n');

        $('#script-editor').val(scriptContent);
    }

    generateScript();

    // 9. 부하 테스트 실행
    $('#btn-run-test').on('click', function() {
        const script = $('#script-editor').val();
        if (!script.trim()) {
            Swal.fire({ icon: 'warning', title: '경고', text: '실행할 스크립트가 없습니다.' });
            return;
        }

        $('#run-loader').removeClass('d-none');
        $('#btn-run-test').prop('disabled', true);

        let seconds = 0;
        const timerInterval = setInterval(function() {
            seconds++;
            $('#loader-timer').text(`소요 시간: ${seconds}초`);
        }, 1000);

        $('#output-console').html('<span class="text-success">[정보] K6 부하 테스트 엔진을 시작하는 중...</span>\n');

        $.ajax({
            url: "{{ route('settings.k6.run') }}",
            type: "POST",
            beforeSend: $.noop,
            complete: $.noop,
            data: {
                _token: csrfToken,
                script: script
            },
            success: function(res) {
                clearInterval(timerInterval);
                $('#run-loader').addClass('d-none');
                $('#btn-run-test').prop('disabled', false);

                if (res.status === 200) {
                    let output = '';
                    if (res.stdout) output += res.stdout;
                    if (res.stderr) output += '\n--- 응답 로그 ---\n' + res.stderr;

                    if (!output.trim()) {
                        output = '출력 로그가 비어있습니다.';
                    } else {
                        output = formatK6Log(output);
                    }

                    $('#output-console').html(output);

                    const consoleEl = document.getElementById('output-console');
                    consoleEl.scrollTop = consoleEl.scrollHeight;

                    Swal.fire({
                        icon: 'success',
                        title: '테스트 완료',
                        text: '부하 테스트가 정상적으로 종료되었습니다.',
                        confirmButtonText: '확인'
                    });
                } else {
                    $('#output-console').text('[오류] 실행 실패:\n' + res.msg);
                    Swal.fire({
                        icon: 'error',
                        title: '실행 실패',
                        text: res.msg,
                        confirmButtonText: '확인'
                    });
                }
            },
            error: function(xhr) {
                clearInterval(timerInterval);
                $('#run-loader').addClass('d-none');
                $('#btn-run-test').prop('disabled', false);

                let errorMsg = '부하 테스트 요청 실행 중 서버 에러가 발생했습니다.';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    errorMsg = xhr.responseJSON.msg;
                }

                $('#output-console').text('[시스템 에러] ' + errorMsg);
                Swal.fire({
                    icon: 'error',
                    title: '시스템 에러',
                    text: errorMsg,
                    confirmButtonText: '확인'
                });
            }
        });
    });

    // Unicode 디코딩 헬퍼 함수
    function decodeUnicode(str) {
        return str.replace(/\\u([0-9a-fA-F]{4})/g, function (match, grp) {
            return String.fromCharCode(parseInt(grp, 16));
        });
    }

    // K6 로그 가공 및 예쁘게 포맷팅
    function formatK6Log(rawText) {
        if (!rawText) return '';
        let decoded = decodeUnicode(rawText);
        let lines = decoded.split('\n');

        // 시간(초 단위)별 고유 색상 매핑을 위한 색상 테이블
        const groupColors = [
            'bg-blue',
            'bg-azure',
            'bg-indigo',
            'bg-purple',
            'bg-pink',
            'bg-orange',
            'bg-teal',
            'bg-green',
            'bg-lime',
            'bg-cyan',
            'bg-yellow text-dark'
        ];

        // 텍스트 기반 간단한 해싱 함수
        function getGroupColor(timeStr) {
            let hash = 0;
            for (let i = 0; i < timeStr.length; i++) {
                hash = timeStr.charCodeAt(i) + ((hash << 5) - hash);
            }
            let index = Math.abs(hash) % groupColors.length;
            return groupColors[index];
        }

        let formattedLines = lines.map(line => {
            // k6 console log 정규식 매칭 (time 값 그룹 매칭 추가)
            let match = line.match(/time="([^"]+)" level=info msg="\[VU:(\d+)\] (Response|Status): (.*?)" source=console/);
            if (match) {
                let timeRaw = match[1];
                let vu = match[2];
                let type = match[3];
                let payload = match[4];

                // 시:분:초 (HH:mm:ss) 추출
                let timeMatch = timeRaw.match(/(\d{2}):(\d{2}):(\d{2})/);
                let timeKey = timeMatch ? timeMatch[0] : timeRaw;

                // 2. 이스케이프 백슬래시 및 이중 이스케이프 쌍따옴표 완벽 정화
                let cleanedPayload = payload.replace(/\\"/g, '"').replace(/\\/g, '');

                // 초 단위로 동일한 시간대에 발생한 요청들에 동일한 색상 지정
                let groupColor = getGroupColor(timeKey);

                try {
                    let json = JSON.parse(cleanedPayload);
                    let prettyJson = JSON.stringify(json, null, 2);

                    // API 응답 상태(Status)에 따른 별도의 미니 뱃지 색상
                    let statusColor = 'bg-secondary';
                    if (json.status === 200) {
                        statusColor = json.cached ? 'bg-info-lt' : 'bg-success-lt';
                    } else if (json.status === 409) {
                        statusColor = 'bg-warning-lt';
                    } else {
                        statusColor = 'bg-danger-lt';
                    }
                    let statusText = json.status ? `${json.status}${json.cached ? ' (Cached)' : ''}` : 'Unknown';

                    return `<div class="py-2 border-bottom border-dark border-opacity-25 text-white">` +
                           `<span class="badge ${groupColor} text-white px-2 py-1 fs-5 me-2">${timeKey} 요청 (VU ${vu})</span>` +
                           `<span class="badge ${statusColor} px-2 py-1 fs-5">${statusText}</span>` +
                           `<pre class="m-0 mt-1 p-2 text-white bg-dark border border-secondary rounded font-monospace" style="font-size: 0.8rem; white-space: pre-wrap; line-height: 1.4;">${prettyJson}</pre>` +
                           `</div>`;
                } catch (e) {
                    return `<div class="py-2 border-bottom border-dark border-opacity-25 text-muted">` +
                           `<span class="badge ${groupColor} text-white px-2 py-1 fs-5 me-2">${timeKey} 요청 (VU ${vu})</span>` +
                           `<span class="badge bg-danger-lt px-2 py-1 fs-5">ERROR</span>` +
                           `<pre class="m-0 mt-1 p-2 text-muted bg-dark border border-secondary rounded font-monospace" style="font-size: 0.8rem; white-space: pre-wrap;">${cleanedPayload}</pre>` +
                           `</div>`;
                }
            }

            // 일반 K6 결과 메트릭 라인들에 스타일 적용
            if (line.includes('http_reqs') || line.includes('vus') || line.includes('iteration') || line.includes('http_req_duration')) {
                return `<span class="text-success-lt">${line}</span>`;
            }
            if (line.includes('✗') || line.includes('✗ status is 200 or 409')) {
                return `<span class="text-danger fw-bold">${line}</span>`;
            }
            if (line.includes('✓') || line.includes('✓ status is 200 or 409')) {
                return `<span class="text-success fw-bold">${line}</span>`;
            }
            return line;
        });

        return formattedLines.join('\n');
    }
});
</script>
@endsection
