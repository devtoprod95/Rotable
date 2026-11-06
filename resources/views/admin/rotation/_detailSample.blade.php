{{-- 로테이션 상세페이지 기본 샘플 (더미 데이터).
     - $columns: 1 이면 행사 1개, 2 이면 행사 2개를 2열로 배치
     - $type: 'wine'(와인파티) | 'solo'(1:1 소개팅) — 이미지/타이틀/포인트가 유형별로 달라짐
     - 각 행사 안에서 좌측=남자 / 여자=우측, 각 5명
     - 이름은 N호로 표기, 셀 높이 고정
     실제 행사 데이터와 무관한 예시이며, 에디터에서 자유롭게 수정 가능. --}}
@php
    $columns   = ($columns ?? 2) == 1 ? 1 : 2;
    $cardWidth = $columns === 1 ? '100%' : '50%';
    $type      = ($type ?? 'wine') === 'solo' ? 'solo' : 'wine';

    // 유형별 설정 (이미지/타이틀/행사명/포인트)
    $typeConfig = [
        'wine' => [
            'image'    => file_exists(public_path('images/detail_img_wine.png'))
                ? asset('images/detail_img_wine.png')
                : asset('images/detail_img.png'),
            'title'    => 'ROTABLE 와인파티 안내',
            'subtitle' => '이번 회차 참여자를 확인하세요',
            'eventName'=> '와인파티',
            // 핵심 매력 — 아이콘 카드 그리드로 노출 (icon / title / desc)
            'points'   => [
                ['icon' => '📍', 'title' => '선릉역 도보 3분', 'desc' => '접근성 좋은 역세권에서 진행돼요'],
                ['icon' => '🍷', 'title' => '와인 무제한', 'desc' => '다양한 와인을 무제한으로 즐겨요'],
                ['icon' => '🧀', 'title' => '핑거푸드 무제한', 'desc' => '맛있는 핑거푸드를 마음껏 드세요'],
                ['icon' => '🚻', 'title' => '남녀 화장실 분리', 'desc' => '남녀 화장실이 따로 준비되어 있어요'],
                ['icon' => '✍️', 'title' => '글작성 X', 'desc' => '종이·설문 없이 스마트폰 터치 몇 번이면 끝'],
                ['icon' => '📱', 'title' => '상대 정보 다시보기', 'desc' => '"누구였지?" 걱정 끝, 폰에서 바로 확인'],
                ['icon' => '📊', 'title' => '받은 표 수 확인', 'desc' => '결과 발표 때 내가 몇 표 받았는지 공개'],
                ['icon' => '🎉', 'title' => '2차 비용 무료', 'desc' => '자유 대화 시간은 자율 참석! 남는 분들은 추가 부담없이 즐겨요'],
                ['icon' => '🤫', 'title' => '100% 비밀 투표', 'desc' => '매칭된 상대에게만 살짝 공유돼요'],
                ['icon' => '💬', 'title' => '대화 주제 제공', 'desc' => '가벼운 대화 주제를 드려 처음에도 어색하지 않아요'],
                ['icon' => '🎮', 'title' => '미니게임 제공', 'desc' => '테이블끼리 즐기는 미니게임으로 어색함을 날려요'],
                ['icon' => '💘', 'title' => '다음 날 오전 발표', 'desc' => '서로 선택하면 매칭 성사!'],
            ],
        ],
        'solo' => [
            'image'    => file_exists(public_path('images/detail_img_one.png'))
                ? asset('images/detail_img_one.png')
                : asset('images/detail_img.png'),
            'title'    => 'ROTABLE 1:1 소개팅 안내',
            'subtitle' => '이번 회차 참여자를 확인하세요',
            'eventName'=> '1:1 소개팅',
            // 핵심 매력 — 아이콘 카드 그리드로 노출 (icon / title / desc)
            'points'   => [
                ['icon' => '📍', 'title' => '선릉역 도보 3분', 'desc' => '접근성 좋은 역세권에서 진행돼요'],
                ['icon' => '⏱️', 'title' => '종료 후 10분 내 발표', 'desc' => '로테이션이 끝나고 전원 퇴실하면 10분 이내에 매칭 결과를 바로 확인'],
                ['icon' => '🚻', 'title' => '남녀 화장실 분리', 'desc' => '남녀 화장실이 따로 준비되어 있어요'],
                ['icon' => '🍪', 'title' => '다과 및 음료 제공', 'desc' => '행사 중 즐길 수 있는 다과와 음료를 제공해요'],
                ['icon' => '✍️', 'title' => '글작성 X', 'desc' => '종이·설문 없이 스마트폰 터치 몇 번이면 끝'],
                ['icon' => '📱', 'title' => '상대 정보 다시보기', 'desc' => '"누구였지?" 걱정 끝, 폰에서 바로 확인'],
                ['icon' => '💌', 'title' => '연락처 공유', 'desc' => '서로 선택해 매칭이 성사되면, 두 사람의 연락처가 쌍방으로 공유돼요'],
                ['icon' => '💘', 'title' => '매칭 안 돼도 연락처', 'desc' => '매칭이 안 되더라도, 상대가 나를 선택했다면 그 상대의 연락처를 받을 수 있어요'],
                ['icon' => '📊', 'title' => '받은 표 수 확인', 'desc' => '결과 발표 때 내가 몇 표 받았는지 공개'],
                ['icon' => '🤍', 'title' => '100% 비밀 투표', 'desc' => '서로 선택한 경우에만 매칭 사실 공유'],
            ],
        ],
    ][$type];

    // 더미 참여자 (남 5 / 여 5) — 이름은 N호로 표기
    // 1:1 소개팅(solo)은 나이 제한(남 25~37세 · 여 23~37세)에 맞춰 "년생"으로 표기한다.
    // (한국식 나이 기준: 출생연도 = 올해 - 나이 + 1)
    $thisYear = (int) now()->year;
    $yearsFromAges = function (array $ages) use ($thisYear): array {
        return array_map(fn (int $age): string => ($thisYear - $age + 1) . '년생', $ages);
    };
    // 남 25~37 / 여 23~37 범위 내 샘플 나이 (5명씩)
    $maleSoloYears   = $yearsFromAges([27, 30, 34, 25, 37]);
    $femaleSoloYears = $yearsFromAges([26, 29, 31, 23, 35]);

    // "참여 조건"의 나이(년생) 범위 라인.
    //  - 나이 범위를 한국식 나이 기준으로 출생연도(2자리)로 환산해 "OO~OO년생"으로 표기한다.
    //  - 1:1 소개팅(solo): 남 25~37세 / 여 23~37세.  와인파티(wine): 기존 범위 유지.
    $yy = fn (int $age): string => sprintf('%02d', ($thisYear - $age + 1) % 100); // 출생연도 뒤 2자리

    /* ──────────────────────────────────────────────────────────────────
     | 페이지 설정 (남/녀 나이 · 남/녀 키)
     |  - 상세페이지 제작 화면의 "페이지 설정"에서 수정 가능한 값들의 "기본값".
     |  - 이 값들은 data-detail-settings(JSON = {events:[...]}) 로 HTML 에 함께 심어지고,
     |    data-setting-block="age|maleHeight|femaleHeight" 컨테이너를 JS 가 행사 수에 맞춰 다시 그린다.
     |  - 샘플을 다시 불러와도(참여자 명단처럼) 현재 설정값이 그대로 따라오도록 설계.
     | ─────────────────────────────────────────────────────────────────*/
    // 나이(최소/최대 세) → "OO~OO년생" 문자열로 환산하는 헬퍼.
    //  - 한국식: 출생연도 = 올해 - 나이 + 1.
    //  - 나이 많을수록 출생연도가 작으므로 (최대나이 → 최소나이) 순서로 "OO~OO년생".
    //  - 둘 중 하나라도 비면 빈 문자열.
    $ageText = function (?int $minAge, ?int $maxAge) use ($yy): string {
        if ($minAge === null || $maxAge === null) {
            return '';
        }
        $lo = min($minAge, $maxAge);
        $hi = max($minAge, $maxAge);

        return $yy($hi) . '~' . $yy($lo) . '년생';
    };

    // 나이 기본값(최소/최대 세) — 남/녀·유형 공통 25~39세.
    $maleAgeMin   = 25;
    $maleAgeMax   = 39;
    $femaleAgeMin = 25;
    $femaleAgeMax = 39;

    $settings = [
        'maleAgeMin'   => $maleAgeMin,                           // 남성 최소 나이(세)
        'maleAgeMax'   => $maleAgeMax,                           // 남성 최대 나이(세)
        'femaleAgeMin' => $femaleAgeMin,                         // 여성 최소 나이(세)
        'femaleAgeMax' => $femaleAgeMax,                         // 여성 최대 나이(세)
        'maleHeight'   => '173cm',                              // 남성 최소 신장 (기본 173cm)
        'femaleHeight' => '',                                    // 여성 최소 신장 (빈값이면 미표기)
    ];

    // 나이(세) → 년생 텍스트 (참여 조건 표기용). JS 와 동일한 환산.
    $settings['maleAgeText']   = $ageText($maleAgeMin, $maleAgeMax);
    $settings['femaleAgeText'] = $ageText($femaleAgeMin, $femaleAgeMax);

    $maleSample = [
        ['no' => 1, 'height' => 177, 'job' => '쇼핑몰',   'age' => $type === 'solo' ? $maleSoloYears[0] : '30대 초반'],
        ['no' => 2, 'height' => 181, 'job' => 'IT개발자', 'age' => $type === 'solo' ? $maleSoloYears[1] : '30대 초반'],
        ['no' => 3, 'height' => 184, 'job' => 'IT개발자', 'age' => $type === 'solo' ? $maleSoloYears[2] : '30대 중반'],
        ['no' => 4, 'height' => 175, 'job' => '회사원',   'age' => $type === 'solo' ? $maleSoloYears[3] : '20대 중반'],
        ['no' => 5, 'height' => 182, 'job' => '공무원',    'age' => $type === 'solo' ? $maleSoloYears[4] : '30대 초반'],
    ];
    $femaleSample = [
        ['no' => 1, 'height' => 168, 'job' => '간호사',   'age' => $type === 'solo' ? $femaleSoloYears[0] : '20대 후반'],
        ['no' => 2, 'height' => 160, 'job' => '사업가',   'age' => $type === 'solo' ? $femaleSoloYears[1] : '30대 초반'],
        ['no' => 3, 'height' => 167, 'job' => '공무원',   'age' => $type === 'solo' ? $femaleSoloYears[2] : '30대 초반'],
        ['no' => 4, 'height' => 158, 'job' => '디자이너', 'age' => $type === 'solo' ? $femaleSoloYears[3] : '30대 중반'],
        ['no' => 5, 'height' => 169, 'job' => '승무원',   'age' => $type === 'solo' ? $femaleSoloYears[4] : '30대 초반'],
    ];

    // 더미 행사
    $sampleEvents = [
        ['title' => '1회 ' . $typeConfig['eventName'], 'date' => '2026.11.07(토) 19:30'],
        ['title' => '2회 ' . $typeConfig['eventName'], 'date' => '2026.12.14(토) 19:30'],
    ];
    if ($columns === 1) {
        $sampleEvents = array_slice($sampleEvents, 0, 1);
    }

    /* ──────────────────────────────────────────────────────────────────
     | 행사별 페이지 설정 (방안 B)
     |  - 2열(행사 2개)일 때 행사마다 나이/키 제한을 다르게 둘 수 있도록,
     |    설정을 "행사별 배열(events)"로 보관한다.
     |  - 샘플 기본값은 모든 행사에 동일한 $settings 를 복제해 채운다.
     |  - data-detail-settings(JSON) = { "events": [ {행사1 설정}, {행사2 설정} ] }.
     |    (구버전 저장본은 flat 형태라, JS 에서 events[0] 로 승격해 하위호환 처리한다.)
     | ─────────────────────────────────────────────────────────────────*/
    $eventSettings = array_map(fn () => $settings, $sampleEvents);
    // 행사가 하나도 없으면(이론상) 최소 1개는 보장.
    if (empty($eventSettings)) {
        $eventSettings = [$settings];
    }
    // data 속성에 실어 JS 가 현재 설정을 읽게 할 JSON.
    $settingsJson = json_encode(['events' => $eventSettings], JSON_UNESCAPED_UNICODE);

    // 행사 수가 2개 이상일 때만 각 줄 앞에 행사명(전체)을 붙여 구분한다.
    $multiEvent = count($sampleEvents) > 1;
    // 행사 라벨: 참여 조건에 "정확한 행사명 전체"를 그대로 쓴다. (없으면 "N회")
    $eventLabel = function (int $i) use ($sampleEvents): string {
        $title = trim($sampleEvents[$i]['title'] ?? '');
        return $title !== '' ? $title : (($i + 1) . '회');
    };

    // 명단 테이블 치수 — 2열일 때는 카드 폭이 절반이라 글씨/간격을 줄여 레이아웃이 깨지지 않게 한다.
    $cellTitleSize   = $columns === 1 ? '29px' : '22px'; // 행사명
    $cellDateSize    = $columns === 1 ? '21px' : '16px'; // 날짜
    $cellHeadSize    = $columns === 1 ? '21px' : '17px'; // 남/여 헤더
    $cellNoSize      = $columns === 1 ? '23px' : '18px'; // N호
    $cellInfoSize    = $columns === 1 ? '26px' : '20px'; // 키·직업·나이 (정보가 더 잘 보이도록 상향)
    $cellHeight      = $columns === 1 ? '108px' : '92px'; // 셀 높이 (더 잘 보이도록 상향)
    $cellPadding     = $columns === 1 ? '10px' : '6px';  // 셀 패딩

    // 이용안내 스텝 (유형별) — 번호 카드 그리드로 노출
    $steps = $type === 'solo'
        ? [
            ['icon' => '🎫', 'title' => '입장 & 번호표', 'desc' => '도착하면 나만의 번호표를 받고 지정 자리에 착석'],
            ['icon' => '☕', 'title' => '1:1 로테이션 대화', 'desc' => '한 분씩 마주 앉아 대화하고, 시간이 되면 자리 이동'],
            ['icon' => '🤫', 'title' => '스마트폰으로 투표', 'desc' => '마음에 든 상대를 스마트폰으로 선택하면 끝'],
            ['icon' => '💘', 'title' => '매칭 발표', 'desc' => '로테이션이 끝나고 전원 퇴실하면 10분 이내에 결과 확인'],
            ['icon' => '💌', 'title' => '상대 정보 & 연락', 'desc' => '선택해 준 상대 정보를 폰으로 확인해 연락 이어가기'],
        ]
        : [
            ['icon' => '🎫', 'title' => '입장 & 번호표', 'desc' => '도착하면 나만의 번호표를 받고 지정 테이블에 착석'],
            ['icon' => '🔄', 'title' => '로테이션 대화', 'desc' => '테이블 단위로 만나고, 시간이 되면 자리 이동'],
            ['icon' => '💌', 'title' => '메시지 & 비밀 투표', 'desc' => '대화 중 살짝 메시지, 끝나면 마음에 든 상대 선택'],
            ['icon' => '🍷', 'title' => '자유 대화 (2차)', 'desc' => '마음에 드는 분과 자유롭게 앉아 더 깊은 대화 — 참석은 자율이에요'],
            ['icon' => '💘', 'title' => '매칭 발표', 'desc' => '서로 선택했다면 성사! 결과는 다음 날 오전 발표'],
            ['icon' => '⚠️', 'title' => '파손 주의', 'desc' => '내부 물품의 경우 파손 시 변상 비용이 발생할 수 있으니 주의 바랍니다.'],
        ];

    // 명단 편집기가 그리드를 재생성할 때 쓰는 설정(열수/치수)을 data 속성에 담기 위한 JSON 문자열.
    $rosterConfigJson = json_encode([
        'columns'       => $columns,
        'cardWidth'     => $cardWidth,
        'cellTitleSize' => $cellTitleSize,
        'cellDateSize'  => $cellDateSize,
        'cellHeadSize'  => $cellHeadSize,
        'cellNoSize'    => $cellNoSize,
        'cellInfoSize'  => $cellInfoSize,
        'cellHeight'    => $cellHeight,
        'cellPadding'   => $cellPadding,
        // 성비 자동 맞춤 제거됨: 자동 채움 없음. 부족한 쪽은 '모집중 추가'로 직접 넣는다.
        'balance'       => false,
        'recruitLabel'  => '모집중',
    ], JSON_UNESCAPED_UNICODE);
@endphp
<style>
    @import url('https://fonts.googleapis.com/css2?family=Jua&family=Gothic+A1:wght@400;500;700;800;900&display=swap');
</style>
<div style="font-family:'Gothic A1','Pretendard','Apple SD Gothic Neo',sans-serif; font-weight:800; color:#4b4453; line-height:1.35; width:100%;">

    {{-- 페이지 설정(행사별 남/녀 나이·남/녀 키) 캐리어.
         상세페이지 제작 화면의 "페이지 설정"이 이 요소의 data-detail-settings(JSON = {events:[...]})를 읽고,
         아래 data-setting-block="age|maleHeight|femaleHeight" 컨테이너를 행사 수에 맞춰 다시 그린다.
         샘플을 다시 불러와도 현재 설정이 그대로 따라오도록 쓰이는 숨김 캐리어다. (화면엔 안 보임)
         ※ 비어 있으면 TinyMCE 가 '빈 요소'로 보고 저장 시 통째로 지워 설정이 유실되므로,
            보이지 않는 문자(zero-width space)를 넣어 항상 non-empty 로 유지한다. --}}
    <div data-detail-settings='{{ $settingsJson }}' style="display:none;">&#8203;</div>

    {{-- 최상단 상세 이미지 --}}
    <div style="text-align:center; margin-bottom:36px;">
        <img src="{{ $typeConfig['image'] }}" alt="상세 이미지"
             style="display:block; width:100%; max-width:100%; height:auto; border-radius:12px;">
    </div>

    <div style="text-align:center; margin-bottom:32px;">
        <div style="font-family:'Jua',sans-serif; font-size:64px; font-weight:400; color:#e86aa6; letter-spacing:0.5px;">{{ $typeConfig['title'] }}</div>
        <div style="font-size:34px; color:#9ca3af; margin-top:10px;">{{ $typeConfig['subtitle'] }}</div>
    </div>

    {{-- 오픈 기념 할인 배너 (최상단 강조) --}}
    <div style="background:#fff7ed; border:3px solid #fb923c; border-radius:16px; padding:24px 16px; margin-bottom:16px; text-align:center;">
        <div style="display:inline-block; background:#ea580c; color:#ffffff; border-radius:999px; padding:5px 20px; font-size:28px; font-weight:900; margin-bottom:14px;">🎉 OPEN EVENT</div>
        <div style="font-family:'Jua',sans-serif; font-size:46px; font-weight:400; color:#c2410c; letter-spacing:0.5px; line-height:1.3;">오픈 기념 할인 진행 중!</div>
        <div style="font-size:28px; color:#9a3412; margin-top:14px; line-height:1.6;">지금 신청하시면 <b>오픈 특가</b>로 참여하실 수 있어요.<br>할인 혜택은 <b>조기 마감</b>될 수 있으니 서둘러 주세요 🔥</div>
        <div style="background:#ffedd5; border:2px dashed #fb923c; border-radius:12px; padding:18px 12px; margin-top:18px; font-size:26px; color:#9a3412; line-height:1.7;">
            ⭐ <b>오픈 기념 할인 행사</b>예요!<br>
            할인 혜택 적용을 위해 참여 후
            <span style="background:linear-gradient(transparent 55%, #fdba74 55%); padding:0 4px; font-weight:900; color:#7c2d12;">리뷰 작성이 필수</span>입니다.
        </div>
    </div>

    @if ($type === 'wine')
    {{-- 진정성 어필 배너 (와인파티 전용) — '도파민 만남'이 아닌 소개팅 목적임을 강조 --}}
    <div style="background:#fdf2f8; border:2px solid #f9a8d4; border-radius:16px; padding:28px 20px; margin-bottom:40px; text-align:center;">
        <div style="font-family:'Jua',sans-serif; font-size:40px; font-weight:400; color:#be185d; letter-spacing:0.5px; line-height:1.3;">🍷 진정성을 가지고 좋은 인연을 찾는 자리예요</div>
        <div style="font-size:30px; font-weight:800; color:#4b5563; margin-top:16px; line-height:1.9;">
            로테이블 와인파티는 <b style="color:#be185d; background:linear-gradient(transparent 55%, #fbcfe8 55%); padding:0 4px;">진짜 좋은 인연을 찾는 소개팅</b>이 목적인 자리예요.<br>
            순간의 재미나 자극만 좇는 <b style="background:linear-gradient(transparent 55%, #fbcfe8 55%); padding:0 4px;">단순한 도파민 만남</b>이 아니라,<br>
            서로를 차분히 알아가며 <b style="background:linear-gradient(transparent 55%, #fbcfe8 55%); padding:0 4px;">진정성 있는 대화</b>를 나눌 수 있도록 준비했어요.<br>
            그래서 호스트가 앞에 나와 억지로 게임을 진행하거나 분위기를 띄우지 않아요.
            대신 <b style="background:linear-gradient(transparent 55%, #fbcfe8 55%); padding:0 4px;">가벼운 대화 주제</b>와 <b style="background:linear-gradient(transparent 55%, #fbcfe8 55%); padding:0 4px;">테이블끼리 즐기는 미니게임</b>을 살짝 건네드려,<br>
            어색한 순간을 자연스럽게 풀어가실 수 있도록 도와드립니다.<br>
            <span style="display:inline-block; margin-top:12px; color:#9d174d;">무리한 게임이나 과한 텐션을 요구하는 자리가 아니니,<br>부담없이 편안하게 <b style="background:linear-gradient(transparent 55%, #fbcfe8 55%); padding:0 4px;">이성에 집중</b>하시면 돼요 💘</span>
        </div>
    </div>
    @endif

    {{-- 실시간 명단 업데이트 + 멀티 플랫폼 모집 안내 (마케팅 강조) --}}
    {{-- data-section="roster-intro": 이미지 분할 다운로드에서 "2장(참가자 명단)"의 시작점.
         이 블록부터 명단 그리드까지가 한 장(참가자 명단)으로 묶인다. --}}
    <div data-section="roster-intro" style="background:#ede9fe; border:3px solid #a78bfa; border-radius:16px; padding:24px 18px; margin-bottom:40px; text-align:center;">
        <div style="display:inline-block; background:#7c3aed; color:#ffffff; border-radius:999px; padding:6px 22px; font-size:26px; font-weight:900; margin-bottom:14px;">⚡ LIVE 모집 중</div>
        <div style="font-family:'Jua',sans-serif; font-size:40px; font-weight:400; color:#6d28d9; letter-spacing:0.5px; line-height:1.3;">명단이 적어 보여도 걱정 마세요!</div>
        <div style="font-size:30px; color:#5b21b6; margin-top:14px; line-height:1.7; word-break:keep-all;">
            지금 이 명단은 <b style="color:#6d28d9; background:linear-gradient(transparent 55%, #ddd6fe 55%); padding:0 4px;">전체의 일부</b>일 뿐이에요.<br>
            <b style="color:#6d28d9;">문토 · 프립 등 여러 플랫폼</b>에서 <b style="background:linear-gradient(transparent 55%, #ddd6fe 55%); padding:0 4px;">동시에 모집</b>하고 있어,<br>
            실제 참여 인원은 <b style="color:#be185d; background:linear-gradient(transparent 55%, #fbcfe8 55%); padding:0 4px; font-weight:900;">참여자 명단 보다 많아요.</b>
        </div>
        <div style="background:#ffffff; border:2px dashed #a78bfa; border-radius:14px; padding:18px 14px; margin-top:18px; font-size:27px; color:#5b21b6; line-height:1.7; word-break:keep-all;">
            💜 명단은 <b>신청 현황에 따라 실시간으로 채워지는 중</b>이에요.<br>
            <span data-updated-at="1" style="font-size:24px; color:#7c3aed;">마지막 업데이트 : {{ now()->format('n월 j일 H:i') }} 기준</span>
        </div>
        <div style="font-size:28px; font-weight:800; color:#be185d; margin-top:16px; line-height:1.6; word-break:keep-all;">
            🔥 자리는 <b style="background:linear-gradient(transparent 55%, #fbcfe8 55%); padding:0 4px;">선착순 마감</b>! 망설이면 마감될 수 있어요.<br>
            지금 바로 신청하고 좋은 인연 만나보세요 💘
        </div>
    </div>

    {{-- 행사 그리드 --}}
    {{-- 명단 편집기(상세페이지 제작 화면)가 이 영역을 통째로 재생성한다.
         ROSTER_GRID 마커 사이가 "행사 카드들" 전체이며, data-roster-config 에
         재생성에 필요한 설정(열수/치수)을 담아 둔다. HTML 을 직접 고치지 말 것. --}}
    <!--ROSTER_GRID:start-->
    <div style="display:flex; flex-wrap:wrap;"
         data-roster-grid="1"
         data-roster-config='{{ $rosterConfigJson }}'>
        @foreach ($sampleEvents as $event)
            <div style="width:{{ $cardWidth }}; box-sizing:border-box; padding:3px;"
                 data-roster-card="{{ $loop->index }}"
                 data-event-title="{{ $event['title'] }}"
                 data-event-date="{{ $event['date'] }}">
                <div style="border:2px solid #cbd5e1; border-radius:10px; overflow:hidden; height:100%;">
                    {{-- 행사명 + 날짜 --}}
                    <div style="background:#eef2f7; padding:7px 6px; border-bottom:2px solid #cbd5e1; text-align:center;">
                        <div style="font-size:{{ $cellTitleSize }}; font-weight:900; color:#000000; word-break:keep-all;">{{ $event['title'] }}</div>
                        <div style="font-size:{{ $cellDateSize }}; color:#374151; font-weight:800; margin-top:1px;">{{ $event['date'] }}</div>
                    </div>

                    {{-- 남(좌) / 여(우) 헤더 --}}
                    <div style="display:flex; font-size:{{ $cellHeadSize }}; font-weight:900; text-align:center; border-bottom:2px solid #cbd5e1;">
                        <div style="width:50%; padding:4px; background:#3b82f6; color:#ffffff;">남 {{ count($maleSample) }}</div>
                        <div style="width:50%; padding:4px; background:#ec4899; color:#ffffff;">여 {{ count($femaleSample) }}</div>
                    </div>

                    {{-- 남/여 행 맞춤 --}}
                    <div style="display:flex;">
                        {{-- 좌: 남자 --}}
                        <div style="width:50%; box-sizing:border-box; border-right:2px solid #c7d7ee; background:#eff6ff;">
                            @foreach ($maleSample as $m)
                                <div style="height:{{ $cellHeight }}; padding:{{ $cellPadding }}; border-bottom:2px solid #c7d7ee; text-align:center; display:flex; flex-direction:column; justify-content:center;">
                                    <div style="font-weight:900; font-size:{{ $cellNoSize }}; color:#000000;">{{ $m['no'] }}호</div>
                                    <div style="color:#000000; font-weight:800; font-size:{{ $cellInfoSize }}; margin-top:4px; word-break:keep-all;">{{ $m['height'] }}cm · {{ $m['job'] }} · {{ $m['age'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        {{-- 우: 여자 --}}
                        <div style="width:50%; box-sizing:border-box; background:#fdf2f8;">
                            @foreach ($femaleSample as $f)
                                <div style="height:{{ $cellHeight }}; padding:{{ $cellPadding }}; border-bottom:2px solid #f6c3da; text-align:center; display:flex; flex-direction:column; justify-content:center;">
                                    <div style="font-weight:900; font-size:{{ $cellNoSize }}; color:#000000;">{{ $f['no'] }}호</div>
                                    <div style="color:#000000; font-weight:800; font-size:{{ $cellInfoSize }}; margin-top:4px; word-break:keep-all;">{{ $f['height'] }}cm · {{ $f['job'] }} · {{ $f['age'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <!--ROSTER_GRID:end-->

    {{-- 좌석/성비 안내 --}}
    @if ($type === 'solo')
    <div style="margin-top:20px; background:#eff6ff; border:1px dashed #93c5fd; border-radius:12px; padding:20px 16px; text-align:center; font-size:27px; color:#1d4ed8; line-height:1.7;">
        🪑 자리는 <b>1:1(남 1 · 여 1)</b> 로 테이블마다 한 쌍씩 배정돼요.<br>
        <span style="font-size:24px; color:#2563eb;">정해진 시간마다 자리를 옮겨 여러 상대와 1:1로 대화해요.</span>
    </div>
    @else
    <div style="margin-top:20px; background:#eff6ff; border:1px dashed #93c5fd; border-radius:12px; padding:20px 16px; text-align:center; font-size:27px; color:#1d4ed8; line-height:1.7;">
        🪑 자리는 <b>3:3 · 4:4</b> 등 최대한 <b>성비를 맞춰</b> 배정해요.<br>
        <span style="font-size:24px; color:#2563eb;">다만 당일 참석 현황에 따라 일부 테이블은 3:2 등으로 조정될 수 있어요.</span>
    </div>
    @endif

    {{-- 이전 로테이블 기록 --}}
    <div style="margin-top: 20px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#0ea5e9; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            이전 로테이블 기록
        </div>
        <div style="background:#f0f9ff; padding:22px 18px; text-align:center;">
            {{-- 헤더 --}}
            <div style="display:flex; font-size:28px; font-weight:800; color:#6b7280; border-bottom:2px solid #cbd5e1; padding-bottom:10px; margin-bottom:4px;">
                <div style="width:28%; border-right:1px solid #cbd5e1;">날짜</div>
                <div style="width:27%; border-right:1px solid #cbd5e1;">행사명</div>
                <div style="width:25%; border-right:1px solid #cbd5e1;">인원 (남/여)</div>
                <div style="width:20%;">매칭</div>
            </div>
            {{-- 행사 기록 (더미) --}}
            <div style="display:flex; font-size:29px; color:#374151; padding:14px 0; border-bottom:1px solid #d7e3ef;">
                <div style="width:28%; border-right:1px solid #d7e3ef;">2025.12.20</div>
                <div style="width:27%; border-right:1px solid #d7e3ef;">3회 {{ $typeConfig['eventName'] }}</div>
                <div style="width:25%; border-right:1px solid #d7e3ef;">16명 (8/8)</div>
                <div style="width:20%; color:#be185d; font-weight:800;">4커플</div>
            </div>
            <div style="display:flex; font-size:29px; color:#374151; padding:14px 0; border-bottom:1px solid #d7e3ef;">
                <div style="width:28%; border-right:1px solid #d7e3ef;">2025.11.15</div>
                <div style="width:27%; border-right:1px solid #d7e3ef;">2회 {{ $typeConfig['eventName'] }}</div>
                <div style="width:25%; border-right:1px solid #d7e3ef;">14명 (7/7)</div>
                <div style="width:20%; color:#be185d; font-weight:800;">3커플</div>
            </div>
            <div style="display:flex; font-size:29px; color:#374151; padding:14px 0;">
                <div style="width:28%; border-right:1px solid #d7e3ef;">2025.10.18</div>
                <div style="width:27%; border-right:1px solid #d7e3ef;">1회 {{ $typeConfig['eventName'] }}</div>
                <div style="width:25%; border-right:1px solid #d7e3ef;">18명 (9/9)</div>
                <div style="width:20%; color:#be185d; font-weight:800;">5커플</div>
            </div>
        </div>
    </div>

    {{-- 핵심 매력 카드 그리드 --}}
    <div style="margin:44px 0;">
        <div style="font-family:'Jua',sans-serif; font-size:42px; font-weight:400; color:#ec4899; text-align:center; letter-spacing:0.5px; margin-bottom:24px;">💗 왜 로테이블일까요?</div>
        <div style="display:flex; flex-wrap:wrap;">
            @foreach ($typeConfig['points'] as $pt)
                <div style="width:50%; box-sizing:border-box; padding:10px;">
                    <div style="height:100%; border:1px solid #f3d6e6; border-radius:14px; background:#fdf2f8; padding:24px 16px; text-align:center; box-sizing:border-box;">
                        <div style="font-size:52px; line-height:1;">{{ $pt['icon'] }}</div>
                        <div style="font-size:30px; font-weight:900; color:#be185d; margin-top:14px; word-break:keep-all;">{{ $pt['title'] }}</div>
                        <div style="font-size:28px; font-weight:800; color:#4b5563; margin-top:10px; line-height:1.55; word-break:keep-all;">{{ $pt['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($type === 'wine')
    {{-- 실제 앱 화면 소개 (와인파티 전용) — 참여자가 겪는 흐름 순서로 스샷 노출 --}}
    @php
        // 상세페이지 제작 화면에서 넣은 스샷. 파일이 있는 것만 카드로 노출한다.
        $appShots = [
            ['file' => 'home.png',    'step' => 'STEP 1', 'title' => '내 테이블 · 이번 턴 상대', 'desc' => '지금 내 앞에 누가 앉았는지 폰에 바로! “누구였지?” 걱정 없이 상대 프로필을 확인해요.'],
            ['file' => 'message.png', 'step' => 'STEP 2', 'title' => '익명 메시지',            'desc' => '대화 중 마음이 가는 상대에게 익명 메시지. 부담 없이 먼저 다가갈 수 있어요.'],
            ['file' => 'match.png',   'step' => 'STEP 3', 'title' => '비밀 투표(매칭 선택)',    'desc' => '마음에 든 상대를 티 안 나게 조용히 선택. 100% 비공개라 솔직하게 고를 수 있어요.'],
            ['file' => 'result.png',  'step' => 'STEP 4', 'title' => '매칭 결과 발표',          'desc' => '서로 선택했다면 매칭 성사! 결과 화면으로 설렘을 확인해요. (예시 화면)'],
        ];
        $appShots = array_values(array_filter($appShots, fn ($s) => file_exists(public_path('images/' . $s['file']))));
        $appTones = [
            ['bd' => '#bae6fd', 'bg' => '#f0f9ff', 'badge' => '#0284c7'], // 스카이
            ['bd' => '#fbcfe8', 'bg' => '#fdf2f8', 'badge' => '#db2777'], // 핑크
            ['bd' => '#ddd6fe', 'bg' => '#f5f3ff', 'badge' => '#7c3aed'], // 보라
            ['bd' => '#fde68a', 'bg' => '#fffbeb', 'badge' => '#d97706'], // 앰버
        ];
    @endphp
    @if (count($appShots) > 0)
    <div style="margin-top:4px; margin-bottom:44px;">
        <div style="font-family:'Jua',sans-serif; font-size:42px; font-weight:400; color:#0ea5e9; text-align:center; letter-spacing:0.5px; margin-bottom:8px;">📱 이렇게 진행돼요</div>
        <div style="font-size:30px; color:#6b7280; text-align:center; margin-bottom:24px; line-height:1.6;">
            모든 진행은 <b style="color:#0ea5e9;">스마트폰 하나</b>로! 아래는 <b>실제 앱 화면</b>이에요.<br>
            글작성 없이 터치 몇 번이면 끝, 처음이어도 어렵지 않아요.
        </div>
        <div style="display:flex; flex-wrap:wrap; padding:0 6px;">
            @foreach ($appShots as $idx => $shot)
                @php $tone = $appTones[$idx % count($appTones)]; @endphp
                <div style="width:50%; box-sizing:border-box; padding:18px 14px;">
                    <div style="position:relative; display:flex; flex-direction:column; border:2px solid {{ $tone['bd'] }}; background:{{ $tone['bg'] }}; border-radius:18px; padding:16px; box-sizing:border-box; height:100%;">
                        {{-- 단계 뱃지 --}}
                        <div style="position:absolute; top:-14px; left:16px; background:{{ $tone['badge'] }}; color:#ffffff; font-family:'Jua',sans-serif; font-size:24px; font-weight:400; line-height:1; padding:9px 18px; border-radius:999px; letter-spacing:0.5px;">
                            {{ $shot['step'] }}
                        </div>
                        {{-- 이미지: 고정 비율 박스(9:16)로 감싸 모든 카드의 이미지 영역 높이를 동일하게 맞춘다. --}}
                        {{-- html2canvas 호환을 위해 aspect-ratio 대신 padding-top(177.78%) 비율 트릭 사용. --}}
                        <div style="position:relative; width:100%; padding-top:177.78%; margin-top:12px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; background:#ffffff;">
                            <img src="{{ asset('images/' . $shot['file']) }}" alt="{{ $shot['title'] }}"
                                 style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:contain;">
                        </div>
                        <div style="font-size:30px; font-weight:900; color:#0f172a; margin-top:16px; text-align:center; word-break:keep-all;">{{ $shot['title'] }}</div>
                        <div style="font-size:26px; font-weight:800; color:#4b5563; margin-top:10px; line-height:1.55; text-align:center; word-break:keep-all;">{{ $shot['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif

    @if ($type === 'solo')
    {{-- 실제 앱 화면 소개 (1:1 소개팅 전용) — 참여자가 겪는 흐름 순서로 스샷 노출 --}}
    @php
        $soloShots = [
            ['file' => 'solo_home.png',  'step' => 'STEP 1', 'title' => '내 상대 · 현재 자리', 'desc' => '지금 내 앞에 앉은 상대가 누구인지 폰에 바로! 상대 프로필을 확인하며 1:1 대화에 집중해요.'],
            ['file' => 'solo_match.png', 'step' => 'STEP 2', 'title' => '비밀 투표(매칭 선택)', 'desc' => '마음에 든 상대를 티 안 나게 조용히 선택. 100% 비공개라 솔직하게 고를 수 있어요.'],
        ];
        $soloShots = array_values(array_filter($soloShots, fn ($s) => file_exists(public_path('images/' . $s['file']))));
        $soloTones = [
            ['bd' => '#bae6fd', 'bg' => '#f0f9ff', 'badge' => '#0284c7'], // 스카이
            ['bd' => '#fbcfe8', 'bg' => '#fdf2f8', 'badge' => '#db2777'], // 핑크
        ];
    @endphp
    @if (count($soloShots) > 0)
    <div style="margin-top:4px; margin-bottom:44px;">
        <div style="font-family:'Jua',sans-serif; font-size:42px; font-weight:400; color:#0ea5e9; text-align:center; letter-spacing:0.5px; margin-bottom:8px;">📱 이렇게 진행돼요</div>
        <div style="font-size:30px; color:#6b7280; text-align:center; margin-bottom:24px; line-height:1.6;">
            모든 진행은 <b style="color:#0ea5e9;">스마트폰 하나</b>로! 아래는 <b>실제 앱 화면</b>이에요.<br>
            글작성 없이 터치 몇 번이면 끝, 처음이어도 어렵지 않아요.
        </div>
        <div style="display:flex; flex-wrap:wrap; padding:0 6px;">
            @foreach ($soloShots as $idx => $shot)
                @php $tone = $soloTones[$idx % count($soloTones)]; @endphp
                <div style="width:50%; box-sizing:border-box; padding:18px 14px;">
                    <div style="position:relative; display:flex; flex-direction:column; border:2px solid {{ $tone['bd'] }}; background:{{ $tone['bg'] }}; border-radius:18px; padding:16px; box-sizing:border-box; height:100%;">
                        {{-- 단계 뱃지 --}}
                        <div style="position:absolute; top:-14px; left:16px; background:{{ $tone['badge'] }}; color:#ffffff; font-family:'Jua',sans-serif; font-size:24px; font-weight:400; line-height:1; padding:9px 18px; border-radius:999px; letter-spacing:0.5px;">
                            {{ $shot['step'] }}
                        </div>
                        {{-- 이미지: 고정 비율 박스(9:16). html2canvas 호환 위해 padding-top 비율 트릭 사용. --}}
                        <div style="position:relative; width:100%; padding-top:177.78%; margin-top:12px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; background:#ffffff;">
                            <img src="{{ asset('images/' . $shot['file']) }}" alt="{{ $shot['title'] }}"
                                 style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:contain;">
                        </div>
                        <div style="font-size:30px; font-weight:900; color:#0f172a; margin-top:16px; text-align:center; word-break:keep-all;">{{ $shot['title'] }}</div>
                        <div style="font-size:26px; font-weight:800; color:#4b5563; margin-top:10px; line-height:1.55; text-align:center; word-break:keep-all;">{{ $shot['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif

    @if ($type === 'wine')
    {{-- 미니게임 콘텐츠 소개 (와인파티 전용) --}}
    <div style="margin-top:4px; margin-bottom:44px;">
        <div style="font-family:'Jua',sans-serif; font-size:42px; font-weight:400; color:#7c3aed; text-align:center; letter-spacing:0.5px; margin-bottom:8px;">🎮 대화가 어색할 틈이 없어요</div>
        <div style="font-size:30px; color:#6b7280; text-align:center; margin-bottom:24px; line-height:1.6;">
            로테이션 중, 같은 테이블끼리 즐기는 <b style="color:#7c3aed;">미니게임</b>을 바로!<br>
            처음 만난 사이도 금세 웃음꽃이 피는 아이스브레이킹 콘텐츠예요.
        </div>

        {{-- 게임 이미지 (존재하는 game1~6.png 를 카드화해 또렷이 구분) --}}
        @php
            $gameImgs = [];
            foreach (range(1, 6) as $n) {
                if (file_exists(public_path("images/game{$n}.png"))) {
                    $gameImgs[] = asset("images/game{$n}.png");
                }
            }
            // 카드별로 번갈아 쓰는 포인트 색 (테두리/뱃지 배경)
            $gameTones = [
                ['bd' => '#c4b5fd', 'bg' => '#f5f3ff', 'badge' => '#7c3aed'], // 보라
                ['bd' => '#fbcfe8', 'bg' => '#fdf2f8', 'badge' => '#db2777'], // 핑크
                ['bd' => '#fde68a', 'bg' => '#fffbeb', 'badge' => '#d97706'], // 앰버
                ['bd' => '#bae6fd', 'bg' => '#f0f9ff', 'badge' => '#0284c7'], // 스카이
                ['bd' => '#bbf7d0', 'bg' => '#f0fdf4', 'badge' => '#16a34a'], // 그린
                ['bd' => '#fecaca', 'bg' => '#fef2f2', 'badge' => '#dc2626'], // 레드
            ];
        @endphp
        @if (count($gameImgs) > 0)
            <div style="display:flex; flex-wrap:wrap; padding:0 6px; margin-bottom:24px;">
                @foreach ($gameImgs as $idx => $src)
                    @php $tone = $gameTones[$idx % count($gameTones)]; @endphp
                    <div style="width:50%; box-sizing:border-box; padding:18px 14px;">
                        <div style="position:relative; border:2px solid {{ $tone['bd'] }}; background:{{ $tone['bg'] }}; border-radius:18px; padding:16px; box-sizing:border-box; box-shadow:0 6px 18px -8px rgba(0,0,0,0.18);">
                            {{-- 번호 뱃지 --}}
                            <div style="position:absolute; top:-14px; left:16px; background:{{ $tone['badge'] }}; color:#ffffff; font-family:'Jua',sans-serif; font-size:26px; font-weight:400; line-height:1; padding:9px 18px; border-radius:999px; box-shadow:0 4px 10px -3px rgba(0,0,0,0.3); letter-spacing:0.5px;">
                                GAME {{ $idx + 1 }}
                            </div>
                            <img src="{{ $src }}" alt="미니게임 예시 {{ $idx + 1 }}"
                                 style="display:block; width:100%; height:auto; border-radius:12px; margin-top:10px;">
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 게임 종류 카드 --}}
        <div style="display:flex; flex-wrap:wrap;">
            @php
                $games = [
                    ['icon' => '🙋', 'title' => 'Yes or No',   'desc' => '같은 테이블끼리 익명으로 예/아니오 투표! 결과가 실시간으로 집계돼요'],
                    ['icon' => '🍯', 'title' => '순한맛 이미지게임', 'desc' => '“~한 사람?” 질문에 어울리는 이성을 다같이 지목하는 가벼운 몸풀기'],
                    ['icon' => '🌶️', 'title' => '매운맛 이미지게임', 'desc' => '조금 더 아찔하고 솔직한 질문으로 분위기를 확 달궈요'],
                    ['icon' => '⚖️', 'title' => '밸런스 게임',   'desc' => 'A vs B 둘 중 끌리는 쪽을 엄지로! 서로의 연애관·취향을 알아가요'],
                ];
            @endphp
            @foreach ($games as $g)
                <div style="width:50%; box-sizing:border-box; padding:10px;">
                    <div style="height:100%; border:1px solid #ddd6fe; border-radius:14px; background:#f5f3ff; padding:24px 16px; text-align:center; box-sizing:border-box;">
                        <div style="font-size:52px; line-height:1;">{{ $g['icon'] }}</div>
                        <div style="font-size:30px; font-weight:900; color:#6d28d9; margin-top:14px; word-break:keep-all;">{{ $g['title'] }}</div>
                        <div style="font-size:27px; font-weight:800; color:#4b5563; margin-top:10px; line-height:1.55; word-break:keep-all;">{{ $g['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="background:#f5f3ff; border:1px dashed #c4b5fd; border-radius:12px; padding:18px 14px; margin-top:16px; text-align:center; font-size:26px; color:#6d28d9; line-height:1.6;">
            🍷 게임 벌칙은 테이블끼리 자유롭게! 가볍게 한 잔으로 분위기를 더 띄워 보세요
        </div>
    </div>
    @endif

    {{-- 파티 진행시간 안내 (와인파티 전용 — 1:1 은 1/2차 구분이 없어 제외) --}}
    @if ($type !== 'solo')
    <div style="margin-top:48px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#7c3aed; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            🕖 파티 진행시간
        </div>
        <div style="background:#f5f3ff; padding:24px 14px;">
            <div style="display:flex; align-items:stretch;">
                {{-- 1차 --}}
                <div style="width:50%; box-sizing:border-box; padding:0 8px;">
                    <div style="height:100%; background:#ffffff; border:2px solid #c4b5fd; border-radius:14px; padding:24px 14px; text-align:center;">
                        <div style="display:inline-block; background:#7c3aed; color:#ffffff; border-radius:999px; padding:4px 18px; font-size:26px; font-weight:900;">1차</div>
                        <div style="font-family:'Jua',sans-serif; font-size:44px; font-weight:400; color:#5b21b6; margin-top:14px;">19:30</div>
                        <div style="font-size:26px; color:#6b7280; margin-top:10px; line-height:1.6;">로테이션 대화<br>+ 비밀 투표</div>
                    </div>
                </div>
                {{-- 2차 --}}
                <div style="width:50%; box-sizing:border-box; padding:0 8px;">
                    <div style="height:100%; background:#ffffff; border:2px solid #f9a8d4; border-radius:14px; padding:24px 14px; text-align:center;">
                        <div style="display:inline-block; background:#ec4899; color:#ffffff; border-radius:999px; padding:4px 18px; font-size:26px; font-weight:900;">2차</div>
                        <div style="font-family:'Jua',sans-serif; font-size:44px; font-weight:400; color:#be185d; margin-top:14px;">~ 00:00</div>
                        <div style="font-size:26px; color:#6b7280; margin-top:10px; line-height:1.6;">자유 대화 시간<br><b style="color:#be185d;">자율 참석 · 추가 비용 무료</b></div>
                    </div>
                </div>
            </div>
            <div style="text-align:center; font-size:25px; color:#7c3aed; margin-top:18px;">※ 진행시간은 당일 상황에 따라 소폭 변동될 수 있어요</div>
        </div>
    </div>
    @endif

    {{-- 1:1 소개팅 전용 — 상대별 대화 시간 안내 --}}
    @if ($type === 'solo')
    <div style="margin-top:48px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#0ea5e9; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            ⏱️ 상대별 대화 시간
        </div>
        <div style="background:#f0f9ff; padding:26px 16px; text-align:center; font-size:31px; color:#0c4a6e; line-height:1.9;">
            한 상대와의 1:1 대화는 <b style="color:#0284c7;">약 10~15분</b>씩 주어져요.<br>
            <span style="font-size:28px; color:#0369a1;">대화 시간은 <b>그날 참여 인원</b>에 따라 조정되며, 상황에 따라 <b>소폭 변경</b>될 수 있어요.</span>
        </div>
    </div>
    @endif

    {{-- 시간 준수 안내 (와인·1:1 공통) --}}
    <div style="margin-top:24px; background:#fff7ed; border:2px dashed #fb923c; border-radius:12px; padding:22px 16px; text-align:center; font-size:30px; color:#9a3412; line-height:1.8;">
        🙏 <b>원활한 진행을 위해 시간 준수</b>를 꼭 부탁드려요!<br>
        <span style="font-size:27px; color:#c2410c;">정해진 자리 이동·투표 시간을 함께 지켜 주셔야 모두가<br>더 많은 분과 즐겁게 대화할 수 있어요.</span>
    </div>

    {{-- 참여 조건 --}}
    <div style="margin-top:48px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#3b82f6; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            참여 조건
        </div>
        <div style="background:#eff6ff; padding:22px 14px; text-align:center; font-size:31px; color:#374151; line-height:2.2;">
            {{-- 나이 조건: 행사별로 한 줄씩 노출 (행사 2개 이상이면 "N회 ·" 라벨을 앞에 붙인다).
                 JS 가 이 컨테이너(data-setting-block="age") 안을 행사 수에 맞춰 다시 그린다. --}}
            <div data-setting-block="age">
                @foreach ($eventSettings as $i => $es)
                    <div>- {{ $multiEvent ? $eventLabel($i) . ' · ' : '' }}남성 {{ $es['maleAgeText'] }} / 여성 {{ $es['femaleAgeText'] }}</div>
                @endforeach
            </div>
            <div>- 미혼이신 분 (돌싱·기혼은 참여가 어려워요)</div>
            <div>- 안정적인 직업과 경제력을 갖추신 분 (신분·재직 확인에 협조해 주세요)</div>
            <div>- 진지하게 좋은 인연을 찾고 싶으신 분</div>
            <div>- 신분 확인(신분증)에 동의해 주실 수 있는 분</div>
            <div>- 서로를 존중하며 매너 있게 대화할 수 있는 분</div>
        </div>
        {{-- 남성 자격요건 / 우선 승인 안내 --}}
        <div style="background:#dbeafe; border-top:2px solid #bfdbfe; padding:22px 14px; text-align:center; font-size:30px; color:#1e3a8a; line-height:2.0;">
            <div style="font-weight:900; font-size:33px; margin-bottom:12px;">👔 남성 참가 자격요건</div>
            {{-- 남성 키: 행사별로 한 줄씩. (행사 2개 이상이면 "N회 ·" 라벨) --}}
            <div data-setting-block="maleHeight">
                @foreach ($eventSettings as $i => $es)
                    {{-- 남성 키는 비어도 기본 173cm 로 항상 노출한다. --}}
                    <div>- {{ $multiEvent ? $eventLabel($i) . ' · ' : '' }}신장 <b>{{ $es['maleHeight'] !== '' ? $es['maleHeight'] : '173cm' }} 이상</b></div>
                @endforeach
            </div>
            <div>- <b>대기업 · 공기업 · 전문직</b> <b>우선 승인</b></div>
            <div>- 공무원 · 교사 · 금융권 · 사업체 운영 · 사무직 등 안정적인 전문 직군 우대</div>
            <div style="background:#bfdbfe; border-radius:10px; padding:16px 10px; margin-top:16px; font-size:28px; color:#1e3a8a; line-height:1.6;">
                📇 <b>남성분은 직업 확인이 가능한 서류</b>(명함 또는 재직증명서 등)를<br>
                <b style="background:linear-gradient(transparent 55%, #93c5fd 55%); padding:0 4px;">현장에 꼭 지참</b>해 주세요!
            </div>
            <div style="color:#2563eb; font-size:27px; margin-top:14px; line-height:1.5;">※ 자격요건은 신청자 현황에 따라 조정될 수 있으며, 재직·신분 확인에 협조해 주셔야 해요.</div>
        </div>
        {{-- 여성 자격요건 (외적 요소 위주) --}}
        <div style="background:#fce7f3; border-top:2px solid #f9a8d4; padding:22px 14px; text-align:center; font-size:30px; color:#9d174d; line-height:2.0;">
            <div style="font-weight:900; font-size:33px; margin-bottom:12px;">💄 여성 참가 자격요건</div>
            {{-- 여성 키: 입력된 행사만 한 줄씩 노출 (전부 비면 아무 줄도 없음). --}}
            <div data-setting-block="femaleHeight">
                @foreach ($eventSettings as $i => $es)
                    @if ($es['femaleHeight'] !== '')
                        <div>- {{ $multiEvent ? $eventLabel($i) . ' · ' : '' }}신장 <b>{{ $es['femaleHeight'] }} 이상</b></div>
                    @endif
                @endforeach
            </div>
            <div>- 밝고 호감 가는 <b>단정한 외모</b>와 분위기를 지니신 분</div>
            <div>- 자기관리가 잘 되어 있고 <b>스타일 감각</b>이 있으신 분</div>
            <div>- 긍정적이고 <b>대화 매너</b>가 좋은 분</div>
            <div>- 안정적으로 <b>경제 활동</b>을 하고 계신 분</div>
        </div>
    </div>

    {{-- 로테이블 이용안내 --}}
    <div style="margin-top:48px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#10b981; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            로테이블 이용안내
        </div>
        <div style="background:#ecfdf5; padding:22px 14px;">
            @foreach ($steps as $i => $step)
                <div style="display:flex; align-items:center; background:#ffffff; border:1px solid #d1fae5; border-radius:14px; padding:20px 18px; margin-bottom:{{ $loop->last ? '0' : '16px' }};">
                    {{-- 번호 뱃지 --}}
                    <div style="flex:0 0 auto; width:64px; height:64px; border-radius:50%; background:#10b981; color:#ffffff; font-family:'Jua',sans-serif; font-weight:400; font-size:38px; line-height:64px; text-align:center;">{{ $i + 1 }}</div>
                    {{-- 아이콘 --}}
                    <div style="flex:0 0 auto; font-size:46px; margin:0 18px;">{{ $step['icon'] }}</div>
                    {{-- 내용 --}}
                    <div style="flex:1 1 auto; text-align:left;">
                        <div style="font-size:31px; font-weight:900; color:#065f46; word-break:keep-all;">{{ $step['title'] }}</div>
                        <div style="font-size:29px; font-weight:800; color:#4b5563; margin-top:8px; line-height:1.55; word-break:keep-all;">{{ $step['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($type === 'solo')
    {{-- 1:1 소개팅 연락처 공개 유의 안내 --}}
    <div style="margin-top:48px; border:2px solid #fca5a5; border-radius:12px; overflow:hidden;">
        <div style="background:#ef4444; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            ⚠️ 매칭 선택 전 유의사항
        </div>
        <div style="background:#fef2f2; padding:26px 16px; font-size:31px; color:#b91c1c; line-height:1.9; text-align:center;">
            내가 선택한 상대에게는 <b>서로 매칭되지 않더라도</b><br>
            내 연락처가 공개될 수 있어요.<br>
            마음에 드는 분을 <b>신중하게</b> 선택해 주세요 🙏
        </div>
    </div>
    @endif

    {{-- 자주 묻는 질문 --}}
    @if ($type === 'solo')
    <div style="margin-top:48px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#f59e0b; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            자주 묻는 질문
        </div>
        <div style="background:#fffbeb; padding:24px 16px; font-size:31px; color:#374151; line-height:1.8; text-align:center;">
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 혼자 가도 괜찮나요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">그럼요, 대부분 혼자 오세요 😊 한 분씩 1:1로 마주 앉아 대화하고, 시간이 되면 자리를 옮기는 방식이라 자연스럽게 모든 상대와 대화하게 돼요. 진행은 스마트폰 안내를 따라가면 되니 편하게 오세요.</div>
            </div>
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 매칭 결과는 언제 알 수 있나요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">로테이션이 모두 끝나고 <b>전원 퇴실하면 10분 이내</b>에 결과가 발표돼요. 기다릴 필요 없이 그날 바로 확인하실 수 있어요!</div>
            </div>
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 내 연락처가 공개되나요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">내가 <b>선택한 상대</b>에게는 서로 매칭되지 않더라도 내 연락처가 공개될 수 있어요. 마음에 드는 분을 <b>신중하게</b> 선택해 주세요 🙏</div>
            </div>
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 주차가 되나요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">아쉽지만 전용 주차 공간은 없어요 🙏<br>선릉역에서 도보 3분 거리라 접근성이 좋으니<br>가급적 대중교통 이용을 부탁드려요!</div>
            </div>
            <div>
                <div style="font-weight:900;">Q. 개인정보는 안전한가요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">네, 안심하셔도 돼요! 🤍<br>투표 결과와 연락처는 선택·매칭된 상대에게만 공유되고,<br>그 외에는 어디에도 공개되지 않아요.<br>소중한 개인정보는 저희가 책임지고 안전하게 지켜드릴게요.</div>
            </div>
        </div>
    </div>
    @else
    <div style="margin-top:48px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#f59e0b; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            자주 묻는 질문
        </div>
        <div style="background:#fffbeb; padding:24px 16px; font-size:31px; color:#374151; line-height:1.8; text-align:center;">
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 혼자 가도 괜찮나요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">그럼요, 전혀 걱정하지 않으셔도 돼요! 😊 사실 대부분의 분들이 혼자 오신답니다. 정해진 로테이션 순서와 대화 주제·미니게임이 자연스럽게 분위기를 이끌어 주고, 모든 진행이 스마트폰 안내를 따라 흘러가니 편한 마음으로 오세요.</div>
            </div>
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 술을 못 마시면 어떡해요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">걱정 마세요! 술은 전혀 필수가 아니랍니다 🙂<br>와인 대신 음료도 준비되어 있으니 편하게 즐기시면 돼요.<br>중요한 건 술이 아니라 서로를 알아가는 즐거운 대화니까요.<br>못 마셔도 충분히 즐겁게 참여하실 수 있어요!</div>
            </div>
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 친구랑 같이 앉을 수 있나요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">함께 오시는 건 언제나 환영이에요! 👯<br>다만 로테이션 특성상 자리는 서로 다른 테이블로 배정될 수 있어요. 더 많은 분과 새로운 인연을 만나실 수 있도록 돕기 위함이니 이해 부탁드려요. 행사가 끝난 뒤에는 다시 만나 후기를 나누는 재미도 쏠쏠하답니다 😉</div>
            </div>
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 주차가 되나요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">아쉽지만 전용 주차 공간은 없어요 🙏<br>선릉역에서 도보 3분 거리라 접근성이 좋으니<br>가급적 대중교통 이용을 부탁드려요!</div>
            </div>
            <div>
                <div style="font-weight:900;">Q. 개인정보는 안전한가요?</div>
                <div style="color:#4b5563; font-weight:800; margin-top:6px;">네, 안심하셔도 돼요! 🤍<br>투표 결과와 연락처는 서로 매칭된 상대에게만 공유되고,<br>그 외에는 어디에도 공개되지 않아요.<br>소중한 개인정보는 저희가 책임지고 안전하게 지켜드릴게요.</div>
            </div>
        </div>
    </div>
    @endif

    {{-- 마감 임박 CTA 배너 --}}
    <div style="margin-top:48px; border-radius:16px; overflow:hidden; background:linear-gradient(135deg,#f43f5e,#ec4899,#8b5cf6); padding:36px 20px; text-align:center; color:#ffffff;">
        <div style="display:inline-block; background:rgba(255,255,255,0.22); border-radius:999px; padding:6px 18px; font-size:26px; font-weight:800; margin-bottom:16px;">🔥 오픈 기념 할인 · 마감 임박</div>
        <div style="font-family:'Jua',sans-serif; font-size:48px; font-weight:400; letter-spacing:0.5px; line-height:1.3;">지금 바로 신청하세요!</div>
        <div style="font-size:29px; margin-top:14px; opacity:0.95; line-height:1.6;">오픈 특가는 조기 마감될 수 있어요.<br>좋은 인연, 망설이는 사이 자리가 찹니다 💘</div>
    </div>
</div>
