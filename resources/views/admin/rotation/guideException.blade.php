@extends('layouts.app')

@section('title', '예외 상황 대처')

@section('content')
<div class="row row-cards mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h3 class="card-title mb-0">
                    <i class="ti ti-alert-triangle text-danger me-1"></i> 예외 상황 대처 설명서
                </h3>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left me-1"></i> 대시보드로
                </a>
            </div>
            <div class="card-body">
                <p class="text-muted">행사 현장에서 자주 생기는 돌발 상황별 <b>처리 방법</b>을 정리했습니다.</p>

                <div class="alert alert-important alert-info my-3">
                    <div class="fw-bold mb-2"><i class="ti ti-bulb me-1"></i> 먼저 알아두세요</div>
                    <ul class="lh-lg mb-0">
                        <li>자리 변경은 <b>[좌석 배정] 화면의 1턴에서</b> 하면 됩니다.</li>
                        <li>1턴에서 사람을 옮기거나 추가/삭제하면 <b>그 뒤 모든 턴에도 자동으로 반영</b>돼요.</li>
                        <li>2턴 이상 화면은 <b>보기 전용</b>입니다. 자리를 바꾸려면 꼭 <b>1턴 화면</b>으로 이동하세요.</li>
                        <li>로테이션이 진행 중이어도 <b>1턴 화면에서는 언제든 수정</b>할 수 있어요.</li>
                    </ul>
                </div>

                <div class="row g-3">
                    {{-- ① 중간 입장 --}}
                    <div class="col-md-6">
                        <div class="card card-sm h-100">
                            <div class="card-body">
                                <div class="fw-bold text-blue mb-2"><i class="ti ti-user-plus me-1"></i> ① 로테이션이 시작된 뒤 늦게 온 참가자</div>
                                <ul class="lh-lg mb-0">
                                    <li>참가자가 <b>입장등록할 때 테이블을 직접 고르면</b> 바로 그 자리에 들어갑니다. 따로 배정하지 않아도 돼요.</li>
                                    <li>직접 넣어주려면 <b>[좌석 배정] 1턴 화면</b>에서 그 사람을 원하는 테이블에 추가하세요.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- ② 테이블 합치기 --}}
                    <div class="col-md-6">
                        <div class="card card-sm h-100">
                            <div class="card-body">
                                <div class="fw-bold text-blue mb-2"><i class="ti ti-arrows-join me-1"></i> ② 인원이 적어 테이블을 합칠 때 (예: 4번 → 3번)</div>
                                <ul class="lh-lg mb-0">
                                    <li><b>[좌석 배정] 1턴 화면</b>으로 갑니다. (진행 중에도 가능)</li>
                                    <li>4번 테이블 사람들을 <b>3번 테이블로 옮깁니다.</b></li>
                                    <li>그다음 <b>비어버린 4번 테이블을 삭제</b>하세요. (행사 상세 화면에서 테이블 삭제) 삭제하지 않으면 다음 턴에 남자가 <b>빈 테이블로 이동</b>해 버립니다.</li>
                                    <li>옮긴 뒤 <span class="badge bg-pink-lt">여자</span> 는 계속 3번에 앉고, <span class="badge bg-blue-lt">남자</span> 는 다음 턴부터 다시 이동합니다.</li>
                                    <li><b>주의:</b> 2턴 이상 화면은 <b>보기 전용</b>이에요. 옮길 땐 꼭 <b>1턴 화면</b>에서 하세요.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- ③ 잘못 진행 --}}
                    <div class="col-md-6">
                        <div class="card card-sm h-100">
                            <div class="card-body">
                                <div class="fw-bold text-blue mb-2"><i class="ti ti-rotate-2 me-1"></i> ③ 로테이션을 실수로 한 번 더 눌렀을 때</div>
                                <ul class="lh-lg mb-0">
                                    <li><b>[좌석 배정] 화면</b> 하단의 <b>[로테이션 되돌리기]</b> 버튼을 누르면 <b>바로 이전 턴으로 돌아갑니다.</b></li>
                                    <li>여러 번 눌렀다면 되돌리기도 <b>그만큼 반복</b>해서 누르면 됩니다.</li>
                                    <li>단, <b>매칭을 시작/종료한 뒤에는</b> 되돌릴 수 없어요. (매칭 단계로 넘어갔기 때문)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="alert alert-important alert-warning py-2 mt-3 mb-0">
                    <i class="ti ti-help me-1"></i> 위 방법으로 해결되지 않으면 임의로 조작하지 말고 <b>운영/개발 담당자에게 문의</b>하세요.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
