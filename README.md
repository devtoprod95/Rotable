# ROTABLE (로테이블) — 로테이션 소개팅 운영 플랫폼

> 한 자리에서 모두와 마주치는 **로테이션 소개팅 파티**를 기획·운영·매칭까지 한 번에 처리하는 웹 서비스입니다.

로테이블은 오프라인 소개팅 행사(와인파티 등)를 디지털로 운영하기 위한 플랫폼입니다.
참가자는 행사장에서 자리를 **로테이션(순환)** 하며 모든 상대와 대화하고, 마음에 든 상대에게 **비밀 투표**를 합니다.
서로를 선택한 경우 **다음 날 오전 매칭 결과**가 발표됩니다. 관리자는 행사 생성부터 좌석 배정, 투표 집계, 매칭 결과 확인까지 전 과정을 웹에서 관리합니다.

---

## 서비스 개요

### 참가자(회원) 관점 흐름

1. **입장등록 / 입장** — 회차(행사)를 선택하고 닉네임·핸드폰·비밀번호(4자리) 등 프로필을 등록하거나 로그인
2. **로테이션 대화** — 행사 타입(와인파티 / 1:1 로테이션 등)에 따라 테이블 단위(3:3, 4:4, 3:2 등)로 단체 대화를 하거나 1:1로 대화하며, 턴마다 자리를 이동해 여러 상대를 만남
3. **메시지 & 비밀 투표** — 대화 중 상대에게 메시지를 보내고, 마음에 든 상대를 최대 3명까지 비공개로 선택
4. **미니게임(와인파티·진행중 한정)** — 로테이션 진행 중 하단 '게임' 탭에서 테이블끼리 즐기는 간단한 아이스브레이킹 게임 (Yes or No / 이미지게임 / 밸런스 게임)
5. **자유 대화 시간(2차)** — 투표 후, 마음에 드는 상대와 자유롭게 앉아 더 깊은 대화
6. **매칭 발표** — 서로 선택한 경우 다음 날 오전 매칭 성사 안내

### 관리자 관점 흐름

```
행사 생성(대기) → 테이블/좌석 배정 → 로테이션 진행(턴 생성) → 투표 시작(투표중)
   → 투표 마감(종료) → 매칭 결과/메시지/요청 확인
```

- **행사 관리**: 회차(행사) 생성/수정/삭제, 참여자 현황 확인
- **로테이션 설정(좌석 배정)**: 턴별로 어느 회원이 어느 테이블에 앉는지 배치하고, 로테이션을 자동 진행(여자 고정 / 남자 한 칸 이동)
- **매칭/메시지/요청 조회**: 투표 결과(상호 매칭 쌍), 오간 메시지, 회원이 관리자에게 보낸 요청 확인
- **상세페이지 제작**: 홍보용 상세페이지를 HTML 에디터로 꾸미고 PNG 이미지로 다운로드

---

## 핵심 도메인 모델

| 모델 | 테이블 | 설명 |
|---|---|---|
| `EventRound` | `event_rounds` | 행사(회차). 상태: 대기(P) → 진행(S) → 투표중(V) → 종료(X) |
| `Member` | `members` | 회차에 입장/등록한 참가자. 성별·출생연도·직업·키·MBTI 등 프로필 |
| `RotationTable` | `rotation_tables` | 행사의 테이블(자리). 테이블 번호 보유 |
| `RotationRound` | `rotation_rounds` | 로테이션 "턴"(0턴, 1턴, ...). 행사별로 턴이 쌓임 |
| `RotationSeating` | `rotation_seatings` | 특정 턴에서 어느 회원이 어느 테이블에 앉았는지 |
| `Vote` | `event_votes` | 비밀 투표(지목). 한 회원당 최대 3명 선택 |
| `Message` | `messages` | 참가자 간 메시지 |
| `AdminRequest` | `admin_requests` | 회원이 관리자에게 보낸 요청 |
| `Admin` / `Role` | `admins` / RBAC | 관리자 계정과 역할/권한 |

> **테이블 네이밍 규칙**: 도메인별 prefix(`event_`, `rotation_`)를 사용합니다. 상세 규칙은 `.kiro/steering/table-naming.md` 참고.

### 상태 전이 (EventRound.status)

```
P(대기) ──[로테이션 진행]──▶ S(진행) ──[투표 시작]──▶ V(투표중) ──[투표 마감]──▶ X(종료)
```

- 투표는 행사 다음 날 오전 9시에 자동 마감(`votingClosesAt()`)되며, 관리자가 수동으로 즉시 마감할 수도 있습니다.

---

## 아키텍처

### 레이어 구성

```
┌─────────────────────────────────────────────────────────────┐
│  Client                                                      │
│  ├─ 관리자(Admin)  : Blade + Tabler UI  (서버 렌더링)         │
│  └─ 참가자(Member) : React + Inertia.js  (SPA 느낌)          │
└─────────────────────────────────────────────────────────────┘
                    │ HTTP / Inertia
┌─────────────────────────────────────────────────────────────┐
│  Laravel 13 (PHP 8.3)                                        │
│  ├─ Routes      : routes/web.php (회원) · routes/admin.php    │
│  ├─ Controllers : RotationController, MemberPageController …  │
│  ├─ Services    : RotationService (도메인 로직 집중)          │
│  ├─ Models      : Eloquent (event_/rotation_ prefix)         │
│  └─ Events      : 메시지/요청 실시간 브로드캐스트             │
└─────────────────────────────────────────────────────────────┘
          │                    │                    │
   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
   │ MySQL/Pgsql  │    │ Redis        │    │ Reverb/Pusher│
   │ (영속 데이터)│    │ (세션/캐시/큐)│   │ (실시간 채팅)│
   └──────────────┘    └──────────────┘    └──────────────┘
```

### 기술 스택

| 영역 | 사용 기술 |
|---|---|
| Backend | Laravel 13, PHP 8.3 (FPM) |
| 관리자 View | Blade + Tabler UI |
| 참가자 View | React + Inertia.js, Tailwind CSS v4 (Vite 빌드) |
| DB | MySQL 또는 PostgreSQL (드라이버 빌트인, `.env`로 전환) |
| Cache / Session / Queue | Redis |
| 실시간 | Laravel Reverb / Pusher (Echo) |
| 모니터링 | Laravel Horizon, Pulse |
| Web Server | Nginx (Alpine) |
| 실행 환경 | Docker |
| 배포 | Railway |

### 요청 처리 패턴

- 관리자 화면은 **Blade**로 서버 렌더링하고, 목록/표는 **Tabulator**(ajax)로 조회합니다.
- 참가자 화면은 **Inertia + React**로 구성되어 SPA처럼 동작합니다.
- 도메인 로직은 컨트롤러가 아닌 **`RotationService`에 집중**되어 있습니다. 컨트롤러는 검증/응답 변환만 담당합니다.
- 공통 응답은 `apiRes()` / `helpersSuccessMessage()` / `helpersFailMessage()` 헬퍼로 표준화합니다.

---

## 서비스 동작 방식 (How It Works)

이 서비스가 **실제로 어떻게 돌아가는지**를 행사 한 번의 생애주기(lifecycle)를 따라 설명합니다. 핵심 도메인 로직은 `RotationService`에 모여 있고, 상태 전이는 관리자의 조작 또는 스케줄러가 트리거합니다.

### 전체 흐름 한눈에 보기

```
[관리자]                                   [참가자]
   │ 1. 행사 생성 (상태 P·대기)
   │ 2. 테이블 N개 생성
   │                                          │ 3. 입장등록/로그인 (회차 선택)
   │ 4. 0턴 좌석 배정(세팅)
   │ 5. 로테이션 진행 ──────────────────────▶  홈에서 "내 테이블/상대" 실시간 갱신
   │    (상태 P→S·진행, 턴마다 남자 한 칸 이동)   메시지(익명) 전송 / 관리자에게 요청
   │ 6. 투표(매칭) 시작 ────────────────────▶  '매칭하기' 탭 열림 → 비밀 투표(최대 3명)
   │    (상태 S→V·매칭중)
   │ 7. 투표 마감 ──────────────────────────▶  매칭 결과 확인
   │    (상태 V→X·종료, 수동 or 자동 9시)
   │ 8. 매칭 쌍/메시지/요청 조회
```

### 1) 행사(회차) 생성 — 상태 `P`(대기)

- 관리자가 제목·**타입**(와인파티 `wine` / 1:1 `one`)·행사일시를 입력해 `event_rounds` 레코드를 만듭니다. 상태는 항상 `P`(대기)로 고정 생성됩니다.
- 생성 시 테이블 개수(`table_count`)를 함께 지정하면 `rotation_tables`가 같이 만들어집니다. (나중에 상세 화면에서 추가/삭제 가능)

### 2) 참가자 입장 / 등록

- 참가자는 랜딩(`/`)에서 회차를 선택하고 닉네임·핸드폰·4자리 비밀번호·성별·출생연도·직업·키·MBTI·지역·흡연 여부와 장점/이상형/취미/TMI 등 프로필을 등록(`/register`)하거나 로그인(`/login`)합니다.
- 각 회원(`members`)은 하나의 행사(`event_id`)에 소속됩니다. 비밀번호는 요구사항에 따라 **평문 저장**합니다(해싱하지 않음).
- 참가자 화면(홈/메시지/매칭/요청)은 `auth:web` 가드로 보호되며 React + Inertia로 렌더링됩니다.

### 3) 좌석 배정과 로테이션 진행 — 상태 `S`(진행)

로테이션의 핵심 규칙은 **"여자는 테이블에 고정, 남자는 턴마다 한 칸씩 이동"** 입니다. (`RotationService::rotateProgress`)

- **턴(round) 모델**: `rotation_rounds`에 턴이 쌓입니다. `round_no = 0`은 관리자용 초기 세팅 턴으로, **참가자 화면에는 노출되지 않습니다.** 실제 만남은 1턴부터입니다.
- **좌석(seating)**: `rotation_seatings`에 "어느 턴에, 어느 회원이, 어느 테이블에 앉았는지"가 기록됩니다.
- **이동 규칙**:
  - 0턴 → 1턴: 세팅 그대로 복사(남자도 이동하지 않음).
  - 1턴부터: 매 진행마다 **남자만 다음 테이블(`table_no` 오름차순, 마지막 → 첫 번째 순환)로 +1 이동**하고 여자는 제자리.
  - 테이블이 N개면 **N턴이 마지막 턴**(남자가 제자리로 복귀하는 시점)입니다.
- 첫 로테이션 진행 시 행사 상태가 `P` → `S`(진행)로 바뀝니다. 1:1(`one`) 타입은 별도의 투표 시작 단계 없이 진행(`S`)부터 매칭 선택이 열리며, 이때 `voting_opened_at`이 기록됩니다.
- 진행 상황은 `RotationUpdated` 이벤트로 브로드캐스트되어 참가자 홈 화면의 "내 테이블/이번 턴 상대"가 실시간으로 갱신됩니다.

### 4) 대화 중 상호작용 — 메시지 & 관리자 요청

- **익명 메시지**: 참가자는 같은 행사의 이성 상대에게 메시지를 보낼 수 있습니다(`messages`, 1인 최대 `5`건). 메시지 전송은 **진행(`S`)·매칭중(`V`) 상태에서만** 허용됩니다. 전송/읽음은 `MessageSent`/`MessageRead` 이벤트로 실시간 반영됩니다.
- **관리자 요청**: 참가자가 운영진에게 보내는 요청(`admin_requests`)으로, 자리 번호와 함께 저장되어 관리자 화면에 실시간(`AdminRequestSent`)으로 뜹니다.

### 5) 비밀 투표(매칭) — 상태 `V`(매칭중)

- **와인파티(`wine`)**: 관리자가 "매칭 시작"을 누르면 상태가 `S` → `V`(매칭중)로 전환되고 `EventVotingOpened` 이벤트가 발행됩니다. 참가자 화면의 '매칭하기' 탭이 열립니다.
- **1:1(`one`)**: 앞서 설명한 대로 진행(`S`)부터 이미 매칭 선택이 열려 있습니다. 별도 `V` 시작 조작 없이 그 자리에서 선택/수정합니다.
- **투표 규칙**: 한 회원은 마음에 든 상대를 **최대 3명(`Vote::MAX_VOTES_PER_MEMBER`)** 까지 비공개로 지목(`event_votes`)합니다. 마감 전까지 자유롭게 수정할 수 있습니다.
- `EventRound::isMatchSelectable()`이 "지금 선택/수정 가능한 기간인지"를 판정합니다.
  - 1:1: 진행(`S`)·매칭중(`V`) 동안, 관리자가 종료(`X`)로 바꾸기 전까지.
  - 와인파티: 매칭중(`V`)이면서 자동 마감 시각(= 행사 **다음 날 오전 9시**, `votingClosesAt()`) 이전까지.

### 6) 투표 마감과 매칭 발표 — 상태 `X`(종료)

- **마감 트리거**는 두 가지입니다.
  - 수동: 관리자가 "매칭 종료"(또는 1:1의 마지막 턴 종료)를 눌러 `V`(또는 `S`) → `X`로 전환. `EventClosed` 이벤트가 발행되어 참가자 화면이 즉시 결과 탭으로 갱신됩니다.
  - 자동: 스케줄러가 매일 오전 9시에 `CloseVoting` 커맨드를 실행해, "행사 다음 날 오전 9시"가 지난 매칭중(`V`) 행사를 `X`로 일괄 전환합니다(`routes/console.php`).
- **상호 매칭 판정**: `A`가 `B`를, `B`가 `A`를 **서로 지목**한 경우에만 매칭 성사입니다(단방향 투표는 매칭 아님). `RotationService`가 `event_votes`를 self-join으로 집계합니다.
  - `matchList()`: 누가 누구를 골랐는지 전부(voter 기준으로 묶고, 상호 매칭 상대는 `is_mutual=true`).
  - `matchedPairList()`: 상호 매칭된 쌍만, **남자(voter) 기준**으로 한 번씩만 노출(A↔B 중복 방지).

### 상태 전이 요약 (EventRound.status)

| 전이 | 트리거 | 발행 이벤트 |
|---|---|---|
| `P` → `S` (대기→진행) | 관리자가 로테이션 처음 진행 | `RotationUpdated` |
| `S` → `V` (진행→매칭중) | 관리자가 "매칭 시작" (와인파티) | `EventVotingOpened` |
| `S`/`V` → `X` (→종료) | 관리자 "매칭 종료" 또는 자동 9시 마감 | `EventClosed` |

> 1:1(`one`) 타입은 `V` 단계를 건너뛰고 `S`에서 바로 매칭 선택이 열립니다.

### 실시간(Broadcast) 동작

실시간 반영은 Laravel Reverb/Pusher(Echo)로 처리되며, 주요 이벤트는 다음과 같습니다.

| 이벤트 | 발생 시점 | 참가자 화면 동작 |
|---|---|---|
| `RotationUpdated` | 로테이션 턴 진행 | 내 테이블/이번 턴 상대 갱신 |
| `EventVotingOpened` | 매칭(투표) 시작 | '매칭하기' 탭 열림 |
| `EventClosed` | 행사 종료 | 매칭 결과 탭으로 리로드 |
| `MessageSent` / `MessageRead` | 메시지 전송/읽음 | 메시지함 실시간 반영 |
| `AdminRequestSent` / `AdminRequestRead` | 관리자 요청 전송/확인 | 요청 상태 실시간 반영 |

### 부가 기능

- **미니게임(와인파티·진행중 한정)**: 로테이션 진행(`S`) 중 참가자 하단 '게임' 탭에서 테이블끼리 즐기는 간단한 게임을 제공합니다. 탭/접근은 **와인파티(`wine`) && 진행중(`S`)** 에서만 열리며, 매칭 시작(`V`)·종료(`X`)되면 탭이 사라지고 게임 화면이면 홈으로 이동합니다.
  - **Yes or No**: 같은 테이블 사람들이 같은 질문에 **익명**으로 예/아니오 투표 → YES/NO 수가 실시간 집계. 집계는 DB가 아닌 **Redis(휘발성)** 에 테이블 단위로 보관해 중간 합류·화면 복귀 시에도 현재 현황이 보입니다. 테이블 전용 private 채널(`table-poll.{eventId}.{tableNo}`, 현재 좌석 기준 인증)로 동작하며, 다음 질문으로 넘어가면 집계가 초기화됩니다.
  - **이미지게임(순한맛/매운맛)**: "~한 사람?" 질문에 어울리는 이성을 다같이 지목. 질문은 프론트 풀에서 덱 셔플로 출제.
  - **밸런스 게임**: A vs B 둘 중 하나를 엄지 업/다운으로 선택.
- **만남 이력 조회(관리자)**: 전화번호(정확 일치) 기준으로 "같은 행사에 함께 참여한 상대"를 조회합니다. 번호 2개를 넣으면 두 사람의 만남 이력 상세를 봅니다.
- **QR 리다이렉트**: 관리자가 대상 URL을 등록하면 공개 주소 `/qr/{code}`가 발급되어 지정 URL로 이동합니다.
- **상세페이지 디자이너**: 홍보용 상세페이지를 HTML 에디터로 꾸미고 PNG로 다운로드합니다.
- **방문 통계**: 스케줄러가 자정마다 방문 통계를 `event_visit_snapshots`에 스냅샷으로 남깁니다(`SnapshotVisitStats`).
- **데이터 정리(운영)**: `php artisan data:purge {members|stats|all}` 커맨드로 회원/통계/전체 데이터를 물리 삭제합니다(파괴적, 확인 프롬프트 있음).

---

## 디렉터리 구조 (요약)

```
.
├── app/
│   ├── Http/Controllers/   # RotationController(행사/좌석/매칭), MemberPageController(참가자) …
│   ├── Services/           # RotationService (핵심 도메인 로직)
│   ├── Models/             # EventRound, Member, Rotation*, Vote, Message …
│   └── Events/             # 메시지/요청 실시간 이벤트
├── resources/
│   ├── views/admin/rotation/   # 관리자 로테이션 화면(Blade)
│   │   ├── eventList / eventForm      # 행사 목록 / 상세
│   │   ├── seating                    # 좌석 배정
│   │   ├── detail / _detailSample     # 상세페이지 제작 + 샘플
│   │   └── _*Modal                    # 매칭/메시지/요청/투표 모달
│   └── js/Pages/               # 참가자 React 페이지 (Auth, Home, Votes, Chats, Games/* …)
├── routes/
│   ├── web.php             # 참가자(회원) 라우트
│   └── admin.php           # 관리자 라우트
├── database/migrations/    # event_/rotation_ 테이블
└── docker/ , docker-compose.yml
```

---

## 시작하기 (Quick Start)

### 1. 클론 & 환경 변수

```bash
git clone <repository-url> rotable
cd rotable
cp .env.example .env
```

`.env`에서 DB/Redis/포트를 환경에 맞게 설정합니다. (`DB_CONNECTION`을 `mysql` 또는 `pgsql`로 전환 가능)

### 2. 컨테이너 빌드 & 실행

```bash
docker compose up -d --build
docker compose exec php bash

# 컨테이너 내부
composer install
php artisan key:generate
php artisan migrate --seed
npm install && npm run build   # 참가자 React/Tailwind 빌드
```

### 3. 접속

- 관리자: `http://localhost:9083/admin/login`
- 참가자: `http://localhost:9083/`

---

## 자주 쓰는 명령어

```bash
docker compose exec php php artisan migrate      # 마이그레이션
docker compose exec php php artisan optimize:clear
docker compose exec php php artisan test --compact
npm run dev    # 프론트 개발 모드 (HMR)
```

---

## 라이선스

내부 운영용 프로젝트입니다.
