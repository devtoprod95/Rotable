---
inclusion: always
---

# 코드 스타일 (Code Style)

## 배열 화살표(`=>`) 정렬 (MUST)

연속된 연관 배열 항목은 `=>` 위치를 세로로 맞춘다. (가장 긴 키 기준으로 공백 패딩)
검증 규칙(`rules()`)·메시지(`messages()`)·뷰 데이터·응답 배열 등 모든 PHP 배열에 적용한다.

```php
return [
    'event_id'       => 'required|integer',
    'rotation_limit' => 'nullable|integer|min:1|max:100',
];
```

- 빈 줄로 구분된 묶음은 묶음 단위로 따로 맞춘다. (사이에 낀 주석 줄은 묶음을 끊지 않는다)
- 연속된 대입문(`$res['msg'] = ...;` 등)의 `=` 도 같은 방식으로 맞춘다.
- 중첩 배열은 각 깊이(들여쓰기)별로 따로 맞춘다.
