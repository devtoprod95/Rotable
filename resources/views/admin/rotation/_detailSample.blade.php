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
            'points'   => [
                '와인 &amp; 핑거푸드가 무제한! 맛있게 드시면서 편하게 대화해요 🍷',
                '남녀 화장실이 따로 준비되어 있어요. 걱정 마세요!',
                '역에서 가까운 접근성 좋은 장소에서 진행돼요 🚶',
                '로테이블만의 간편 매칭 소개팅 웹! 투표부터 매칭까지 스마트폰으로 간편하게 📱',
                '투표는 100% 비밀이에요. 매칭된 상대에게만 살짝 공유됩니다 🤫',
                '서로 선택하면 매칭 성사! 결과는 다음 날 오전에 알려드려요 💘',
            ],
        ],
        'solo' => [
            'image'    => file_exists(public_path('images/detail_solo.png'))
                ? asset('images/detail_solo.png')
                : asset('images/detail_img.png'),
            'title'    => 'ROTABLE 1:1 소개팅 안내',
            'subtitle' => '이번 회차 참여자를 확인하세요',
            'eventName'=> '1:1 소개팅',
            'points'   => [
                '로테이블이 끝나면 그 자리에서 바로! 매칭 결과를 알려드려요. 다음 날까지 두근두근 기다리지 않아도 돼요 ⏱️💘',
                '나를 선택해 준 상대의 정보도 함께 공유해드려요. 그래서 행사 뒤에도 편하게 연락을 이어갈 수 있어요 💌',
                '"그 사람이 누구였더라?" 헷갈릴 걱정 없어요. 만난 상대의 정보를 스마트폰으로 한눈에 다시 확인할 수 있답니다 📱',
                '쪽지를 몰래 건네거나 번호를 물어보며 민망해지는 순간, 로테이블엔 없어요. 모든 선택은 스마트폰에서 조용하고 깔끔하게 🤫',
                '투표는 100% 비밀! 서로 선택한 경우에만 매칭 사실이 공유되니 마음 편히 솔직하게 고르세요 🤍',
                '첫 만남부터 매칭·연락까지 로테이블 하나로. 복잡한 과정 없이 좋은 인연에만 집중하시면 돼요 😊',
            ],
        ],
    ][$type];

    // 더미 참여자 (남 5 / 여 5) — 이름은 N호로 표기
    $maleSample = [
        ['no' => 1, 'height' => 180, 'job' => '개발자',   'age' => '30대 초반'],
        ['no' => 2, 'height' => 178, 'job' => '디자이너', 'age' => '20대 후반'],
        ['no' => 3, 'height' => 183, 'job' => '마케터',   'age' => '30대 중반'],
        ['no' => 4, 'height' => 175, 'job' => '회사원',   'age' => '20대 중반'],
        ['no' => 5, 'height' => 182, 'job' => '의사',     'age' => '30대 초반'],
    ];
    $femaleSample = [
        ['no' => 1, 'height' => 164, 'job' => '승무원',   'age' => '20대 중반'],
        ['no' => 2, 'height' => 160, 'job' => '간호사',   'age' => '20대 후반'],
        ['no' => 3, 'height' => 167, 'job' => '교사',     'age' => '30대 초반'],
        ['no' => 4, 'height' => 162, 'job' => '디자이너', 'age' => '20대 중반'],
        ['no' => 5, 'height' => 169, 'job' => '회사원',   'age' => '20대 후반'],
    ];

    // 더미 행사
    $sampleEvents = [
        ['title' => '1회 ' . $typeConfig['eventName'], 'date' => '2026.01.17(토) 19:00'],
        ['title' => '2회 ' . $typeConfig['eventName'], 'date' => '2026.02.14(토) 18:00'],
    ];
    if ($columns === 1) {
        $sampleEvents = array_slice($sampleEvents, 0, 1);
    }
@endphp
<style>
    @import url('https://fonts.googleapis.com/css2?family=Jua&family=Gothic+A1:wght@400;500;700;800;900&display=swap');
</style>
<div style="font-family:'Gothic A1','Pretendard','Apple SD Gothic Neo',sans-serif; font-weight:700; color:#4b4453; line-height:1.35; width:100%;">

    {{-- 최상단 상세 이미지 --}}
    <div style="text-align:center; margin-bottom:24px;">
        <img src="{{ $typeConfig['image'] }}" alt="상세 이미지"
             style="display:block; width:100%; max-width:100%; height:auto; border-radius:12px;">
    </div>

    <div style="text-align:center; margin-bottom:24px;">
        <div style="font-family:'Jua',sans-serif; font-size:46px; font-weight:400; color:#e86aa6; letter-spacing:0.5px;">{{ $typeConfig['title'] }}</div>
        <div style="font-size:24px; color:#9ca3af; margin-top:2px;">{{ $typeConfig['subtitle'] }}</div>
    </div>

    {{-- 실시간 명단 업데이트 안내 --}}
    <div style="background:#f3e8ff; border:1px solid #e9d5ff; border-radius:12px; padding:9px 11px; margin-bottom:11px; text-align:center;">
        <div style="font-family:'Jua',sans-serif; font-size:25px; font-weight:400; color:#7e22ce; letter-spacing:0.5px;">실시간 명단 업데이트 중</div>
        <div style="font-size:21px; color:#6b21a8; margin-top:2px;">
            아래 참여자 명단은 신청 현황에 따라 수시로 갱신됩니다.<br>
            <b>마지막 업데이트: {{ now()->format('n월 j일 H:i') }}</b> 기준
        </div>
    </div>

    {{-- 행사 그리드 --}}
    <div style="display:flex; flex-wrap:wrap;">
        @foreach ($sampleEvents as $event)
            <div style="width:{{ $cardWidth }}; box-sizing:border-box; padding:3px;">
                <div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; height:100%;">
                    {{-- 행사명 + 날짜 --}}
                    <div style="background:#f8fafc; padding:7px 6px; border-bottom:1px solid #e5e7eb; text-align:center;">
                        <div style="font-size:29px; font-weight:900;">{{ $event['title'] }}</div>
                        <div style="font-size:21px; color:#6b7280; margin-top:1px;">{{ $event['date'] }}</div>
                    </div>

                    {{-- 남(좌) / 여(우) 헤더 --}}
                    <div style="display:flex; font-size:21px; font-weight:900; text-align:center; border-bottom:1px solid #eee;">
                        <div style="width:50%; padding:4px; background:#eff6ff; color:#2563eb;">남 {{ count($maleSample) }}</div>
                        <div style="width:50%; padding:4px; background:#fdf2f8; color:#be185d;">여 {{ count($femaleSample) }}</div>
                    </div>

                    {{-- 남/여 행 맞춤 --}}
                    <div style="display:flex;">
                        {{-- 좌: 남자 --}}
                        <div style="width:50%; box-sizing:border-box; border-right:1px solid #eee;">
                            @foreach ($maleSample as $m)
                                <div style="height:84px; padding:10px; border-bottom:1px solid #f3f4f6; text-align:center; display:flex; flex-direction:column; justify-content:center;">
                                    <div style="font-weight:900; font-size:23px;">{{ $m['no'] }}호</div>
                                    <div style="color:#6b7280; font-size:19px; margin-top:4px;">{{ $m['height'] }}cm · {{ $m['job'] }} · {{ $m['age'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        {{-- 우: 여자 --}}
                        <div style="width:50%; box-sizing:border-box;">
                            @foreach ($femaleSample as $f)
                                <div style="height:84px; padding:10px; border-bottom:1px solid #f3f4f6; text-align:center; display:flex; flex-direction:column; justify-content:center;">
                                    <div style="font-weight:900; font-size:23px;">{{ $f['no'] }}호</div>
                                    <div style="color:#6b7280; font-size:19px; margin-top:4px;">{{ $f['height'] }}cm · {{ $f['job'] }} · {{ $f['age'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 이전 로테이블 기록 --}}
    <div style="margin-top:30px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#f0f9ff; padding:7px 10px; font-size:28px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#0369a1; text-align:center;">
            이전 로테이블 기록
        </div>
        <div style="padding:14px 16px; text-align:center;">
            {{-- 헤더 --}}
            <div style="display:flex; font-size:20px; font-weight:800; color:#6b7280; border-bottom:2px solid #e5e7eb; padding-bottom:10px; margin-bottom:4px;">
                <div style="width:28%;">날짜</div>
                <div style="width:27%;">행사명</div>
                <div style="width:25%;">인원 (남/여)</div>
                <div style="width:20%;">매칭</div>
            </div>
            {{-- 행사 기록 (더미) --}}
            <div style="display:flex; font-size:21px; color:#374151; padding:14px 0; border-bottom:1px solid #f3f4f6;">
                <div style="width:28%;">2025.12.20</div>
                <div style="width:27%;">3회 {{ $typeConfig['eventName'] }}</div>
                <div style="width:25%;">16명 (8/8)</div>
                <div style="width:20%; color:#be185d; font-weight:800;">4커플</div>
            </div>
            <div style="display:flex; font-size:21px; color:#374151; padding:14px 0; border-bottom:1px solid #f3f4f6;">
                <div style="width:28%;">2025.11.15</div>
                <div style="width:27%;">2회 {{ $typeConfig['eventName'] }}</div>
                <div style="width:25%;">14명 (7/7)</div>
                <div style="width:20%; color:#be185d; font-weight:800;">3커플</div>
            </div>
            <div style="display:flex; font-size:21px; color:#374151; padding:14px 0;">
                <div style="width:28%;">2025.10.18</div>
                <div style="width:27%;">1회 {{ $typeConfig['eventName'] }}</div>
                <div style="width:25%;">18명 (9/9)</div>
                <div style="width:20%; color:#be185d; font-weight:800;">5커플</div>
            </div>
        </div>
    </div>

    {{-- 로테이블 포인트 --}}
    <div style="margin-top:28px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#fdf2f8; padding:7px 10px; font-size:28px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#be185d; text-align:center;">
            💗 로테이블 포인트
        </div>
        <div style="padding:10px; text-align:center; font-size:22px; color:#374151; line-height:2.0;">
            @foreach ($typeConfig['points'] as $point)
                <div>- {!! $point !!}</div>
            @endforeach
        </div>
    </div>

    {{-- 참여 조건 --}}
    <div style="margin-top:28px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#eff6ff; padding:7px 10px; font-size:28px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#2563eb; text-align:center;">
            참여 조건
        </div>
        <div style="padding:10px; text-align:center; font-size:22px; color:#374151; line-height:2.0;">
            <div>- 남성 00~88년생 / 여성 02~88년생</div>
            <div>- 미혼이신 분 (돌싱·기혼은 참여가 어려워요)</div>
            <div>- 안정적인 직업과 경제력을 갖추신 분 (신분·재직 확인에 협조해 주세요)</div>
            <div>- 진지하게 좋은 인연을 찾고 싶으신 분</div>
            <div>- 신분 확인(신분증)에 동의해 주실 수 있는 분</div>
            <div>- 서로를 존중하며 매너 있게 대화할 수 있는 분</div>
        </div>
    </div>

    {{-- 로테이블 이용안내 --}}
    <div style="margin-top:28px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#ecfdf5; padding:7px 10px; font-size:28px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#047857; text-align:center;">
            로테이블 이용안내
        </div>
        <div style="padding:10px; font-size:22px; color:#374151; line-height:1.7; text-align:center;">
            @if ($type === 'solo')
                <div style="margin-bottom:6px;"><b>1. 입장 &amp; 닉네임 확인</b><br>도착하시면 나만의 닉네임을 받고 지정 자리에 착석해요.</div>
                <div style="margin-bottom:6px;"><b>2. 1:1 로테이션 대화</b><br>한 분씩 마주 앉아 1:1로 대화해요 ☕<br>시간이 지나면 자리를 옮겨 모든 상대와 차례로 만나요.</div>
                <div style="margin-bottom:6px;"><b>3. 스마트폰 비밀 투표</b><br>쪽지도, 어색하게 번호를 묻는 순간도 없어요 🙂<br>마음에 든 상대를 스마트폰에서 조용히, 비공개로 선택하면 끝!</div>
                <div style="margin-bottom:6px;"><b>4. 바로 매칭 발표</b><br>로테이션이 끝나면 그 자리에서 바로 매칭 결과를 알려드려요 💘<br>다음 날까지 기다리지 않아도 돼요.</div>
                <div><b>5. 상대 정보 공유 &amp; 연락</b><br>나를 선택해 준 상대의 정보를 스마트폰으로 확인할 수 있어요 📱<br>헷갈릴 걱정 없이, 행사 뒤에도 편하게 연락을 이어가세요 😊</div>
            @else
                <div style="margin-bottom:6px;"><b>1. 입장 &amp; 닉네임 확인</b><br>도착하시면 나만의 닉네임을 받고 지정 테이블에 착석해요.</div>
                <div style="margin-bottom:6px;"><b>2. 로테이션 대화</b><br>회차 타입에 따라 테이블 단위(3:3, 4:4, 3:2 등)로 함께 또는 1:1로 만나요.<br>시간이 지나면 자리를 이동하며 여러 분과 자연스럽게 대화해요.</div>
                <div style="margin-bottom:6px;"><b>3. 메시지 &amp; 비밀 투표</b><br>대화 중 마음이 가는 분께 살짝 메시지도 보낼 수 있어요 💌<br>로테이션이 끝나면 마음에 든 상대를 비공개로 선택해요.</div>
                <div style="margin-bottom:6px;"><b>4. 자유 대화 시간 (2차)</b><br>투표가 끝난 뒤에는, 마음에 드는 분과 자유롭게 앉아<br>더 깊은 이야기를 나눌 수 있는 시간을 드려요 ☕</div>
                <div><b>5. 매칭 발표</b><br>서로 선택했다면 매칭 성사! 결과는 <b>다음 날 오전</b>에 알려드려요 😊</div>
            @endif
        </div>
    </div>

    {{-- 자주 묻는 질문 --}}
    <div style="margin-top:28px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="background:#fef3c7; padding:7px 10px; font-size:28px; font-family:'Jua',sans-serif; font-weight:400; letter-spacing:0.5px; color:#b45309; text-align:center;">
            자주 묻는 질문
        </div>
        <div style="padding:10px; font-size:22px; color:#374151; line-height:1.7; text-align:center;">
            <div style="margin-bottom:8px;">
                <div style="font-weight:900;">Q. 혼자 가도 괜찮나요?</div>
                <div style="color:#6b7280;">그럼요, 전혀 걱정하지 않으셔도 돼요! 😊 사실 대부분의 분들이 혼자 오신답니다.<br>전문 호스트가 처음부터 끝까지 분위기를 부드럽게 이끌어 드리고,<br>자연스럽게 대화가 이어지도록 도와드리니 편한 마음으로 오세요.</div>
            </div>
            <div style="margin-bottom:8px;">
                <div style="font-weight:900;">Q. 술을 못 마시면 어떡해요?</div>
                <div style="color:#6b7280;">걱정 마세요! 술은 전혀 필수가 아니랍니다 🙂<br>와인 대신 음료도 준비되어 있으니 편하게 즐기시면 돼요.<br>중요한 건 술이 아니라 서로를 알아가는 즐거운 대화니까요.<br>못 마셔도 충분히 즐겁게 참여하실 수 있어요!</div>
            </div>
            <div style="margin-bottom:8px;">
                <div style="font-weight:900;">Q. 친구랑 같이 앉을 수 있나요?</div>
                <div style="color:#6b7280;">함께 오시는 건 언제나 환영이에요! 👯<br>다만 로테이션 특성상 자리는 서로 다른 테이블로 배정될 수 있어요.<br>더 많은 분과 새로운 인연을 만나실 수 있도록 돕기 위함이니 이해 부탁드려요.<br>행사가 끝난 뒤에는 다시 만나 후기를 나누는 재미도 쏠쏠하답니다 😉</div>
            </div>
            <div>
                <div style="font-weight:900;">Q. 개인정보는 안전한가요?</div>
                <div style="color:#6b7280;">네, 안심하셔도 돼요! 🤍<br>투표 결과와 연락처는 서로 매칭된 상대에게만 공유되고,<br>그 외에는 어디에도 공개되지 않아요.<br>소중한 개인정보는 저희가 책임지고 안전하게 지켜드릴게요.</div>
            </div>
        </div>
    </div>
</div>
