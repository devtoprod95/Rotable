# 실시간(WebSocket) 동작 방식 & 라이프사이클

이 문서는 ROTABLE의 실시간 기능이 **어떻게 연결되고, 누가 무슨 역할을 하며, 이벤트 하나가 어떤 경로로 브라우저까지 도달하는지**를 설명합니다.

핵심 한 줄 요약:

> **"방(채널)을 정의하고 입장 자격을 심사하는 건 Laravel(PHP)이고, 그 방을 실제로 운영하며 참가자들에게 메시지를 뿌리는 건 Reverb(WebSocket 서버)다."**

---

## 0. "라라벨이 방을 열어주는 건가?"에 대한 정확한 답

반은 맞고 반은 아닙니다. 역할을 나눠보면:

| 역할 | 담당 | 설명 |
|---|---|---|
| **방(채널) 정의** | Laravel `routes/channels.php` | 어떤 이름의 방이 존재하고, 누가 들어올 수 있는지 규칙 정의 |
| **입장 자격 심사** | Laravel `/broadcasting/auth` (PHP-FPM) | private 채널 구독 시 세션으로 "너 들어올 자격 있어?" 판정 |
| **방 운영 / 메시지 중계** | **Reverb** (`artisan reverb:start`) | 실제 WebSocket 연결을 유지하고, 들어온 메시지를 구독자에게 전달 |
| **무엇을 보낼지(편지 내용)** | Laravel `app/Events/*` | 어느 채널에 / 어떤 이벤트명으로 / 무슨 데이터를 보낼지 |
| **실제 비즈니스 로직** | `app/Http/Controllers/*`, `app/Services/RotationService` | 저장·집계·상태 전이 등 "진짜 일" |

즉 **Laravel은 방을 "설계·경비"하고, Reverb는 방을 "운영·배달"합니다.** 비즈니스 로직은 웹소켓이 아니라 일반 HTTP 요청으로 PHP에서 처리됩니다.

---

## 1. 구성 요소 (Components)

```
┌─────────────────────────────────────────────────────────────────────┐
│  브라우저 (참가자, React + Inertia)                                   │
│   ├─ resources/js/echo.js          : Echo 인스턴스 (연결 설정)        │
│   ├─ resources/js/useRealtimeChat.js : chat.{memberId} 구독 훅        │
│   └─ Pages/Home.jsx, Votes.jsx, Requests.jsx : 채널 구독 & 화면 갱신  │
└─────────────────────────────────────────────────────────────────────┘
        │ ① HTTP (로직 요청)              │ ② WSS (실시간 수신)
        ▼                                 ▼
┌───────────────────────────┐   ┌───────────────────────────────────┐
│  nginx (:8080, 공개 포트) │   │  nginx  location /app → proxy_pass │
│   → PHP-FPM (:9000)       │   │        127.0.0.1:9997              │
└───────────────────────────┘   └───────────────────────────────────┘
        │                                 │
        ▼                                 ▼
┌───────────────────────────┐   ┌───────────────────────────────────┐
│  Laravel (PHP)            │   │  Reverb 서버 (:9997)               │
│   Controller/Service      │   │  artisan reverb:start              │
│   → DB 저장/집계          │   │  --host=0.0.0.0 --port=9997        │
│   → event(new Xxx(...))   │──▶│  (supervisor가 상주 실행)          │
│        (브로드캐스트 push) │   │  구독자에게 메시지 전달            │
└───────────────────────────┘   └───────────────────────────────────┘
```

- **공개되는 포트는 nginx(8080)뿐**입니다. Reverb(9997)는 컨테이너 내부에만 바인딩되어, 외부는 반드시 `nginx /app` 프록시를 거칩니다.
- 운영(Railway)은 단일 컨테이너 안에서 `supervisor`가 `php-fpm`, `nginx`, `reverb`, `horizon`, `schedule` 등을 함께 띄웁니다. (`docker/supervisor-railway.conf`)
- 로컬(docker-compose)은 전용 `reverb` 컨테이너가 따로 떠서 호스트에 `9997`로 공개됩니다.

---

## 2. 두 종류의 트래픽 (가장 중요한 개념)

프론트는 서버와 **두 경로**로 통신합니다. 이걸 섞으면 헷갈립니다.

| 구분 | 경로 | 역할 | 로직 유무 |
|---|---|---|---|
| **① 로직 요청** | 브라우저 → nginx → PHP-FPM → Controller/Service → DB | 메시지 저장, 투표 집계, 로테이션 진행 등 실제 처리 | **O (여기서 다 함)** |
| **② 실시간 수신** | 브라우저 ⇄ nginx `/app` ⇄ Reverb(9997) | 결과를 "듣기"만 함 | **X (중계만)** |

> WebSocket(`/app`)은 **비즈니스 로직을 실행하지 않습니다.** "결과를 밀어주는 통로/우편함"일 뿐입니다.

---

## 3. 연결 라이프사이클 (한 요청의 생애)

메시지 전송을 예로, 연결부터 화면 갱신까지 전체 순서입니다.

### (A) 앱 로드 & WebSocket 연결
1. 브라우저가 React 앱을 로드하면 `resources/js/echo.js`가 `VITE_REVERB_*` 값으로 Echo 인스턴스를 생성.
2. Echo가 `wss://<도메인>/app/{VITE_REVERB_APP_KEY}`로 Reverb에 WebSocket 연결. (nginx `/app`이 받아 9997로 프록시)

### (B) private 채널 구독 (입장 자격 심사)
3. `useRealtimeChat.js`가 로그인 회원의 개인 채널 `chat.{memberId}`를 `echo.private(...)`로 구독 시도.
4. 구독 전, Echo가 **HTTP POST `/broadcasting/auth`** 를 호출(CSRF 토큰 동봉 — `echo.js`의 `auth.headers`).
5. 이 요청은 PHP-FPM으로 가서 `routes/channels.php`의 클로저 실행:
   - `chat.{memberId}` → "로그인 회원 id == memberId 인가?" (`web` 가드 세션 인증)
   - 통과하면 서명된 토큰 발급 → Echo가 그 토큰으로 Reverb에 구독 확정.
6. 이제 브라우저는 자기 방(`chat.{내id}`)에서 벌어지는 일을 들을 준비 완료.

### (C) 메시지 전송 (로직 처리 — 일반 HTTP)
7. 발신자가 메시지를 보내면 **일반 HTTP 요청**이 들어옴.
8. Controller가 검증 후 DB(`messages`)에 저장. (여기가 "진짜 일"을 하는 곳)
9. 저장 성공 후 `event(new MessageSent($message))` 발생.

### (D) 브로드캐스트 (Laravel → Reverb → 구독자)
10. `MessageSent`는 `ShouldBroadcastNow`라 **즉시** Reverb로 push.
    - `broadcastOn()` → `PrivateChannel('chat.' . $receiver_id)` (수신자 방으로만)
    - `broadcastAs()` → `'message.sent'` (프론트가 들을 이벤트명)
    - `broadcastWith()` → 발신자 익명 처리된 payload
11. Reverb가 `chat.{receiver_id}` 구독자(수신자 브라우저)에게 WebSocket으로 전달.

### (E) 프론트 수신 & 화면 갱신
12. `useRealtimeChat.js`의 `channel.listen('.message.sent', ...)` 콜백 실행 → `onMessage(message)` → 메시지함 실시간 갱신.

```
발신자 브라우저 ──POST(HTTP)──▶ PHP: 저장 + event(MessageSent)
                                      │ push
                                      ▼
                               Reverb(chat.{수신자id})
                                      │ wss
                                      ▼
                          수신자 브라우저: onMessage → 화면 갱신
```

---

## 4. 채널 & 이벤트 전체 지도

### 4-1. 채널 정의 (`routes/channels.php`)

| 채널 | 종류 | 구독 자격 | 가드 |
|---|---|---|---|
| `chat.{memberId}` | private | 로그인 회원 **본인**(`member.id == memberId`) | `web` |
| `event-rotation.{eventId}` | private | 그 행사 소속 회원(`member.event_id == eventId`) | `web` |

> `admin-requests`, `event-messages.{eventId}`는 `Channel`(public)로, `channels.php`에 인증 규칙 없이 열린 채널입니다.

### 4-2. 이벤트 → 채널 → 이벤트명 매핑 (`app/Events/*`)

| 이벤트 클래스 | 채널 (`broadcastOn`) | 종류 | 프론트 이벤트명 (`broadcastAs`) | 발생 시점 |
|---|---|---|---|---|
| `MessageSent` | `chat.{receiver_id}` | private | `message.sent` | 회원 간 메시지 전송 |
| `MessageRead` | `chat.{sender_id}` | private | `message.read` | 내가 보낸 메시지가 읽힘 |
| `AdminRequestRead` | `chat.{memberId}` | private | `request.read` | 관리자가 내 요청을 확인 |
| `RotationUpdated` | `event-rotation.{eventId}` | private | `rotation.updated` | 로테이션 턴 진행 |
| `EventVotingOpened` | `event-rotation.{eventId}` | private | `event.voting.opened` | 매칭(투표) 시작 |
| `EventClosed` | `event-rotation.{eventId}` | private | `event.closed` | 행사 종료 |
| `AdminRequestSent` | `admin-requests` | public | `request.sent` | 회원이 관리자에게 요청 전송 |
| `EventMessageSent` | `event-messages.{event_id}` | public | `message.sent` | 행사 단위 메시지 |

> 모든 이벤트가 `ShouldBroadcastNow` → 큐를 거치지 않고 즉시 브로드캐스트됩니다.

---

## 5. 프론트/서버 설정 키 (연결이 안 될 때 점검 순서)

연결 실패의 대부분은 아래 값 불일치입니다.

### 클라이언트(브라우저, `VITE_*` → 빌드 시 주입, `echo.js`에서 사용)
| 키 | 운영(Railway) 권장값 | 의미 |
|---|---|---|
| `VITE_REVERB_APP_KEY` | `REVERB_APP_KEY`와 **동일** | 앱 식별 키 |
| `VITE_REVERB_HOST` | 공개 도메인 | 접속 호스트 |
| `VITE_REVERB_PORT` | `443` | 접속 포트(wss) |
| `VITE_REVERB_SCHEME` | `https` | `https`면 `forceTLS=true` → wss |

> `VITE_*`는 **빌드 타임에 박히므로**, 값 변경 후 반드시 `npm run build` 필요.

### 서버(PHP 브로드캐스터 → Reverb로 push)
| 키 | 운영 권장값 | 의미 |
|---|---|---|
| `BROADCAST_CONNECTION` | `reverb` | 브로드캐스터 드라이버 |
| `REVERB_HOST` | `127.0.0.1` | 같은 컨테이너 내 Reverb |
| `REVERB_PORT` | `9997` | Reverb 바인딩 포트와 일치 |
| `REVERB_SCHEME` | `http` | 내부 통신 |
| `REVERB_APP_ID/KEY/SECRET` | 발급값 | 서버·클라이언트 **키 일치**가 핵심 |

### 점검 체크리스트
1. `REVERB_APP_KEY == VITE_REVERB_APP_KEY` 인가? (가장 흔한 실패 원인)
2. Reverb가 `9997`로 떠 있고, nginx `/app`이 `127.0.0.1:9997`로 프록시하는가?
3. `VITE_*` 변경 후 `npm run build` 했는가?
4. 공개 도메인이 HTTPS인가? (`forceTLS`/wss 사용 시 필수)
5. private 채널 실패 시 `/broadcasting/auth`가 200을 주는가? (세션·CSRF 확인)

---

## 6. 관련 파일 빠른 참조

| 관심사 | 파일 |
|---|---|
| WebSocket 연결 설정 | `resources/js/echo.js` |
| 개인 채널 구독 훅 | `resources/js/useRealtimeChat.js` |
| 채널 정의 & 구독 권한 | `routes/channels.php` |
| 브로드캐스트 이벤트 | `app/Events/*.php` |
| 비즈니스 로직 | `app/Http/Controllers/*`, `app/Services/RotationService.php` |
| Reverb 실행(운영) | `docker/supervisor-railway.conf` (`[program:reverb]`) |
| Reverb 실행(로컬) | `docker-compose.yml` (`reverb` 서비스) |
| nginx `/app` 프록시 | `docker/nginx/conf.d/default-railway.conf` |
| 환경변수 가이드 | `.env.example` (REVERB_* / VITE_REVERB_* 주석) |
