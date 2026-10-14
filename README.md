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
4. **자유 대화 시간(2차)** — 투표 후, 마음에 드는 상대와 자유롭게 앉아 더 깊은 대화
5. **매칭 발표** — 서로 선택한 경우 다음 날 오전 매칭 성사 안내

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
│   └── js/Pages/               # 참가자 React 페이지 (Auth, Home, Votes, Chats …)
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
