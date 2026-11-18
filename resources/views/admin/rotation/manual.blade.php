@extends('layouts.app')

@section('title', '사용 설명서')

@section('content')
{{--
    관리자 사용 설명서.
    - 서비스의 전체 순서도, 행사 상태 전이, 로테이션/매칭 로직, 화면별 사용법을 한 페이지에 정리한다.
    - 순서도·상태도는 이미지 없이 HTML/CSS 로 그린다. (외부 라이브러리 불필요)
--}}
<style>
    .mn-toc{ position:sticky; top:84px; }
    .mn-toc a{ display:block; padding:.4rem .6rem; border-radius:8px; color:#374151; text-decoration:none; font-weight:600; font-size:.9rem; }
    .mn-toc a:hover{ background:#f1f5f9; }
    .mn-sec{ scroll-margin-top:90px; }
    .mn-sec h2{ font-size:1.25rem; font-weight:800; margin:0; }
    .mn-lead{ color:#6b7280; margin:.25rem 0 1rem; }
    .mn-sub{ font-weight:800; margin:1.1rem 0 .4rem; display:flex; align-items:center; gap:.4rem; }
    .mn-list{ padding-left:1.1rem; line-height:1.85; margin-bottom:.5rem; }
    .mn-list li{ margin-bottom:.1rem; }

    /* 상태 전이 도표 */
    .mn-states{ display:flex; flex-wrap:wrap; align-items:stretch; gap:.5rem; }
    .mn-state{ flex:1 1 150px; border-radius:14px; padding:.75rem .85rem; color:#fff; }
    .mn-state b{ display:block; font-size:1.05rem; }
    .mn-state small{ display:block; opacity:.9; margin-top:.2rem; line-height:1.45; }
    .mn-arrow{ flex:0 0 auto; align-self:center; text-align:center; color:#6b7280; font-size:.78rem; line-height:1.3; max-width:120px; }
    .mn-arrow i{ display:block; font-size:1.4rem; }
    .mn-p{ background:#868e96; } .mn-s{ background:#206bc4; } .mn-v{ background:#ae3ec9; } .mn-x{ background:#343a40; }

    /* 순서도: 관리자 | 참가자 두 줄 */
    .mn-flow{ display:grid; grid-template-columns:minmax(0,1fr) 34px minmax(0,1fr); gap:.5rem .5rem; align-items:stretch; }
    .mn-flow .mn-head{ font-weight:800; text-align:center; padding:.4rem; border-radius:10px; }
    .mn-flow .mn-head.is-admin{ background:#e7f1ff; color:#1c5fb0; }
    .mn-flow .mn-head.is-member{ background:#ffeef4; color:#c2255c; }
    .mn-cell{ border:1px solid #e6e8eb; border-radius:12px; padding:.6rem .75rem; background:#fff; font-size:.9rem; line-height:1.6; }
    .mn-cell.is-empty{ border:none; background:transparent; }
    .mn-cell .mn-no{ display:inline-flex; width:1.5rem; height:1.5rem; align-items:center; justify-content:center; border-radius:50%; background:#206bc4; color:#fff; font-weight:800; font-size:.8rem; margin-right:.35rem; }
    .mn-mid{ display:flex; align-items:center; justify-content:center; color:#adb5bd; font-size:1.1rem; }
    .mn-stage{ grid-column:1 / -1; text-align:center; font-weight:800; font-size:.8rem; color:#fff; border-radius:999px; padding:.15rem .8rem; justify-self:center; }
    @media (max-width: 767.98px){
        .mn-flow{ grid-template-columns:minmax(0,1fr); }
        .mn-flow .mn-mid{ display:none; }
        .mn-flow .mn-head.is-member{ display:none; }
        .mn-cell.is-member{ border-left:4px solid #e8709a; }
        .mn-cell.is-admin{ border-left:4px solid #4a8fe7; }
        .mn-cell.is-empty{ display:none; }
    }

    /* 로테이션 예시표 */
    .mn-rot{ width:100%; border-collapse:separate; border-spacing:0; font-size:.88rem; text-align:center; }
    .mn-rot th, .mn-rot td{ border:1px solid #e6e8eb; padding:.4rem .5rem; }
    .mn-rot th{ background:#f6f8fb; font-weight:700; }
    .mn-rot td.is-f{ background:#fff0f5; color:#c2255c; font-weight:700; }
    .mn-rot td.is-m{ background:#eef5ff; color:#1c5fb0; font-weight:700; }

    .mn-tip{ border-left:4px solid #f0b400; background:#fff9e6; border-radius:8px; padding:.55rem .8rem; margin:.6rem 0; font-size:.9rem; line-height:1.65; }
    .mn-warn{ border-left:4px solid #e03131; background:#fff5f5; }
    .mn-ok{ border-left:4px solid #2fb344; background:#f1fbf3; }
    .mn-kbd{ display:inline-block; padding:.05rem .45rem; border-radius:6px; background:#f1f3f5; border:1px solid #dee2e6; font-size:.82rem; font-weight:700; }
</style>

<div class="alert alert-info mt-2 mb-0 d-flex align-items-center flex-wrap gap-2">
    <span><i class="ti ti-route-2 me-1"></i> 입장등록·음료 선택·앱 기능까지 <b>순서대로 한눈에</b> 보고 싶다면 핵심 요약을 먼저 보세요.</span>
    <a href="{{ route('admin.manual.summary') }}" class="btn btn-sm btn-primary ms-auto">핵심 요약 보기</a>
</div>
<div class="row g-3 mt-1">
    {{-- 목차 --}}
    <div class="col-lg-3 d-none d-lg-block">
        <div class="card mn-toc">
            <div class="card-body p-2">
                <div class="fw-bold px-2 py-1"><i class="ti ti-book me-1"></i> 목차</div>
                <a href="#s-overview">1. 서비스 한눈에 보기</a>
                <a href="#s-flow">2. 전체 순서도</a>
                <a href="#s-state">3. 행사 상태 전이</a>
                <a href="#s-rotation">4. 로테이션 규칙</a>
                <a href="#s-match">5. 투표·매칭 규칙</a>
                <a href="#s-screens">6. 화면별 사용법</a>
                <a href="#s-member">7. 회원 화면 확인 (대리 로그인)</a>
                <a href="#s-auto">8. 자동으로 일어나는 일</a>
                <a href="#s-checklist">9. 행사 당일 체크리스트</a>
                <a href="#s-faq">10. 자주 겪는 상황</a>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="d-flex flex-column gap-3">

            {{-- 1. 서비스 한눈에 보기 --}}
            <div class="card mn-sec" id="s-overview">
                <div class="card-body">
                    <h2><i class="ti ti-heart-handshake text-pink me-1"></i> 1. 서비스 한눈에 보기</h2>
                    <p class="mn-lead">로테이블은 <b>로테이션 소개팅</b>(와인파티, 1:1)을 기획부터 매칭 발표까지 웹에서 운영하는 서비스예요.</p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mn-cell h-100">
                                <b class="text-blue"><i class="ti ti-user-cog me-1"></i> 관리자(운영진)</b>
                                <ul class="mn-list mb-0">
                                    <li>행사 만들기, 테이블·좌석 배치</li>
                                    <li>현장 체크인과 번호표 발급</li>
                                    <li>로테이션 턴 진행, 투표 시작·마감</li>
                                    <li>매칭·메시지·요청 확인, 홍보물·안내 자료 제작</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mn-cell h-100">
                                <b class="text-pink"><i class="ti ti-users me-1"></i> 참가자(회원)</b>
                                <ul class="mn-list mb-0">
                                    <li>번호표로 <b>입장등록</b>(프로필 작성)</li>
                                    <li>턴마다 자리를 옮기며 대화 (이동 안내는 홈 화면)</li>
                                    <li>익명 메시지, 운영진에게 요청</li>
                                    <li>마음에 든 상대에게 <b>최대 3명</b> 비밀 투표 → 서로 선택하면 매칭</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="mn-sub"><i class="ti ti-layout-grid text-blue"></i> 행사 타입</div>
                    <ul class="mn-list">
                        <li><span class="badge bg-red-lt">와인파티</span> 테이블(3:3·4:4 등)에서 단체 대화. 투표 후 2차 자유 대화, 매칭 결과는 <b>다음 날 오전 9시</b>에 발표돼요.</li>
                        <li><span class="badge bg-purple-lt">1:1 소개팅</span> 테이블마다 남녀 1명씩 마주 앉아요. 별도 투표 시작 없이 <b>진행 중부터 매칭 선택이 열리고</b>, 종료하면 바로 결과를 봐요.</li>
                    </ul>
                </div>
            </div>

            {{-- 2. 전체 순서도 --}}
            <div class="card mn-sec" id="s-flow">
                <div class="card-body">
                    <h2><i class="ti ti-route text-blue me-1"></i> 2. 전체 순서도</h2>
                    <p class="mn-lead">행사 한 번의 흐름이에요. 왼쪽이 관리자가 하는 일, 오른쪽이 그때 참가자 화면에서 벌어지는 일이에요.</p>

                    <div class="mn-flow">
                        <div class="mn-head is-admin">🛠 관리자</div>
                        <div></div>
                        <div class="mn-head is-member">🙋 참가자</div>

                        <div class="mn-stage mn-p">대기 (P)</div>

                        <div class="mn-cell is-admin"><span class="mn-no">1</span><b>행사 생성</b><br>제목·타입·일시·테이블 수 입력. 상태는 항상 <b>대기</b>로 시작해요.<br><small class="text-secondary">행사 운영 &gt; 행사 목록</small></div>
                        <div class="mn-mid"><i class="ti ti-arrow-right"></i></div>
                        <div class="mn-cell is-empty"></div>

                        <div class="mn-cell is-admin"><span class="mn-no">2</span><b>홍보·안내 준비</b><br>상세페이지 제작, 초대 멘트, 안내 슬라이드(입장 QR), 채팅방 공지.<br><small class="text-secondary">홍보 / 안내 메뉴</small></div>
                        <div class="mn-mid"><i class="ti ti-arrow-right"></i></div>
                        <div class="mn-cell is-member">모집 글·공지를 보고 참여를 신청해요. (플랫폼은 문토/프립)</div>

                        <div class="mn-cell is-admin"><span class="mn-no">3</span><b>체크인 명단 준비 → 현장 체크인</b><br>닉네임·플랫폼·성별(+년생·키·직업·장점)을 명단에 넣고, 도착한 분을 체크인하며 <b>번호표와 테이블</b>을 정해요.<br><small class="text-secondary">행사 운영 &gt; 체크인</small></div>
                        <div class="mn-mid"><i class="ti ti-arrow-right"></i></div>
                        <div class="mn-cell is-member">번호표 카드를 받고 안내받은 테이블에 앉아요.</div>

                        <div class="mn-cell is-admin"><span class="mn-no">4</span><b>입장등록 현황 확인</b><br>체크인 카드에 <b>입장등록 대기 → 완료</b>가 표시돼요.</div>
                        <div class="mn-mid"><i class="ti ti-arrow-left"></i></div>
                        <div class="mn-cell is-member"><span class="mn-no" style="background:#d6336c;">A</span><b>입장등록</b><br>입장등록 QR로 접속 → 번호표·핸드폰·4자리 비밀번호·프로필 작성. <b>체크인된 번호표만</b> 등록할 수 있어요.</div>

                        <div class="mn-stage mn-s">진행 (S)</div>

                        <div class="mn-cell is-admin"><span class="mn-no">5</span><b>로테이션 진행</b><br>[로테이션 진행] 버튼을 누를 때마다 <b>턴 +1</b>. 첫 진행에서 상태가 대기 → 진행으로 바뀌어요.<br><small class="text-secondary">행사 운영 &gt; 로테이션 진행</small></div>
                        <div class="mn-mid"><i class="ti ti-arrow-right"></i></div>
                        <div class="mn-cell is-member">홈 화면의 <b>내 테이블·이번 턴 상대</b>가 실시간으로 바뀌어요. 안내에 따라 자리를 옮겨 대화해요. 메시지(최대 5건)·요청도 보낼 수 있어요.</div>

                        <div class="mn-stage mn-v">매칭중 (V) — 와인파티</div>

                        <div class="mn-cell is-admin"><span class="mn-no">6</span><b>매칭(투표) 시작</b><br>마지막 턴이 끝나면 [매칭 시작]. (1:1은 이 단계 없이 진행 중부터 선택 가능)</div>
                        <div class="mn-mid"><i class="ti ti-arrow-right"></i></div>
                        <div class="mn-cell is-member">'매칭하기' 탭이 열려요. 마음에 든 상대를 <b>최대 3명</b>까지 비밀로 선택하고, 마감 전까지 바꿀 수 있어요.</div>

                        <div class="mn-stage mn-x">종료 (X)</div>

                        <div class="mn-cell is-admin"><span class="mn-no">7</span><b>매칭 종료</b><br>수동으로 [매칭 종료]하거나, 와인파티는 <b>행사 다음 날 오전 9시</b>에 자동 마감돼요.</div>
                        <div class="mn-mid"><i class="ti ti-arrow-right"></i></div>
                        <div class="mn-cell is-member">결과 탭이 열려요. <b>서로 선택한 경우에만</b> 매칭 성사로 안내돼요. (1:1은 이후 '어필'도 이용)</div>

                        <div class="mn-cell is-admin"><span class="mn-no">8</span><b>결과 확인·정리</b><br>전체 매칭 / 매칭 결과(상호) / 메시지 / 요청 조회. 종료 요약이 운영 텔레그램으로 전송돼요(운영 환경만).</div>
                        <div class="mn-mid"></div>
                        <div class="mn-cell is-empty"></div>
                    </div>

                    <div class="mn-tip"><i class="ti ti-bulb me-1"></i> 순서는 지켜야 해요. <b>체크인(번호표) → 입장등록 → 로테이션 진행 → 매칭 시작 → 매칭 종료</b> 순으로만 흘러요. 실수했을 때는 로테이션 진행 화면의 <b>매칭 시작 취소 / 종료 되돌리기</b>로 한 단계 되돌릴 수 있어요.</div>
                </div>
            </div>

            {{-- 3. 상태 전이 --}}
            <div class="card mn-sec" id="s-state">
                <div class="card-body">
                    <h2><i class="ti ti-arrows-exchange text-purple me-1"></i> 3. 행사 상태 전이</h2>
                    <p class="mn-lead">행사에는 네 가지 상태가 있고, 참가자 화면에 보이는 메뉴도 상태에 따라 바뀌어요.</p>
                    <div class="mn-states">
                        <div class="mn-state mn-p"><b>대기 (P)</b><small>행사만 만들어진 상태. 체크인·입장등록·좌석 준비를 해요.</small></div>
                        <div class="mn-arrow"><i class="ti ti-arrow-narrow-right"></i>첫 [로테이션 진행]</div>
                        <div class="mn-state mn-s"><b>진행 (S)</b><small>턴이 진행 중. 메시지 전송 가능. 와인파티는 미니게임 탭이 열려요.</small></div>
                        <div class="mn-arrow"><i class="ti ti-arrow-narrow-right"></i>[매칭 시작]<br>(와인파티)</div>
                        <div class="mn-state mn-v"><b>매칭중 (V)</b><small>투표 가능. 와인파티의 '매칭하기' 탭이 열리고 미니게임은 닫혀요.</small></div>
                        <div class="mn-arrow"><i class="ti ti-arrow-narrow-right"></i>[매칭 종료]<br>또는 다음 날 9시</div>
                        <div class="mn-state mn-x"><b>종료 (X)</b><small>결과 공개. 투표 수정 불가.</small></div>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-vcenter card-table table-sm">
                            <thead><tr><th>전이</th><th>누가·언제</th><th>참가자 화면에서 일어나는 일</th></tr></thead>
                            <tbody>
                                <tr><td>대기 → 진행</td><td>관리자가 처음 로테이션 진행</td><td>홈의 내 테이블·상대가 실시간 갱신</td></tr>
                                <tr><td>진행 → 매칭중</td><td>관리자 [매칭 시작] (와인파티)</td><td>'매칭하기' 탭 열림, 게임 탭 닫힘</td></tr>
                                <tr><td>진행/매칭중 → 종료</td><td>관리자 [매칭 종료] 또는 자동 마감</td><td>결과 탭으로 즉시 갱신</td></tr>
                                <tr><td>되돌리기</td><td>관리자 [매칭 시작 취소] / [종료 되돌리기]</td><td>취소하면 <b>그 투표 데이터는 삭제</b>돼요. 신중히 누르세요.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mn-tip mn-ok"><i class="ti ti-info-circle me-1"></i> 1:1 소개팅은 <b>매칭중(V) 단계를 건너뛰어요</b>. 진행(S)에서 바로 매칭 선택이 열리고, 관리자가 종료(X)하면 닫혀요.</div>
                </div>
            </div>

            {{-- 4. 로테이션 규칙 --}}
            <div class="card mn-sec" id="s-rotation">
                <div class="card-body">
                    <h2><i class="ti ti-rotate text-teal me-1"></i> 4. 로테이션 규칙</h2>
                    <p class="mn-lead"><b>여자는 테이블에 고정, 남자는 턴마다 다음 테이블로 한 칸씩 이동</b>해요. 그래서 모든 남녀가 한 번씩 마주쳐요.</p>
                    <ul class="mn-list">
                        <li><b>턴은 숫자 하나</b>예요. 행사의 현재 턴(0 = 시작 전, 1, 2, 3…)이 [로테이션 진행]을 누를 때마다 1씩 올라가요.</li>
                        <li><b>좌석은 한 번만 저장</b>해요. 각 회원의 <b>기준 자리</b>(여자=고정석, 남자=1턴 출발석)만 저장하고, 턴별 자리는 현재 턴으로 <b>계산</b>해요. 그래서 중간 입장자도 현재 턴 자리가 자동으로 맞춰져요.</li>
                        <li>마지막 턴은 기본적으로 <b>테이블 수</b>예요(남자가 제자리로 돌아오는 시점). <b>로테이션 횟수</b>를 정하면 그 턴에서 끝나요. (0턴일 때만 변경 가능, 상한은 테이블 수)</li>
                        <li>좌석 편집(회원 추가·제거)은 <b>1턴(기준) 화면에서만</b> 가능해요. 2턴 이상에서 보이는 자리는 계산된 결과라서요.</li>
                    </ul>

                    <div class="mn-sub"><i class="ti ti-table text-teal"></i> 예시: 테이블 4개, 남자 A·B·C·D가 1턴에 1~4번 테이블에서 시작</div>
                    <div class="table-responsive">
                        <table class="mn-rot">
                            <thead><tr><th></th><th>1번 테이블</th><th>2번 테이블</th><th>3번 테이블</th><th>4번 테이블</th></tr></thead>
                            <tbody>
                                <tr><th>여자 (고정)</th><td class="is-f">가</td><td class="is-f">나</td><td class="is-f">다</td><td class="is-f">라</td></tr>
                                <tr><th>1턴 남자</th><td class="is-m">A</td><td class="is-m">B</td><td class="is-m">C</td><td class="is-m">D</td></tr>
                                <tr><th>2턴 남자</th><td class="is-m">D</td><td class="is-m">A</td><td class="is-m">B</td><td class="is-m">C</td></tr>
                                <tr><th>3턴 남자</th><td class="is-m">C</td><td class="is-m">D</td><td class="is-m">A</td><td class="is-m">B</td></tr>
                                <tr><th>4턴 남자 (마지막)</th><td class="is-m">B</td><td class="is-m">C</td><td class="is-m">D</td><td class="is-m">A</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-secondary small mt-1">남자 A는 1→2→3→4번 테이블로 한 칸씩 이동하며 여자 가·나·다·라를 차례로 만나요.</div>
                    <div class="mn-tip mn-warn"><i class="ti ti-alert-triangle me-1"></i> 입장등록 후 <b>성별을 바꾸면</b> 이동 방식(여자 고정 / 남자 이동)이 달라져 <b>현재 자리가 바뀌어요</b>. 로테이션이 시작된 뒤에는 특히 조심하세요. 투표·메시지 기록이 있으면 성별은 바꿀 수 없어요.</div>
                    <div class="mn-tip"><i class="ti ti-bulb me-1"></i> 1:1 소개팅은 <b>테이블마다 같은 성별 1명</b>만 앉을 수 있어요. 체크인 화면에서 이미 찬 테이블은 목록에서 숨겨져요. 와인파티는 성비가 고르도록 자리가 미리 선택돼요.</div>
                </div>
            </div>

            {{-- 5. 투표·매칭 --}}
            <div class="card mn-sec" id="s-match">
                <div class="card-body">
                    <h2><i class="ti ti-heart text-pink me-1"></i> 5. 투표·매칭 규칙</h2>
                    <p class="mn-lead">매칭은 <b>서로 선택했을 때만</b> 성사돼요. 한쪽만 선택한 건 매칭이 아니에요.</p>
                    <div class="row g-2">
                        <div class="col-md-4"><div class="mn-cell h-100"><b>최대 3명</b><br>한 명이 비밀로 고를 수 있는 사람 수예요. 마감 전에는 자유롭게 수정해요.</div></div>
                        <div class="col-md-4"><div class="mn-cell h-100"><b>상호 선택 = 매칭</b><br>A가 B를, B가 A를 골랐을 때만 성사. 결과 화면은 남자 기준으로 한 번씩만 보여요.</div></div>
                        <div class="col-md-4"><div class="mn-cell h-100"><b>선택 가능한 시간</b><br>와인파티: 매칭중(V)이면서 다음 날 9시 전. 1:1: 진행(S)·매칭중 동안 종료 전까지.</div></div>
                    </div>
                    <div class="mn-sub"><i class="ti ti-messages text-blue"></i> 메시지와 요청</div>
                    <ul class="mn-list">
                        <li><b>익명 메시지</b>: 같은 행사의 이성에게 보낼 수 있고 1인 최대 5건이에요. 진행·매칭중 상태에서만 보낼 수 있어요.</li>
                        <li><b>관리자 메시지</b>: 메시지 조회 모달의 [관리자 메시지 보내기]로 전체/남/여/개별 참가자에게 보내요. 참가자에게는 '관리자'로 표시돼요.</li>
                        <li><b>요청</b>: 참가자가 운영진에게 보내는 요청. 자리 번호와 함께 관리자 화면에 실시간으로 떠요.</li>
                        <li><b>1:1 어필</b>: 매칭 종료 후, 이어지지 않은 상대에게 번호표가 보이는 메시지를 한 번 보낼 수 있어요. 종료 전에는 화면이 잠겨 있고 관리자 메시지도 표시되지 않아요.</li>
                    </ul>
                </div>
            </div>

            {{-- 6. 화면별 사용법 --}}
            <div class="card mn-sec" id="s-screens">
                <div class="card-body">
                    <h2><i class="ti ti-layout-dashboard text-blue me-1"></i> 6. 화면별 사용법</h2>
                    <p class="mn-lead">메뉴 순서대로 정리했어요. 화면 위쪽의 <b>행사 탭바</b>(행사 상세·체크인·로테이션 진행)에서 행사를 바꾸고 입장등록 QR을 볼 수 있어요.</p>

                    <div class="accordion" id="mnAccordion">
                        @php
                            $screens = [
                                [
                                    'id'    => 'ev',
                                    'icon'  => 'calendar-event',
                                    'title' => '행사 운영 › 행사 목록 / 행사 상세',
                                    'items' => [
                                        '<b>행사 생성</b>: 제목·타입·일시·테이블 수를 입력해요. 테이블은 상세에서 추가·삭제할 수 있어요.',
                                        '<b>참여자 목록</b>: 남/여를 나눠 두 칸(PC는 좌우, 모바일은 위아래)으로 보여줘요. 위쪽 탭으로 <b>전체 / 남 / 여</b>만 볼 수 있어요.',
                                        '<b>정렬</b>: 번호표 순, 받은·보낸 투표 많은/적은 순, <b>최근 접속 순 / 오래된 접속 순</b>. 남/여 칸마다 따로 정렬돼요.',
                                        '<b>카드 정보</b>: 번호표·닉네임(플랫폼)·프로필·핸드폰·<b>마지막 로그인</b>(최고관리자는 비밀번호도), 보낸 투표/받은 투표/메시지/요청 숫자(누르면 목록).',
                                        '<b>상세보기 · 수정</b>: 회원 프로필 전체를 보고 고쳐요. 번호표·성별을 바꾸면 체크인 명단도 함께 바뀌어요. 이미 다른 사람이 쓰는 번호는 쓸 수 없어요.',
                                        '<b>회원으로 로그인</b>: 그 회원 화면을 새 탭에서 그대로 확인해요. (7장 참고)',
                                        '결과 버튼: <b>전체 매칭 / 매칭 결과 / 메시지 / 요청</b>. 1:1은 <b>음료 선택 현황</b>도 여기서 봐요.',
                                    ],
                                ],
                                [
                                    'id'    => 'ck',
                                    'icon'  => 'user-check',
                                    'title' => '행사 운영 › 체크인 (현장용)',
                                    'items' => [
                                        '<b>명단 추가</b>: 닉네임·플랫폼·성별·년생·키·직업·장점을 넣어요. 상세페이지 참여자 명단에도 쓰여요.',
                                        '<b>체크인</b>: 카드나 [체크인] 버튼을 누르면 번호표·테이블 창이 떠요. 번호를 입력하면 그 번호, <b>비우면 그 성별의 가장 작은 빈 번호</b>가 자동 배정돼요.',
                                        '카드 상태: <b>미도착 → 입장등록 대기(체크인 완료) → 입장등록 완료</b>.',
                                        '<b>입장등록 완료</b>자는 카드를 눌러도 동작하지 않아요(체크인 취소·번호 변경 불가). 대신 <b>회원 상세보기</b>와 <b>회원으로 로그인</b> 버튼이 나와요.',
                                        '남/여 명단 머리글을 누르면 <b>접기/펼치기</b>가 돼요. 접은 상태는 기억돼요.',
                                        '<b>참가자 명단 이미지</b>: 상세페이지의 참가자 명단 장을 바로 이미지로 받아요. 1:1은 <b>음료 선택지</b> 설정도 여기에 있어요.',
                                    ],
                                ],
                                [
                                    'id'    => 'se',
                                    'icon'  => 'armchair',
                                    'title' => '행사 운영 › 로테이션 진행',
                                    'items' => [
                                        '<b>좌석 배치</b>: 테이블 카드에서 회원을 추가/제거해요. 넣는 즉시 저장되고, <b>1턴(기준) 화면에서만</b> 편집돼요.',
                                        '각 참여자 칸의 아이콘: <b>상세(카드 모양)</b>는 회원 상세 모달, <b>로그인</b>은 회원 화면 확인, <b>x</b>는 자리에서 빼기.',
                                        '<b>턴 이동</b>: 보고 있는 턴을 바꿔 과거 자리를 확인해요. <b>[로테이션 진행]</b>은 최신 턴에서만 눌러요. <b>로테이션 횟수</b>는 0턴일 때만 정해요.',
                                        '<b>상태 버튼</b>: 매칭 시작(와인) → 매칭 종료. 되돌리기: 매칭 시작 취소(투표 삭제), 종료 되돌리기, 로테이션 되돌리기.',
                                        '상단 결과 버튼에서 <b>메시지 조회</b>를 열면 <b>보낸 사람(남/여/관리자)</b>·<b>읽음 여부</b> 필터로 걸러 한 줄씩 읽을 수 있어요.',
                                    ],
                                ],
                                [
                                    'id'    => 'pr',
                                    'icon'  => 'sparkles',
                                    'title' => '홍보 › 상세페이지 제작 / 초대 멘트 / QR코드 설정',
                                    'items' => [
                                        '<b>상세페이지 제작</b>: 유형(와인/1:1)을 고르고 샘플을 불러와 꾸민 뒤 <b>이미지로 다운로드</b>해요. 체크인 명단으로 참여자 명단을 자동으로 채울 수 있고, 저장 목록에 이름을 붙여 보관해요.',
                                        '<b>초대 멘트</b>: 유형·성별·무료 여부에 맞는 초대 문구를 복사해요.',
                                        '<b>QR코드 설정</b>: 대상 URL을 등록하면 공개 주소 <span class="mn-kbd">/qr/{코드}</span>가 발급돼요. 주소를 바꿔도 이미 뿌린 QR은 그대로 쓸 수 있어요.',
                                    ],
                                ],
                                [
                                    'id'    => 'gd',
                                    'icon'  => 'presentation',
                                    'title' => '안내 › 안내 슬라이드 / 채팅방 공지 / 발표 멘트',
                                    'items' => [
                                        '<b>안내 슬라이드</b>: 현장 화면에 띄우는 이용안내입니다. 표지의 <b>입장등록 QR이 선택한 행사의 입장등록 주소로 자동 생성</b>돼요. 위쪽 <b>입장 QR 행사</b>에서 행사를 바꾸세요.',
                                        '공개 슬라이드 주소는 <span class="mn-kbd">/public/guide/wine?eventId=번호</span> 형식이에요. 번호를 생략하면 지금 운영 중인 행사 QR이 나와요.',
                                        '<b>채팅방 공지</b>: 시작 시간·타입에 맞춘 공지 문구를 만들어 복사해요. <b>발표 멘트 편집</b>은 호스트 대본을 편집·저장해요.',
                                    ],
                                ],
                                [
                                    'id'    => 'pb',
                                    'icon'  => 'external-link',
                                    'title' => '공개 페이지 / 분석·도구',
                                    'items' => [
                                        '<b>공개 페이지</b>: 공개 미니게임·안내 슬라이드·호스트 멘트 링크를 한 화면에 모았어요. 새 탭으로 열려요.',
                                        '<b>만남 이력</b>: 전화번호 기준으로 같은 행사에 함께한 상대를 조회해요. 번호 1개는 기본 조회, 2개는 두 사람의 관계(상호 매칭·메시지 수·프로필 비교) 상세 조회예요.',
                                        '<b>손익분기 계산기 / N빵 계산기</b>: 운영 비용 계산 도구예요.',
                                    ],
                                ],
                                [
                                    'id'    => 'ad',
                                    'icon'  => 'user-cog',
                                    'title' => '관리자 / 설정',
                                    'items' => [
                                        '<b>관리자 목록·권한 관리</b>: 관리자 계정과 역할별 접근 가능 페이지를 관리해요. 새로 생긴 페이지는 기본적으로 모두 열려 있고, 막고 싶을 때만 페이지 권한에서 금지해요.',
                                        '<b>텔레그램 알림 ON/OFF</b>는 상단 메뉴에서 최고관리자만 바꿀 수 있어요. 매칭 종료 요약은 <b>운영(production) 환경에서만</b> 전송돼요.',
                                    ],
                                ],
                            ];
                        @endphp
                        @foreach ($screens as $sc)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#mn-{{ $sc['id'] }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                        <i class="ti ti-{{ $sc['icon'] }} me-2"></i>{{ $sc['title'] }}
                                    </button>
                                </h2>
                                <div id="mn-{{ $sc['id'] }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#mnAccordion">
                                    <div class="accordion-body">
                                        <ul class="mn-list mb-0">
                                            @foreach ($sc['items'] as $it)
                                                <li>{!! $it !!}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 7. 대리 로그인 --}}
            <div class="card mn-sec" id="s-member">
                <div class="card-body">
                    <h2><i class="ti ti-login-2 text-orange me-1"></i> 7. 회원 화면 확인 (회원으로 로그인)</h2>
                    <p class="mn-lead">참가자가 어떤 화면을 보는지, 문의한 문제가 실제로 보이는지 확인할 때 써요.</p>
                    <ul class="mn-list">
                        <li><b>위치</b>: 행사 상세 참여자 카드, 체크인(입장등록 완료자), 로테이션 진행 참여자 칸.</li>
                        <li><b>동작</b>: 새 탭에서 그 회원의 홈이 열려요. 같은 브라우저에 다른 회원이 로그인돼 있어도 <b>덮어써요</b>. 관리자 로그인은 그대로 유지돼요.</li>
                        <li><b>표시</b>: 회원 화면 위쪽에 노란 <b>"관리자 대리 로그인 중"</b> 표시가 떠요.</li>
                    </ul>
                    <div class="mn-tip mn-ok"><i class="ti ti-shield-check me-1"></i> <b>기록을 남기지 않아요</b>: 마지막 로그인 시각이 갱신되지 않고, 받은 <b>메시지·요청 답장이 읽음 처리되지 않아요</b>. 하단 탭의 안 읽은 숫자도 그대로 유지돼요. 그래서 실제 참가자에게 영향이 없어요.</div>
                    <div class="mn-tip mn-warn"><i class="ti ti-alert-triangle me-1"></i> 이 화면에서 <b>투표·메시지 전송·요청·프로필 수정</b>은 실제로 그 회원의 이름으로 저장돼요. 확인만 하고 눌러보지 마세요. 끝나면 회원이 직접 로그인하거나 로그아웃할 때 대리 로그인 표시가 사라져요.</div>
                </div>
            </div>

            {{-- 8. 자동 --}}
            <div class="card mn-sec" id="s-auto">
                <div class="card-body">
                    <h2><i class="ti ti-clock-play text-azure me-1"></i> 8. 자동으로 일어나는 일</h2>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-sm">
                            <thead><tr><th style="width:30%">언제</th><th>무슨 일</th></tr></thead>
                            <tbody>
                                <tr><td>매일 오전 9시</td><td>"행사 다음 날 오전 9시"가 지난 <b>매칭중(V)</b> 행사를 자동으로 종료(X)해요. 종료 요약 텔레그램은 운영 환경에서만 가요.</td></tr>
                                <tr><td>매일 자정</td><td>방문 통계를 스냅샷으로 저장해요.</td></tr>
                                <tr><td>턴 진행·매칭 시작/종료</td><td>참가자 화면이 <b>실시간</b>으로 바뀌어요. (연결이 끊겼다 돌아오면 자동으로 최신 상태로 다시 불러와요)</td></tr>
                                <tr><td>메시지·요청 발생</td><td>관리자 화면에 실시간으로 떠요. 메시지 조회 모달의 '실시간 연결됨' 배지로 연결 상태를 알 수 있어요.</td></tr>
                                <tr><td>입장등록 완료</td><td>체크인 명단과 번호표(성별+번호)로 연결돼 <b>입장등록 완료</b>로 표시돼요.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 9. 체크리스트 --}}
            <div class="card mn-sec" id="s-checklist">
                <div class="card-body">
                    <h2><i class="ti ti-list-check text-green me-1"></i> 9. 행사 당일 체크리스트</h2>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="mn-cell h-100">
                                <b>🕐 행사 전</b>
                                <ul class="mn-list mb-0">
                                    <li>행사·테이블 수 확인</li>
                                    <li>체크인 명단 입력(프로필 포함)</li>
                                    <li>안내 슬라이드의 <b>입장 QR 행사</b> 확인</li>
                                    <li>채팅방 공지 발송</li>
                                    <li>1:1이면 음료 선택지 설정</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mn-cell h-100">
                                <b>🎫 입장 시</b>
                                <ul class="mn-list mb-0">
                                    <li>체크인 → 번호표·테이블 안내</li>
                                    <li>입장등록 QR 안내</li>
                                    <li>대기 → 완료로 바뀌는지 확인</li>
                                    <li>로테이션 진행 화면에서 좌석 확인(1턴)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mn-cell h-100">
                                <b>🔄 진행·마무리</b>
                                <ul class="mn-list mb-0">
                                    <li>[로테이션 진행]을 턴마다 누르기</li>
                                    <li>마지막 턴 후 [매칭 시작] (와인)</li>
                                    <li>투표 마감 → 결과·메시지·요청 확인</li>
                                    <li>자동 마감 전(다음 날 9시)에 필요하면 수동 종료</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 10. FAQ --}}
            <div class="card mn-sec" id="s-faq">
                <div class="card-body">
                    <h2><i class="ti ti-help-circle text-red me-1"></i> 10. 자주 겪는 상황</h2>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-sm">
                            <thead><tr><th style="width:32%">이럴 때</th><th>이렇게 해요</th></tr></thead>
                            <tbody>
                                <tr><td>참가자가 입장등록이 안 돼요</td><td>체크인 명단에 그 번호표(성별+번호)가 있는지 확인해요. <b>체크인되지 않은 번호는 거부</b>돼요.</td></tr>
                                <tr><td>번호표를 잘못 줬어요</td><td>입장등록 <b>전</b>이면 체크인 화면의 [번호·테이블]에서 바꿔요. 입장등록 <b>후</b>면 행사 상세의 [상세보기 · 수정]에서 번호표를 바꿔요. (명단도 같이 바뀌어요)</td></tr>
                                <tr><td>자리가 이상하게 보여요</td><td>현재 턴과 기준 자리로 계산되므로, 로테이션 진행에서 <b>1턴(기준)</b>의 좌석을 먼저 점검해요. 성별이 잘못 등록됐는지도 확인해요.</td></tr>
                                <tr><td>투표를 잘못 시작했어요</td><td>[매칭 시작 취소]로 진행 상태로 돌아가요. <b>이미 들어온 투표는 삭제</b>되니 참가자에게 알려요.</td></tr>
                                <tr><td>종료를 잘못 눌렀어요</td><td>[종료 되돌리기]로 직전 상태(와인=매칭중, 1:1=진행)로 돌려요.</td></tr>
                                <tr><td>참가자 화면을 직접 봐야 해요</td><td>[회원으로 로그인]으로 확인해요. 읽음·로그인 기록은 남지 않아요.</td></tr>
                                <tr><td>매칭 종료 알림이 텔레그램에 안 와요</td><td>운영(production) 환경에서만 가요. 환경값과 텔레그램 ON/OFF를 확인해요.</td></tr>
                                <tr><td>로테이션이 시작된 뒤 늦게 온 참가자가 있어요</td><td>체크인에서 번호표와 <b>테이블을 지정</b>해 주세요. 입장등록을 마치면 현재 턴 자리가 자동으로 맞춰져요. 직접 넣으려면 로테이션 진행의 <b>1턴 화면</b>에서 그 테이블에 추가해요.</td></tr>
                                <tr><td>인원이 적어 테이블을 합치고 싶어요 (예: 4번 → 3번)</td><td><b>1턴 화면</b>에서 4번 테이블 사람들을 3번으로 옮기고, 비게 된 4번 테이블은 행사 상세에서 <b>삭제</b>해요. 삭제하지 않으면 다음 턴에 남자가 빈 테이블로 이동해요. 1턴에서 바꾼 자리는 이후 모든 턴에 자동 반영돼요.</td></tr>
                                <tr><td>로테이션을 한 번 더 눌렀어요</td><td>로테이션 진행 화면의 <b>[로테이션 되돌리기]</b>로 바로 이전 턴으로 돌아가요. 여러 번 눌렀다면 그만큼 반복해요. <b>매칭을 시작·종료한 뒤에는</b> 이 방법으로 되돌릴 수 없어요.</td></tr>
                                <tr><td>자리를 바꿔야 하는데 2턴 이상 화면이에요</td><td>2턴 이상 화면은 <b>보기 전용</b>이에요. 자리 편집은 꼭 <b>1턴 화면</b>에서 해요.</td></tr>
                                <tr><td>위 방법으로 해결되지 않아요</td><td>임의로 조작하지 말고 운영/개발 담당자에게 문의해요.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
