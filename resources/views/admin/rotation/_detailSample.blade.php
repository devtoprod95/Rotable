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
            'image'    => file_exists(public_path('images/detail_wine.png'))
                ? asset('images/detail_wine.png')
                : asset('images/detail_img.png'),
            'title'    => 'ROTABLE 와인파티 안내',
            'subtitle' => '이번 회차 참여자를 확인하세요',
            'eventName'=> '와인파티',
            // 핵심 매력 — 아이콘 카드 그리드로 노출 (icon / title / desc)
            'points'   => [
                ['icon' => '📍', 'title' => '선릉역 도보 4분', 'desc' => '접근성 좋은 역세권에서 진행돼요'],
                ['icon' => '🍷', 'title' => '와인 무제한', 'desc' => '다양한 와인을 무제한으로 즐겨요'],
                ['icon' => '🧀', 'title' => '핑거푸드 무제한', 'desc' => '맛있는 핑거푸드를 마음껏 드세요'],
                ['icon' => '🚻', 'title' => '남녀 화장실 분리', 'desc' => '남녀 화장실이 따로 준비되어 있어요'],
                ['icon' => '✍️', 'title' => '글작성 X', 'desc' => '종이·설문 없이 스마트폰 터치 몇 번이면 끝'],
                ['icon' => '📱', 'title' => '상대 정보 다시보기', 'desc' => '"누구였지?" 걱정 끝, 폰에서 바로 확인'],
                ['icon' => '📊', 'title' => '받은 표 수 확인', 'desc' => '결과 발표 때 내가 몇 표 받았는지 공개'],
                ['icon' => '🎉', 'title' => '2차 비용 무료', 'desc' => '자유 대화 시간, 추가 부담 없이 즐겨요'],
                ['icon' => '🤫', 'title' => '100% 비밀 투표', 'desc' => '매칭된 상대에게만 살짝 공유돼요'],
                ['icon' => '💬', 'title' => '대화 주제 제공', 'desc' => '가벼운 대화 주제를 드려 처음에도 어색하지 않아요'],
                ['icon' => '🎮', 'title' => '미니게임 제공', 'desc' => '테이블끼리 즐기는 미니게임으로 어색함을 날려요'],
                ['icon' => '💘', 'title' => '다음 날 오전 발표', 'desc' => '서로 선택하면 매칭 성사!'],
            ],
        ],
        'solo' => [
            'image'    => file_exists(public_path('images/detail_solo.png'))
                ? asset('images/detail_solo.png')
                : asset('images/detail_img.png'),
            'title'    => 'ROTABLE 1:1 소개팅 안내',
            'subtitle' => '이번 회차 참여자를 확인하세요',
            'eventName'=> '1:1 소개팅',
            // 핵심 매력 — 아이콘 카드 그리드로 노출 (icon / title / desc)
            'points'   => [
                ['icon' => '📍', 'title' => '선릉역 도보 4분', 'desc' => '접근성 좋은 역세권에서 진행돼요'],
                ['icon' => '⏱️', 'title' => '현장 바로 발표', 'desc' => '끝나면 그 자리에서 매칭 결과 확인'],
                ['icon' => '✍️', 'title' => '글작성 X', 'desc' => '종이·설문 없이 스마트폰 터치 몇 번이면 끝'],
                ['icon' => '📱', 'title' => '상대 정보 다시보기', 'desc' => '"누구였지?" 걱정 끝, 폰에서 바로 확인'],
                ['icon' => '💌', 'title' => '연락처 공유', 'desc' => '선택해 준 상대 정보로 편하게 연락 이어가기'],
                ['icon' => '📊', 'title' => '받은 표 수 확인', 'desc' => '결과 발표 때 내가 몇 표 받았는지 공개'],
                ['icon' => '🎉', 'title' => '2차 비용 무료', 'desc' => '자유 대화 시간, 추가 부담 없이 즐겨요'],
                ['icon' => '💬', 'title' => '대화 주제 제공', 'desc' => '가벼운 대화 주제를 드려 처음에도 어색하지 않아요'],
                ['icon' => '🤍', 'title' => '100% 비밀 투표', 'desc' => '서로 선택한 경우에만 매칭 사실 공유'],
            ],
        ],
    ][$type];

    // 더미 참여자 (남 5 / 여 5) — 이름은 N호로 표기
    $maleSample = [
        ['no' => 1, 'height' => 177, 'job' => '쇼핑몰',   'age' => '30대 초반'],
        ['no' => 2, 'height' => 181, 'job' => 'IT개발자', 'age' => '30대 초반'],
        ['no' => 3, 'height' => 183, 'job' => '마케터',   'age' => '30대 중반'],
        ['no' => 4, 'height' => 175, 'job' => '회사원',   'age' => '20대 중반'],
        ['no' => 5, 'height' => 182, 'job' => '공무원',    'age' => '30대 초반'],
    ];
    $femaleSample = [
        ['no' => 1, 'height' => 168, 'job' => '간호사',   'age' => '20대 후반'],
        ['no' => 2, 'height' => 160, 'job' => '사업가',   'age' => '30대 초반'],
        ['no' => 3, 'height' => 167, 'job' => '공무원',   'age' => '30대 초반'],
        ['no' => 4, 'height' => 158, 'job' => '디자이너', 'age' => '30대 중반'],
        ['no' => 5, 'height' => 169, 'job' => '승무원',   'age' => '30대 초반'],
    ];

    // 더미 행사
    $sampleEvents = [
        ['title' => '1회 ' . $typeConfig['eventName'], 'date' => '2026.11.07(토) 19:30'],
        ['title' => '2회 ' . $typeConfig['eventName'], 'date' => '2026.12.14(토) 19:30'],
    ];
    if ($columns === 1) {
        $sampleEvents = array_slice($sampleEvents, 0, 1);
    }

    // 명단 테이블 치수 — 2열일 때는 카드 폭이 절반이라 글씨/간격을 줄여 레이아웃이 깨지지 않게 한다.
    $cellTitleSize   = $columns === 1 ? '29px' : '22px'; // 행사명
    $cellDateSize    = $columns === 1 ? '21px' : '16px'; // 날짜
    $cellHeadSize    = $columns === 1 ? '21px' : '17px'; // 남/여 헤더
    $cellNoSize      = $columns === 1 ? '23px' : '18px'; // N호
    $cellInfoSize    = $columns === 1 ? '19px' : '14px'; // 키·직업·나이
    $cellHeight      = $columns === 1 ? '84px' : '70px'; // 셀 높이
    $cellPadding     = $columns === 1 ? '10px' : '6px';  // 셀 패딩

    // 이용안내 스텝 (유형별) — 번호 카드 그리드로 노출
    $steps = $type === 'solo'
        ? [
            ['icon' => '🎫', 'title' => '입장 & 닉네임', 'desc' => '도착하면 나만의 닉네임을 받고 지정 자리에 착석'],
            ['icon' => '☕', 'title' => '1:1 로테이션 대화', 'desc' => '한 분씩 마주 앉아 대화하고, 시간이 되면 자리 이동'],
            ['icon' => '🤫', 'title' => '스마트폰 비밀 투표', 'desc' => '마음에 든 상대를 조용히·비공개로 선택하면 끝'],
            ['icon' => '💘', 'title' => '바로 매칭 발표', 'desc' => '끝나면 그 자리에서 결과 확인, 기다릴 필요 없어요'],
            ['icon' => '💌', 'title' => '상대 정보 & 연락', 'desc' => '선택해 준 상대 정보를 폰으로 확인해 연락 이어가기'],
        ]
        : [
            ['icon' => '🎫', 'title' => '입장 & 닉네임', 'desc' => '도착하면 나만의 닉네임을 받고 지정 테이블에 착석'],
            ['icon' => '🔄', 'title' => '로테이션 대화', 'desc' => '테이블 단위로 만나고, 시간이 되면 자리 이동'],
            ['icon' => '💌', 'title' => '메시지 & 비밀 투표', 'desc' => '대화 중 살짝 메시지, 끝나면 마음에 든 상대 선택'],
            ['icon' => '🍷', 'title' => '자유 대화 (2차)', 'desc' => '마음에 드는 분과 자유롭게 앉아 더 깊은 대화'],
            ['icon' => '💘', 'title' => '매칭 발표', 'desc' => '서로 선택했다면 성사! 결과는 다음 날 오전 발표'],
            ['icon' => '⚠️', 'title' => '파손 주의', 'desc' => '내부 물품의 경우 파손 시 변상 비용이 발생할 수 있으니 주의 바랍니다.'],
        ];
@endphp
<style>
    @import url('https://fonts.googleapis.com/css2?family=Jua&family=Gothic+A1:wght@400;500;700;800;900&display=swap');
</style>
<div style="font-family:'Gothic A1','Pretendard','Apple SD Gothic Neo',sans-serif; font-weight:700; color:#4b4453; line-height:1.35; width:100%;">

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

    {{-- 한 줄 요약 배너 (후킹) --}}
    <div style="background:linear-gradient(135deg,#ec4899,#8b5cf6); border-radius:14px; padding:28px 20px; margin-top:20px; margin-bottom:40px; text-align:center; color:#ffffff;">
        <div style="font-family:'Jua',sans-serif; font-size:40px; font-weight:400; letter-spacing:0.5px;">선릉역 4분 · 글작성 X · 2차 무료</div>
        <div style="font-size:32px; font-weight:800; margin-top:8px; opacity:0.98; line-height:1.7;">
            지금까지 소개팅에서 프로필을 직접 적고,<br>
            상대방 이름과 정보를 외우느라 힘드셨죠?<br>
            로테이블은 그 모든 과정을 <b>스마트폰 하나로</b> 해결했어요.<br>
            내 프로필 등록부터 상대 정보 확인, 마음에 드는 분 선택,<br>
            그리고 매칭 결과까지 — 전부 폰 안에서 <b>간편하게</b> 진행됩니다.<br>
            복잡한 절차와 어색한 순간은 덜어내고, 대화에만 집중하실 수 있도록<br>
            <b>깔끔하고 세련된 방식</b>으로 준비했어요.<br>
            물론 <b>2차까지 전부 무료</b>랍니다 💘<br>
            <span style="display:inline-block; margin-top:10px;">🔋 모든 진행이 스마트폰으로 이뤄지니, <b>배터리는 꼭 넉넉히 충전</b>해서 와 주세요!</span>
        </div>
    </div>

    {{-- 실시간 명단 업데이트 안내 --}}
    <div style="background:#ede9fe; border:2px solid #c4b5fd; border-radius:12px; padding:18px 16px; margin-bottom:40px; text-align:center;">
        <div style="font-family:'Jua',sans-serif; font-size:35px; font-weight:400; color:#7e22ce; letter-spacing:0.5px;">실시간 명단 업데이트 중</div>
        <div style="font-size:29px; color:#6b21a8; margin-top:10px; line-height:1.5;">
            아래 참여자 명단은 신청 현황에 따라 수시로 갱신됩니다.<br>
            <b>마지막 업데이트: {{ now()->format('n월 j일 H:i') }}</b> 기준
        </div>
        <div style="font-size:26px; color:#7c3aed; margin-top:14px; padding-top:14px; border-top:1px dashed #c4b5fd; line-height:1.6;">
            📢 <b>여러 플랫폼에서 함께 모집 중</b>이에요!<br>
            아래 명단이 전부가 아니며, 실제 참여 인원은 더 많을 수 있어요.
        </div>
    </div>

    {{-- 행사 그리드 --}}
    <div style="display:flex; flex-wrap:wrap;">
        @foreach ($sampleEvents as $event)
            <div style="width:{{ $cardWidth }}; box-sizing:border-box; padding:3px;">
                <div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; height:100%;">
                    {{-- 행사명 + 날짜 --}}
                    <div style="background:#eef2f7; padding:7px 6px; border-bottom:1px solid #e5e7eb; text-align:center;">
                        <div style="font-size:{{ $cellTitleSize }}; font-weight:900; word-break:keep-all;">{{ $event['title'] }}</div>
                        <div style="font-size:{{ $cellDateSize }}; color:#6b7280; margin-top:1px;">{{ $event['date'] }}</div>
                    </div>

                    {{-- 남(좌) / 여(우) 헤더 --}}
                    <div style="display:flex; font-size:{{ $cellHeadSize }}; font-weight:900; text-align:center; border-bottom:1px solid #eee;">
                        <div style="width:50%; padding:4px; background:#3b82f6; color:#ffffff;">남 {{ count($maleSample) }}</div>
                        <div style="width:50%; padding:4px; background:#ec4899; color:#ffffff;">여 {{ count($femaleSample) }}</div>
                    </div>

                    {{-- 남/여 행 맞춤 --}}
                    <div style="display:flex;">
                        {{-- 좌: 남자 --}}
                        <div style="width:50%; box-sizing:border-box; border-right:1px solid #c7d7ee; background:#eff6ff;">
                            @foreach ($maleSample as $m)
                                <div style="height:{{ $cellHeight }}; padding:{{ $cellPadding }}; border-bottom:1px solid #d7e3ef; text-align:center; display:flex; flex-direction:column; justify-content:center;">
                                    <div style="font-weight:900; font-size:{{ $cellNoSize }};">{{ $m['no'] }}호</div>
                                    <div style="color:#6b7280; font-size:{{ $cellInfoSize }}; margin-top:4px; word-break:keep-all;">{{ $m['height'] }}cm · {{ $m['job'] }} · {{ $m['age'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        {{-- 우: 여자 --}}
                        <div style="width:50%; box-sizing:border-box; background:#fdf2f8;">
                            @foreach ($femaleSample as $f)
                                <div style="height:{{ $cellHeight }}; padding:{{ $cellPadding }}; border-bottom:1px solid #f6d9e6; text-align:center; display:flex; flex-direction:column; justify-content:center;">
                                    <div style="font-weight:900; font-size:{{ $cellNoSize }};">{{ $f['no'] }}호</div>
                                    <div style="color:#6b7280; font-size:{{ $cellInfoSize }}; margin-top:4px; word-break:keep-all;">{{ $f['height'] }}cm · {{ $f['job'] }} · {{ $f['age'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 좌석/성비 안내 --}}
    <div style="margin-top:20px; background:#eff6ff; border:1px dashed #93c5fd; border-radius:12px; padding:20px 16px; text-align:center; font-size:27px; color:#1d4ed8; line-height:1.7;">
        🪑 자리는 <b>3:3 · 4:4</b> 등 최대한 <b>성비를 맞춰</b> 배정해요.<br>
        <span style="font-size:24px; color:#2563eb;">다만 당일 참석 현황에 따라 일부 테이블은 3:2 등으로 조정될 수 있어요.</span>
    </div>

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
                        <div style="font-size:28px; font-weight:700; color:#4b5563; margin-top:10px; line-height:1.55; word-break:keep-all;">{{ $pt['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($type === 'wine')
    {{-- 미니게임 콘텐츠 소개 (와인파티 전용) --}}
    <div style="margin-top:4px; margin-bottom:44px;">
        <div style="font-family:'Jua',sans-serif; font-size:42px; font-weight:400; color:#7c3aed; text-align:center; letter-spacing:0.5px; margin-bottom:8px;">🎮 대화가 어색할 틈이 없어요</div>
        <div style="font-size:30px; color:#6b7280; text-align:center; margin-bottom:24px; line-height:1.6;">
            로테이션 중, 같은 테이블끼리 즐기는 <b style="color:#7c3aed;">미니게임</b>을 폰으로 바로!<br>
            처음 만난 사이도 금세 웃음꽃이 피는 아이스브레이킹 콘텐츠예요.
        </div>

        {{-- 게임 이미지 (존재하는 game1~5.png 를 2열로 자동 노출) --}}
        @php
            $gameImgs = [];
            foreach (range(1, 6) as $n) {
                if (file_exists(public_path("images/game{$n}.png"))) {
                    $gameImgs[] = asset("images/game{$n}.png");
                }
            }
        @endphp
        @if (count($gameImgs) > 0)
            <div style="display:flex; flex-wrap:wrap; margin-bottom:20px;">
                @foreach ($gameImgs as $idx => $src)
                    <div style="width:50%; box-sizing:border-box; padding:8px;">
                        <img src="{{ $src }}" alt="미니게임 예시 {{ $idx + 1 }}"
                             style="display:block; width:100%; height:auto; border-radius:14px; border:1px solid #e5e7eb;">
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
                        <div style="font-size:27px; font-weight:700; color:#4b5563; margin-top:10px; line-height:1.55; word-break:keep-all;">{{ $g['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="background:#f5f3ff; border:1px dashed #c4b5fd; border-radius:12px; padding:18px 14px; margin-top:16px; text-align:center; font-size:26px; color:#6d28d9; line-height:1.6;">
            🍷 게임 벌칙은 테이블끼리 자유롭게! 가볍게 한 잔으로 분위기를 더 띄워 보세요
        </div>
    </div>
    @endif

    {{-- 파티 진행시간 안내 --}}
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
                        <div style="font-size:26px; color:#6b7280; margin-top:10px; line-height:1.6;">자유 대화 시간<br><b style="color:#be185d;">추가 비용 무료</b></div>
                    </div>
                </div>
            </div>
            <div style="text-align:center; font-size:25px; color:#7c3aed; margin-top:18px;">※ 진행시간은 당일 상황에 따라 소폭 변동될 수 있어요</div>
        </div>
    </div>

    {{-- 참여 조건 --}}
    <div style="margin-top:48px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#3b82f6; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            참여 조건
        </div>
        <div style="background:#eff6ff; padding:22px 14px; text-align:center; font-size:31px; color:#374151; line-height:2.2;">
            <div>- 남성 00~88년생 / 여성 02~88년생</div>
            <div>- 미혼이신 분 (돌싱·기혼은 참여가 어려워요)</div>
            <div>- 안정적인 직업과 경제력을 갖추신 분 (신분·재직 확인에 협조해 주세요)</div>
            <div>- 진지하게 좋은 인연을 찾고 싶으신 분</div>
            <div>- 신분 확인(신분증)에 동의해 주실 수 있는 분</div>
            <div>- 서로를 존중하며 매너 있게 대화할 수 있는 분</div>
        </div>
        {{-- 남성 자격요건 / 우선 승인 안내 --}}
        <div style="background:#dbeafe; border-top:2px solid #bfdbfe; padding:22px 14px; text-align:center; font-size:30px; color:#1e3a8a; line-height:2.0;">
            <div style="font-weight:900; font-size:33px; margin-bottom:12px;">👔 남성 참가 자격요건</div>
            <div>- 신장 <b>175cm 이상</b></div>
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
                        <div style="font-size:29px; font-weight:700; color:#4b5563; margin-top:8px; line-height:1.55; word-break:keep-all;">{{ $step['desc'] }}</div>
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
    <div style="margin-top:48px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#f59e0b; padding:16px 12px; font-size:39px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#ffffff; text-align:center;">
            자주 묻는 질문
        </div>
        <div style="background:#fffbeb; padding:24px 16px; font-size:31px; color:#374151; line-height:1.8; text-align:center;">
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 혼자 가도 괜찮나요?</div>
                <div style="color:#4b5563; font-weight:700; margin-top:6px;">그럼요, 전혀 걱정하지 않으셔도 돼요! 😊 사실 대부분의 분들이 혼자 오신답니다.<br>정해진 로테이션 순서와 대화 주제·미니게임이 자연스럽게 분위기를 이끌어 주고,<br>모든 진행이 스마트폰 안내를 따라 흘러가니 편한 마음으로 오세요.</div>
            </div>
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 술을 못 마시면 어떡해요?</div>
                <div style="color:#4b5563; font-weight:700; margin-top:6px;">걱정 마세요! 술은 전혀 필수가 아니랍니다 🙂<br>와인 대신 음료도 준비되어 있으니 편하게 즐기시면 돼요.<br>중요한 건 술이 아니라 서로를 알아가는 즐거운 대화니까요.<br>못 마셔도 충분히 즐겁게 참여하실 수 있어요!</div>
            </div>
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 친구랑 같이 앉을 수 있나요?</div>
                <div style="color:#4b5563; font-weight:700; margin-top:6px;">함께 오시는 건 언제나 환영이에요! 👯<br>다만 로테이션 특성상 자리는 서로 다른 테이블로 배정될 수 있어요.<br>더 많은 분과 새로운 인연을 만나실 수 있도록 돕기 위함이니 이해 부탁드려요.<br>행사가 끝난 뒤에는 다시 만나 후기를 나누는 재미도 쏠쏠하답니다 😉</div>
            </div>
            <div style="margin-bottom:24px;">
                <div style="font-weight:900;">Q. 주차가 되나요?</div>
                <div style="color:#4b5563; font-weight:700; margin-top:6px;">아쉽지만 전용 주차 공간은 없어요 🙏<br>선릉역에서 도보 4분 거리라 접근성이 좋으니<br>가급적 대중교통 이용을 부탁드려요!</div>
            </div>
            <div>
                <div style="font-weight:900;">Q. 개인정보는 안전한가요?</div>
                <div style="color:#4b5563; font-weight:700; margin-top:6px;">네, 안심하셔도 돼요! 🤍<br>투표 결과와 연락처는 서로 매칭된 상대에게만 공유되고,<br>그 외에는 어디에도 공개되지 않아요.<br>소중한 개인정보는 저희가 책임지고 안전하게 지켜드릴게요.</div>
            </div>
        </div>
    </div>

    {{-- 마감 임박 CTA 배너 --}}
    <div style="margin-top:48px; border-radius:16px; overflow:hidden; background:linear-gradient(135deg,#f43f5e,#ec4899,#8b5cf6); padding:36px 20px; text-align:center; color:#ffffff;">
        <div style="display:inline-block; background:rgba(255,255,255,0.22); border-radius:999px; padding:6px 18px; font-size:26px; font-weight:800; margin-bottom:16px;">🔥 오픈 기념 할인 · 마감 임박</div>
        <div style="font-family:'Jua',sans-serif; font-size:48px; font-weight:400; letter-spacing:0.5px; line-height:1.3;">지금 바로 신청하세요!</div>
        <div style="font-size:29px; margin-top:14px; opacity:0.95; line-height:1.6;">오픈 특가는 조기 마감될 수 있어요.<br>좋은 인연, 망설이는 사이 자리가 찹니다 💘</div>
    </div>
</div>
