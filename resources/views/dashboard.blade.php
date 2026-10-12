@php
    use Carbon\Carbon;
    use App\Enums\Role;
@endphp
@extends('layouts.app')

@section('title', '대시보드')

@section('content')
    <div class="page-header d-print-none mt-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="text-muted mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="12" cy="12" r="9"/>
                        <polyline points="12 7 12 12 15 15"/>
                    </svg>
                    마지막 업데이트: {{ now()->format('Y-m-d H:i:s') }}
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button class="btn btn-primary align-items-baseline" onclick="location.reload()">
                    <i class="ti ti-refresh me-1"></i>
                    새로고침
                </button>
                @if (auth('admin')->user()->hasRole(Role::SuperAdmin->value))
                    {{-- <button class="btn btn-teal align-items-baseline" onclick="window.open('{{ route('admin.erd.index') }}', '_blank')">
                        <i class="ti ti-database me-1"></i>
                        ERD
                    </button> --}}
                    <button class="btn btn-warning align-items-baseline" onclick="window.open('{{ route('log-viewer.index') }}', '_blank')">
                        <i class="ti ti-terminal-2 me-1"></i>
                        시스템 로그
                    </button>
                    @if (env('QUEUE_CONNECTION', '') === 'redis')
                        <button class="btn btn-danger align-items-baseline" onclick="window.open('/horizon', '_blank')">
                            <i class="ti ti-stack-2 me-1"></i>
                            Queue Monitor
                        </button>
                    @endif
                    <button class="btn btn-purple align-items-baseline" onclick="window.open('/pulse', '_blank')">
                        <i class="ti ti-activity me-1"></i>
                        Pulse Monitor
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="page-body">

        {{-- ─── 방문자 통계 ─────────────────────────────────────── --}}
        <div class="row row-deck row-cards mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-users me-1"></i>
                            방문자 통계
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="card card-sm bg-blue-lt border-0">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-blue text-white avatar">
                                                    <i class="ti ti-users me-1"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <div class="font-weight-medium">전체 누적 방문자</div>
                                                <div class="text-muted">{{ number_format($stats['total_visitors'] ?? 0) }} 명</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="card card-sm bg-green-lt border-0">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-green text-white avatar">
                                                    <i class="ti ti-user me-1"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <div class="font-weight-medium">금일 방문자</div>
                                                <div class="text-muted">{{ number_format($stats['today_visitors'] ?? 0) }} 명</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="card card-sm bg-yellow-lt border-0">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-yellow text-white avatar">
                                                    <i class="ti ti-history me-1"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <div class="font-weight-medium">어제 방문자</div>
                                                <div class="text-muted">{{ number_format($stats['yesterday_visitors'] ?? 0) }} 명</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-3">

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">
                            실시간 접속 관리자
                            <span class="badge bg-green text-white ms-1">{{ count($onlineUsers ?? []) }}명</span>
                        </h3>
                    </div>
                    <div class="card-body p-0" style="max-height: 320px; overflow-y: auto;">
                        @forelse($onlineUsers ?? [] as $user)
                            <div class="d-flex align-items-center px-3 py-2 border-bottom">
                                <span class="avatar avatar-sm rounded-circle me-2">
                                    {{ mb_substr($user['name'], 0, 1) }}
                                </span>
                                <div class="flex-fill">
                                    <div class="fw-medium text-truncate" style="max-width: 140px;">{{ $user['name'] }}</div>
                                    <a href="{{ $user['current_page'] ?? '#' }}" target="_blank" class="d-block text-muted small text-truncate" style="max-width: 140px;">{{ $user['current_page'] ?? '-' }}</a>
                                    <div class="text-muted fs-6">{{ $user['last_active_at_raw'] ?? '' }}</div>
                                </div>
                                <div class="ms-auto text-end">
                                    <span class="text-muted small">
                                        @if(!empty($user['last_active_at']) && $user['last_active_at'] !== 'offline')
                                            {{ $user['last_active_at'] ?? '' }}
                                        @endif
                                    </span>
                                    <div>
                                        @if(($user['last_active_at'] ?? '') === 'offline')
                                            <span class="badge bg-red-lt text-red">오프라인</span>
                                        @else
                                            <span class="badge bg-green-lt text-green">접속중</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5 align-content-center h-100">
                                <p>현재 접속 중인 관리자가 없습니다</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ─── 서버 사양 ───────────────────────────────────────── --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">
                            서버 사양 및 메모리
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">디스크 사용률</span>
                                <span class="fw-medium">{{ $server['disk_used'] }} / {{ $server['disk_total'] }} GB</span>
                            </div>
                            @php
                                $memPercent = $server['disk_total'] > 0
                                    ? round(($server['disk_used'] / $server['disk_total']) * 100)
                                    : 0;
                                $memColor = $memPercent > 85 ? 'bg-danger' : ($memPercent > 60 ? 'bg-warning' : 'bg-dark');
                            @endphp
                            <div class="progress mb-1" style="height: 8px;">
                                <div class="progress-bar {{ $memColor }}" style="width: {{ $memPercent }}%" role="progressbar">
                                    <span class="visually-hidden">{{ $memPercent }}%</span>
                                </div>
                            </div>
                            <div class="text-end small text-muted">{{ $memPercent }}%</div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">메모리 사용률</span>
                                <span class="fw-medium">{{ $server['memory_used'] }} / {{ $server['memory_total'] }} MB</span>
                            </div>
                            @php
                                $memPercent = $server['memory_total'] > 0
                                    ? round(($server['memory_used'] / $server['memory_total']) * 100)
                                    : 0;
                                $memColor = $memPercent > 85 ? 'bg-danger' : ($memPercent > 60 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="progress mb-1" style="height: 8px;">
                                <div class="progress-bar {{ $memColor }}" style="width: {{ $memPercent }}%" role="progressbar">
                                    <span class="visually-hidden">{{ $memPercent }}%</span>
                                </div>
                            </div>
                            <div class="text-end small text-muted">{{ $memPercent }}%</div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">CPU 사용률</span>
                                <span class="fw-medium">{{ $server['cpu_usage'] }}%</span>
                            </div>
                            @php
                                $cpuPercent = $server['cpu_usage'];
                                $cpuColor   = $cpuPercent > 85 ? 'bg-danger' : ($cpuPercent > 60 ? 'bg-warning' : 'bg-azure');
                            @endphp
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $cpuColor }}" style="width: {{ $cpuPercent }}%" role="progressbar"></div>
                            </div>
                        </div>

                        <div class="row g-2 mt-1">
                            <div class="col-6">
                                <div class="text-muted small">PHP 버전</div>
                                <div class="fw-medium">{{ $server['php_version'] }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Laravel 버전</div>
                                <div class="fw-medium">{{ $server['laravel_version'] }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">OS</div>
                                <div class="fw-medium">{{ $server['os'] }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">업타임</div>
                                <div class="fw-medium">{{ $server['uptime'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-3">

            {{-- ─── 스케줄 리스트 ───────────────────────────────────── --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">
                            예정된 스케줄
                        </h3>
                    </div>
                    <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                        <table class="table table-vcenter card-table table-hover">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th>작업명</th>
                                    <th>표현식</th>
                                    <th>다음 실행</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(empty($schedules))
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">등록된 스케줄이 없습니다</td>
                                    </tr>
                                @else
                                    @foreach($schedules as $schedule)
                                        @php
                                            $nextRun      = Carbon::parse($schedule['next_run']);
                                            $minutesUntil = now()->diffInMinutes($nextRun, false);   // false = 부호 유지
                                            $isClose      = $minutesUntil > 0 && $minutesUntil < 5;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-medium">{{ $schedule['name'] }}</div>
                                                <div class="text-muted small">{{ $schedule['description'] ?? '' }}</div>
                                            </td>
                                            <td>
                                                <code class="small">{{ $schedule['expression'] }}</code>
                                            </td>
                                            <td>
                                                <span class="{{ $isClose ? 'text-danger fw-bold' : 'text-muted' }} small">
                                                    {{ $nextRun->diffForHumans() }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($schedule['running'] ?? false)
                                                    <span class="badge bg-yellow-lt text-yellow">실행중</span>
                                                @else
                                                    <span class="badge bg-green-lt text-green">대기</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ─── HTTP 상태 코드 통계 ─────────────────────────────── --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">
                            금일 HTTP 응답 코드
                        </h3>
                    </div>
                    <div class="card-body">
                        @forelse($httpStats ?? [] as $code => $count)
                        @php
                            $total = array_sum($httpStats);
                            $percent = $total > 0 ? round(($count / $total) * 100) : 0;
                            $barClass = match(true) {
                                $code >= 500 => 'bg-danger',
                                $code >= 400 => 'bg-warning',
                                $code >= 300 => 'bg-info',
                                default      => 'bg-success',
                            };
                            $badgeClass = match(true) {
                                $code >= 500 => 'bg-red-lt text-red',
                                $code >= 400 => 'bg-yellow-lt text-yellow',
                                $code >= 300 => 'bg-azure-lt text-azure',
                                default      => 'bg-green-lt text-green',
                            };
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <div>
                                    <span class="badge {{ $badgeClass }} me-1">{{ $code }}</span>
                                    <span class="text-muted small">{{ $httpStatusLabels[$code] ?? '' }}</span>
                                </div>
                                <span class="fw-medium">{{ number_format($count) }}건 ({{ $percent }}%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar {{ $barClass }}" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4">금일 기록된 응답 코드가 없습니다</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Laravel 로그 ────────────────────────────────────────── --}}
        <div class="row row-deck row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            금일 Laravel 로그
                        </h3>
                        <div class="card-options">
                            <div class="ms-2 d-flex gap-1">
                                @if(!empty($logs))
                                    <button type="submit" class="btn btn-outline-danger btn-sm btn-log-delete">
                                        <i class="ti ti-trash me-1"></i>로그 삭제
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0" style="max-height: 480px; overflow-y: auto;" id="logContainer">
                        @forelse($logs ?? [] as $log)
                        @php
                            $levelUpper = strtoupper($log['level'] ?? 'INFO');
                            $rowClass   = match($levelUpper) {
                                'ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT' => 'table-danger',
                                'WARNING' => 'table-warning',
                                'DEBUG'   => 'text-muted',
                                default   => '',
                            };
                            $badgeColor = match($levelUpper) {
                                'ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT' => 'bg-red',
                                'WARNING'  => 'bg-yellow',
                                'INFO'     => 'bg-azure',
                                'DEBUG'    => 'bg-secondary',
                                default    => 'bg-secondary',
                            };
                        @endphp
                        <div class="log-entry px-3 py-2 border-bottom {{ $rowClass }}" data-level="{{ $levelUpper }}">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge {{ $badgeColor }} text-white flex-shrink-0 mt-1" style="font-size: 0.65rem; min-width: 58px; text-align: center;">
                                    {{ $levelUpper }}
                                </span>
                                <div class="flex-fill" style="min-width: 0;">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">{{ $log['time'] ?? '' }}</span>
                                        @if(!empty($log['env']))
                                        <span class="badge bg-light text-muted small">{{ $log['env'] }}</span>
                                        @endif
                                    </div>
                                    <div class="text-truncate" style="max-width: 100%; font-family: monospace; font-size: 0.82rem;">
                                        {{ $log['message'] ?? '' }}
                                    </div>
                                    @if(!empty($log['context']))
                                        <div class="mt-1">
                                            <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" href="#trace-{{ $loop->index }}">
                                                <i class="ti ti-code me-1"></i>스택 트레이스
                                            </a>
                                            <div class="collapse mt-3" id="trace-{{ $loop->index }}">
                                                <pre class="p-4 rounded small" style="white-space: pre-wrap; word-break: break-all; font-size: 0.75rem; max-height: 300px; overflow-y: auto;">{!! e($log['context']) !!}</pre>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-5">
                            <p>금일 로그가 없습니다</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function(){
            $('.btn-log-delete').click(async function(){
                if(await salert({text: '금일 로그를 삭제하시겠습니까?'})){
                    $.ajax({
                        url: "{{ route('admin.dashboard.log.delete') }}",
                        type: 'DELETE',
                        success: function(res) {
                            alert(res?.msg);
                            if (res?.status === 200) {
                                location.reload();
                            }
                        },
                        error: function(xhr) {
                            var res = xhr?.responseJSON;
                            alert(res?.error?.message ?? '오류가 발생했습니다.');
                        }
                    });
                }
            });
        });
    </script>
@endsection

