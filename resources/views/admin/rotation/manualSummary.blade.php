@extends('layouts.app')

@section('title', '핵심 요약')

@section('content')
{{--
    핵심 요약 — 행사 한 번의 순서도. 글을 줄이고 단계별로 "관리자 / 참가자 앱 / 시스템"만 짧게 보여준다.
    자세한 화면별 사용법·규칙은 전체 사용 설명서(admin.manual)에 있다.
--}}
<style>
    .sm-toc{ position:sticky; top:84px; }
    .sm-toc a{ display:flex; align-items:center; gap:.5rem; padding:.45rem .6rem; border-radius:8px; color:#374151; text-decoration:none; font-weight:600; font-size:.9rem; }
    .sm-toc a:hover{ background:#f1f5f9; }
    .sm-toc .sm-dot{ flex:0 0 auto; width:1.4rem; height:1.4rem; border-radius:50%; color:#fff; font-size:.75rem; font-weight:800; display:flex; align-items:center; justify-content:center; }
    .sm-sec{ scroll-margin-top:90px; }

    /* 단계 카드 */
    .sm-step{ border:1px solid #e6e8eb; border-radius:18px; background:#fff; overflow:hidden; box-shadow:0 1px 4px rgba(20,30,60,.05); }
    .sm-head{ display:flex; align-items:center; gap:.7rem; padding:.85rem 1.1rem; color:#fff; flex-wrap:wrap; }
    .sm-no{ width:2.2rem; height:2.2rem; border-radius:50%; background:rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.05rem; flex:0 0 auto; }
    .sm-title{ font-weight:800; font-size:1.2rem; }
    .sm-state{ margin-left:auto; font-size:.8rem; font-weight:800; background:rgba(255,255,255,.22); border-radius:999px; padding:.2rem .75rem; white-space:nowrap; }
    .sm-body{ display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.75rem; padding:1rem; }
    .sm-col{ border-radius:14px; padding:.8rem .95rem; font-size:.95rem; line-height:1.75; }
    .sm-col.is-admin{ background:#eef5ff; } .sm-col.is-app{ background:#fff0f5; } .sm-col.is-sys{ background:#ecfaf6; }
    .sm-col h4{ font-size:.9rem; font-weight:800; margin:0 0 .45rem; display:flex; align-items:center; gap:.35rem; }
    .sm-col.is-admin h4{ color:#1c5fb0; } .sm-col.is-app h4{ color:#c2255c; } .sm-col.is-sys h4{ color:#0b7285; }
    .sm-col ul{ margin:0; padding-left:1.1rem; }
    .sm-col li{ margin-bottom:.35rem; }
    .sm-col li:last-child{ margin-bottom:0; }
    .sm-col .sm-empty{ color:#9ca3af; }
    .sm-note{ margin:0 1rem 1rem; padding:.65rem .9rem; background:#fff9e6; border-left:4px solid #f0b400; border-radius:8px; font-size:.92rem; line-height:1.65; }
    .sm-note.is-warn{ background:#fff5f5; border-left-color:#e03131; }
    .sm-arrow{ text-align:center; color:#adb5bd; font-size:1.3rem; line-height:1; padding:.35rem 0; }
    @media (max-width: 991.98px){ .sm-body{ grid-template-columns:minmax(0, 1fr); } }

    .bg-sm-0{ background:#495057; } .bg-sm-p{ background:#868e96; } .bg-sm-s{ background:#206bc4; } .bg-sm-v{ background:#ae3ec9; } .bg-sm-x{ background:#343a40; } .bg-sm-d{ background:#e8590c; } .bg-sm-a{ background:#d6336c; }

    /* 입장등록 항목 묶음 */
    .sm-groups{ display:flex; flex-direction:column; gap:.4rem; }
    .sm-group{ display:flex; gap:.5rem; align-items:baseline; }
    .sm-group b{ flex:0 0 3.6rem; font-size:.8rem; color:#c2255c; }
    .sm-chip{ display:inline-block; border-radius:999px; padding:.05rem .6rem; font-size:.82rem; font-weight:700; margin:.1rem .15rem .1rem 0; background:#fff; border:1px solid #f3c1d3; color:#a61e4d; }
    .sm-chip.is-auto{ background:#d3f9d8; border-color:#b2f2bb; color:#2b8a3e; }
    .sm-chip.is-state{ background:#fff; border-color:#ced4da; color:#495057; }

    /* 음료 흐름 */
    .sm-flow4{ display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:.6rem; }
    .sm-flow4 .sm-box{ border-radius:14px; padding:.8rem .9rem; background:#fff4e6; font-size:.93rem; line-height:1.65; position:relative; }
    .sm-flow4 .sm-box b{ display:block; color:#d9480f; margin-bottom:.15rem; }
    .sm-flow4 .sm-box + .sm-box::before{ content:'›'; position:absolute; left:-.55rem; top:50%; transform:translateY(-50%); color:#adb5bd; font-size:1.4rem; font-weight:800; }
    @media (max-width: 767.98px){
        .sm-flow4{ grid-template-columns:minmax(0, 1fr); }
        .sm-flow4 .sm-box + .sm-box::before{ content:'⌄'; left:50%; top:-.85rem; transform:translateX(-50%); }
    }

    /* 앱 탭 */
    .sm-tabs{ display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:.6rem; }
    .sm-tab{ border-radius:14px; padding:.75rem .9rem; background:#f8f9fa; font-size:.93rem; line-height:1.6; }
    .sm-tab .sm-ico{ font-size:1.5rem; line-height:1; display:block; margin-bottom:.2rem; }
    .sm-tab b{ display:block; font-size:1rem; }
    .sm-tab small{ color:#6b7280; }
</style>

<div class="row g-3 mt-1">
    {{-- 목차 --}}
    <div class="col-lg-3 d-none d-lg-block">
        <div class="card sm-toc">
            <div class="card-body p-2">
                <div class="fw-bold px-2 py-1"><i class="ti ti-route-2 me-1"></i> 순서도 바로가기</div>
                <a href="#s0"><span class="sm-dot bg-sm-0">0</span>사전 준비</a>
                <a href="#s1"><span class="sm-dot bg-sm-p">1</span>현장 체크인</a>
                <a href="#s2"><span class="sm-dot bg-sm-a">2</span>입장등록</a>
                <a href="#s3"><span class="sm-dot bg-sm-p">3</span>다시 들어오기</a>
                <a href="#s4"><span class="sm-dot bg-sm-s">4</span>로테이션 진행</a>
                <a href="#s5"><span class="sm-dot bg-sm-v">5</span>매칭(투표)</a>
                <a href="#s6"><span class="sm-dot bg-sm-x">6</span>종료·발표</a>
                <hr class="my-2">
                <a href="#drink"><span class="sm-dot bg-sm-d"><i class="ti ti-glass-full" style="font-size:.8rem;"></i></span>1:1 음료 흐름</a>
                <a href="#tabs"><span class="sm-dot bg-sm-a"><i class="ti ti-device-mobile" style="font-size:.8rem;"></i></span>참가자 앱 탭</a>
                <hr class="my-2">
                <a href="{{ route('admin.manual') }}"><span class="sm-dot" style="background:#adb5bd;"><i class="ti ti-book" style="font-size:.8rem;"></i></span>전체 사용 설명서</a>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
            <h2 class="mb-0 fw-bold">행사 한 번의 순서도</h2>
            <a href="{{ route('admin.manual') }}" class="btn btn-sm btn-outline-secondary ms-auto d-lg-none"><i class="ti ti-book me-1"></i> 전체 설명서</a>
        </div>
        <div class="text-muted mb-3">
            <span class="badge bg-blue-lt">관리자</span>
            <span class="badge bg-pink-lt">참가자 앱</span>
            <span class="badge bg-teal-lt">시스템</span>
            세 가지로 나눠서 단계마다 핵심만 보여드려요.
        </div>

        {{-- 0 --}}
        <div class="sm-step sm-sec" id="s0">
            <div class="sm-head bg-sm-0"><span class="sm-no">0</span><span class="sm-title">사전 준비</span><span class="sm-state">대기(P)</span></div>
            <div class="sm-body">
                <div class="sm-col is-admin">
                    <h4><i class="ti ti-user-cog"></i> 관리자</h4>
                    <ul>
                        <li>행사 만들기 <small class="text-muted">(타입 · 일시 · 테이블 수)</small></li>
                        <li>체크인 명단 입력 <small class="text-muted">(닉네임 · 플랫폼 · 성별 · 년생 · 키 · 직업 · 장점)</small></li>
                        <li>1:1이면 <b>음료 목록</b> 등록</li>
                    </ul>
                </div>
                <div class="sm-col is-app">
                    <h4><i class="ti ti-device-mobile"></i> 참가자 앱</h4>
                    <div class="sm-empty">아직 없음 (모집 글로 신청만 한 상태)</div>
                </div>
                <div class="sm-col is-sys">
                    <h4><i class="ti ti-settings"></i> 시스템</h4>
                    <ul>
                        <li>행사는 항상 <b>대기</b>로 시작</li>
                        <li>테이블이 함께 만들어짐</li>
                    </ul>
                </div>
            </div>
            <div class="sm-note is-warn"><b>년생 · 키 · 직업</b>은 꼭 명단에 있어야 해요. 입장등록 때 참가자가 쓰지 않고 이 값을 가져가요.</div>
        </div>
        <div class="sm-arrow">▼</div>

        {{-- 1 --}}
        <div class="sm-step sm-sec" id="s1">
            <div class="sm-head bg-sm-p"><span class="sm-no">1</span><span class="sm-title">현장 체크인</span><span class="sm-state">대기(P)</span></div>
            <div class="sm-body">
                <div class="sm-col is-admin">
                    <h4><i class="ti ti-user-cog"></i> 관리자</h4>
                    <ul>
                        <li>카드를 눌러 <b>번호표</b>와 <b>테이블</b> 정하기</li>
                        <li>번호를 비우면 <b>자동 배정</b></li>
                        <li>번호표 카드를 건네고 자리 안내</li>
                    </ul>
                </div>
                <div class="sm-col is-app">
                    <h4><i class="ti ti-device-mobile"></i> 참가자 앱</h4>
                    <ul>
                        <li>번호표를 받고 테이블에 앉기</li>
                        <li>입장등록 QR 안내받기</li>
                    </ul>
                </div>
                <div class="sm-col is-sys">
                    <h4><i class="ti ti-settings"></i> 시스템</h4>
                    <ul>
                        <li>번호는 같은 성별끼리만 안 겹침</li>
                        <li>상태 <span class="sm-chip is-state">미도착</span> → <span class="sm-chip is-state">입장등록 대기</span></li>
                    </ul>
                </div>
            </div>
            <div class="sm-note">1:1은 <b>테이블마다 같은 성별 1명</b>만 앉을 수 있어요. 이미 찬 테이블은 목록에서 숨겨져요.</div>
        </div>
        <div class="sm-arrow">▼</div>

        {{-- 2 --}}
        <div class="sm-step sm-sec" id="s2">
            <div class="sm-head bg-sm-a"><span class="sm-no">2</span><span class="sm-title">입장등록 (참가자 앱)</span><span class="sm-state">대기 · 진행 · 매칭중</span></div>
            <div class="sm-body">
                <div class="sm-col is-admin">
                    <h4><i class="ti ti-user-cog"></i> 관리자</h4>
                    <ul>
                        <li>체크인 카드가 <span class="sm-chip is-state">대기</span> → <span class="sm-chip is-auto">완료</span>로 바뀌는지 확인</li>
                        <li>완료된 카드에서 <b>회원 상세보기</b> · <b>회원으로 로그인</b></li>
                    </ul>
                </div>
                <div class="sm-col is-app">
                    <h4><i class="ti ti-device-mobile"></i> 직접 입력</h4>
                    <div class="sm-groups">
                        <div class="sm-group"><b>기본</b><span><span class="sm-chip">성별</span><span class="sm-chip">번호표</span><span class="sm-chip">핸드폰</span><span class="sm-chip">비밀번호 4자리</span></span></div>
                        <div class="sm-group"><b>프로필</b><span><span class="sm-chip">지역</span><span class="sm-chip">MBTI</span><span class="sm-chip">종교</span></span></div>
                        <div class="sm-group"><b>생활</b><span><span class="sm-chip">주량</span><span class="sm-chip">음주 횟수</span><span class="sm-chip">친구 약속</span><span class="sm-chip">애완동물</span><span class="sm-chip">흡연</span></span></div>
                        <div class="sm-group"><b>이상형</b><span><span class="sm-chip">이상형</span><span class="sm-chip">외적 조건</span><span class="sm-chip">내적 조건</span></span></div>
                        <div class="sm-group"><b>소개</b><span><span class="sm-chip">장점</span><span class="sm-chip">취미</span><span class="sm-chip">TMI</span></span></div>
                        <div class="sm-group"><b>1:1만</b><span><span class="sm-chip" style="border-color:#ffc078;color:#d9480f;">🥤 음료 선택</span></span></div>
                    </div>
                </div>
                <div class="sm-col is-sys">
                    <h4><i class="ti ti-settings"></i> 자동으로 채움</h4>
                    <div class="mb-1">
                        <span class="sm-chip is-auto">년생 · 키 · 직업</span>
                        <span class="sm-chip is-auto">플랫폼</span>
                        <span class="sm-chip is-auto">앉은 테이블</span>
                    </div>
                    <div class="small text-muted mb-2">← 모두 체크인 명단에서</div>
                    <ul>
                        <li>끝나면 <b>자동 로그인</b> → 홈</li>
                        <li>핸드폰은 행사 안에서 <b>한 번만</b></li>
                    </ul>
                </div>
            </div>
            <div class="sm-note">QR 주소는 <code>/register/행사번호</code> 예요. 대기 · 진행 · 매칭중 행사만 등록할 수 있고, 종료된 행사나 잘못된 QR은 안내 문구가 나와요. 음료가 등록된 1:1은 <b>음료 선택이 필수</b>예요.</div>
        </div>
        <div class="sm-arrow">▼</div>

        {{-- 3 --}}
        <div class="sm-step sm-sec" id="s3">
            <div class="sm-head bg-sm-p"><span class="sm-no">3</span><span class="sm-title">다시 들어오기 (로그인)</span><span class="sm-state">언제든</span></div>
            <div class="sm-body">
                <div class="sm-col is-admin">
                    <h4><i class="ti ti-user-cog"></i> 관리자</h4>
                    <ul>
                        <li>참가자 화면이 궁금하면 <b>회원으로 로그인</b></li>
                        <li>비밀번호는 최고관리자만 확인 · 변경</li>
                    </ul>
                </div>
                <div class="sm-col is-app">
                    <h4><i class="ti ti-device-mobile"></i> 참가자 앱</h4>
                    <ul>
                        <li><b>회차 + 핸드폰 + 비밀번호</b>로 입장</li>
                    </ul>
                </div>
                <div class="sm-col is-sys">
                    <h4><i class="ti ti-settings"></i> 시스템</h4>
                    <ul>
                        <li>직접 입장한 시각이 <b>마지막 로그인</b>으로 기록</li>
                        <li>관리자 대리 로그인은 기록 안 함</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="sm-arrow">▼</div>

        {{-- 4 --}}
        <div class="sm-step sm-sec" id="s4">
            <div class="sm-head bg-sm-s"><span class="sm-no">4</span><span class="sm-title">로테이션 진행</span><span class="sm-state">진행(S)</span></div>
            <div class="sm-body">
                <div class="sm-col is-admin">
                    <h4><i class="ti ti-user-cog"></i> 관리자</h4>
                    <ul>
                        <li>1턴 화면에서 <b>좌석 점검</b></li>
                        <li><b>[로테이션 진행]</b> 누를 때마다 턴 +1</li>
                        <li>1:1이면 음료 <b>전달 완료</b> 체크</li>
                    </ul>
                </div>
                <div class="sm-col is-app">
                    <h4><i class="ti ti-device-mobile"></i> 참가자 앱</h4>
                    <ul>
                        <li>홈에서 <b>내 테이블 · 이번 상대</b> 확인</li>
                        <li>익명 메시지 · 운영진 요청</li>
                        <li>와인: 게임 / 1:1: 질문카드</li>
                    </ul>
                </div>
                <div class="sm-col is-sys">
                    <h4><i class="ti ti-settings"></i> 시스템</h4>
                    <ul>
                        <li><b>여자 고정, 남자 한 칸씩 이동</b></li>
                        <li>턴이 바뀌면 앱이 <b>실시간</b> 갱신</li>
                    </ul>
                </div>
            </div>
            <div class="sm-note">자리 편집은 <b>1턴 화면</b>에서만 돼요. 2턴 이상은 보기 전용이에요.</div>
        </div>
        <div class="sm-arrow">▼</div>

        {{-- 5 --}}
        <div class="sm-step sm-sec" id="s5">
            <div class="sm-head bg-sm-v"><span class="sm-no">5</span><span class="sm-title">매칭(투표)</span><span class="sm-state">와인 매칭중(V) · 1:1 진행(S)부터</span></div>
            <div class="sm-body">
                <div class="sm-col is-admin">
                    <h4><i class="ti ti-user-cog"></i> 관리자</h4>
                    <ul>
                        <li>와인: 마지막 턴 뒤 <b>[매칭 시작]</b></li>
                        <li>1:1: 누를 것 없이 이미 열려 있음</li>
                        <li>잘못 눌렀다면 <b>[매칭 시작 취소]</b></li>
                    </ul>
                </div>
                <div class="sm-col is-app">
                    <h4><i class="ti ti-device-mobile"></i> 참가자 앱</h4>
                    <ul>
                        <li>마음에 든 상대 <b>최대 3명</b> 비밀 선택</li>
                        <li>마감 전까지 <b>수정 가능</b></li>
                    </ul>
                </div>
                <div class="sm-col is-sys">
                    <h4><i class="ti ti-settings"></i> 시스템</h4>
                    <ul>
                        <li>누가 골랐는지 상대는 모름</li>
                        <li>와인은 <b>다음 날 오전 9시</b>까지만 선택 가능</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="sm-arrow">▼</div>

        {{-- 6 --}}
        <div class="sm-step sm-sec" id="s6">
            <div class="sm-head bg-sm-x"><span class="sm-no">6</span><span class="sm-title">종료 · 결과 발표</span><span class="sm-state">종료(X)</span></div>
            <div class="sm-body">
                <div class="sm-col is-admin">
                    <h4><i class="ti ti-user-cog"></i> 관리자</h4>
                    <ul>
                        <li><b>[매칭 종료]</b> 또는 자동 마감 대기</li>
                        <li>전체 매칭 · 매칭 결과 · 메시지 · 요청 확인</li>
                        <li>실수했다면 <b>[종료 되돌리기]</b></li>
                    </ul>
                </div>
                <div class="sm-col is-app">
                    <h4><i class="ti ti-device-mobile"></i> 참가자 앱</h4>
                    <ul>
                        <li><b>서로 선택했을 때만</b> 매칭 성사</li>
                        <li>1:1은 <b>어필</b> 탭이 열려 1회 메시지 가능</li>
                    </ul>
                </div>
                <div class="sm-col is-sys">
                    <h4><i class="ti ti-settings"></i> 시스템</h4>
                    <ul>
                        <li>와인은 다음 날 9시에 <b>자동 종료</b></li>
                        <li>종료 요약이 텔레그램으로 (운영 환경만)</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- 음료 --}}
        <div class="card mt-4 sm-sec" id="drink">
            <div class="card-body">
                <h3 class="fw-bold mb-1"><i class="ti ti-glass-full text-orange me-1"></i> 1:1 음료 선택 흐름</h3>
                <div class="text-muted mb-3">1:1 소개팅에만 있는 기능이에요.</div>
                <div class="sm-flow4">
                    <div class="sm-box"><b>① 목록 등록</b>체크인 화면 [음료 선택] 카드에서 음료 추가</div>
                    <div class="sm-box"><b>② 참가자 선택</b>입장등록 때 하나 고르기 (필수)</div>
                    <div class="sm-box"><b>③ 실시간 현황</b>고르면 바로 알림, 음료별 인원 표시</div>
                    <div class="sm-box"><b>④ 전달 체크</b>가져다준 뒤 사람을 눌러 <b>전달 완료</b></div>
                </div>
                <ul class="mt-3 mb-0" style="line-height:1.9;">
                    <li>현황에는 <b>성별 · 번호표 · 테이블</b>만 보여요.</li>
                    <li>전달 완료를 누르면 시각이 남고, 다시 누르면 해제돼요.</li>
                    <li>음료를 바꾸거나 목록에서 지우면 <b>전달 상태가 초기화</b>돼요.</li>
                </ul>
            </div>
        </div>

        {{-- 앱 탭 --}}
        <div class="card mt-3 sm-sec" id="tabs">
            <div class="card-body">
                <h3 class="fw-bold mb-1"><i class="ti ti-device-mobile text-pink me-1"></i> 참가자 앱 — 하단 탭</h3>
                <div class="text-muted mb-3">행사 타입과 상태에 따라 달라져요.</div>

                <div class="fw-bold mb-2"><span class="badge bg-red-lt">와인파티</span></div>
                <div class="sm-tabs mb-4">
                    <div class="sm-tab"><span class="sm-ico">💗</span><b>홈</b>내 테이블 · 이번 상대</div>
                    <div class="sm-tab"><span class="sm-ico">💬</span><b>메시지</b>익명 메시지 주고받기<br><small>최대 5건</small></div>
                    <div class="sm-tab"><span class="sm-ico">🗳️</span><b>매칭하기</b>최대 3명 선택<br><small>매칭중(V)에 열림</small></div>
                    <div class="sm-tab"><span class="sm-ico">🙋</span><b>요청</b>운영진에게 요청 · 답장 확인</div>
                    <div class="sm-tab"><span class="sm-ico">🎮</span><b>게임</b>Yes or No · 이미지 · 밸런스<br><small>진행(S) 중에만</small></div>
                </div>

                <div class="fw-bold mb-2"><span class="badge bg-purple-lt">1:1 소개팅</span></div>
                <div class="sm-tabs">
                    <div class="sm-tab"><span class="sm-ico">💗</span><b>홈</b>내 자리 · 이번 상대 · 바로 매칭 선택</div>
                    <div class="sm-tab"><span class="sm-ico">❓</span><b>질문카드</b>대화 주제 카드</div>
                    <div class="sm-tab"><span class="sm-ico">🗳️</span><b>매칭 결과</b>종료 후 열림</div>
                    <div class="sm-tab"><span class="sm-ico">💌</span><b>어필</b>종료 후 1회 메시지<br><small>종료 전엔 잠금 안내</small></div>
                </div>
                <div class="text-muted small mt-2">1:1에는 메시지함 · 요청 탭이 없어요.</div>
            </div>
        </div>

        <div class="text-center text-muted small my-4">
            더 자세한 규칙과 화면별 사용법은 <a href="{{ route('admin.manual') }}">전체 사용 설명서</a>에서 볼 수 있어요.
        </div>
    </div>
</div>
@endsection
