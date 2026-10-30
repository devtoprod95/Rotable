---
inclusion: always
---

# DB 테이블 네이밍 컨벤션 (Table Naming Convention)

## 핵심 원칙 (프로젝트 불문, MUST)

도메인 테이블은 **그 테이블이 속한 도메인(바운디드 컨텍스트)을 나타내는 prefix**로 묶는다.
테이블명만 보고도 어떤 도메인에 속하는지 드러나야 한다.

- 새 테이블을 만들 때는 **먼저 그 데이터가 어느 도메인에 속하는지 판단**하고, 그 도메인의 prefix를 붙인다.
- prefix는 **그 프로젝트의 도메인 언어**를 따른다. 특정 단어(`event_` 등)에 종속되지 않는다.
  예를 들어 커머스 프로젝트라면 `order_`, `catalog_`, 결제 도메인이라면 `payment_` 처럼
  **프로젝트/도메인에 맞는 prefix**를 새로 정해서 쓴다.
- 어떤 도메인에도 속하지 않는 프레임워크/인프라 테이블(`users`, `jobs`, `cache`, `sessions` 등
  Laravel 기본 테이블)은 관례상 prefix 없이 그대로 둔다.
- **단수형·도메인 불명확 테이블명 금지.** 예: 그냥 `votes`, `logs`, `items` 처럼
  어느 도메인인지 알 수 없는 이름은 쓰지 않는다. → `event_votes`, `audit_logs`, `order_items`.

## 모델 ↔ 테이블 매핑 (MUST)

- Eloquent 모델명은 단수 PascalCase를 유지하되(`Vote`, `Order`), prefix가 들어간 테이블명은
  **모델의 `protected $table` 프로퍼티에 명시적으로 지정**한다. 이것이 테이블명의 단일 소스다.

```php
class Vote extends Model
{
    protected $table = 'event_votes'; // 도메인 prefix 명시
}
```

- 마이그레이션 파일명/`Schema::create()` 대상 테이블명도 동일한 prefix 테이블명을 사용한다.
- 테이블 생성 시 `status` 등 코드값 컬럼에는 가능한 값을 `comment`로 남긴다.

## 현재 프로젝트(Rotable) 적용 예시

이 프로젝트의 도메인별 prefix는 다음과 같다. (새 테이블 추가 시 이 표를 갱신)

| 도메인 | prefix | 예시 |
|---|---|---|
| 행사(소개팅 회차) 및 그 하위 데이터 | `event_` | `event_rounds`, `event_votes` |
| 로테이션(좌석 배치) | `rotation_` | `rotation_tables`, `rotation_seats` |

- 회원 간 **투표**는 행사(event)에 종속되므로 테이블명은 **`event_votes`**.
- 행사에 종속되는 테이블을 더 추가할 때도 `event_` prefix를 쓴다. (예: `event_notices`, `event_prizes`)
