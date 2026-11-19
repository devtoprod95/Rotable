<?php

namespace App\Services\Rotation;

use App\Models\EventScript;
use Illuminate\Support\Facades\Auth;

/**
 * 호스트 발표 멘트(대본) 조회/저장 서비스.
 *
 * - 유형(wine/solo)별로 DB(event_scripts)에 저장된 멘트가 있으면 그것을, 없으면 코드의 기본 멘트를 쓴다.
 * - 멘트 구조: ['title' => string, 'sections' => [['no','heading','lines'=>[]], ...]]
 */
class ScriptService
{
    /**
     * 유형 정규화. 'wine' 외에는 전부 'solo'(1:1) 로 귀속.
     */
    public function normalizeType(?string $type): string
    {
        return $type === 'wine' ? 'wine' : 'solo';
    }

    /**
     * 유형별 발표 멘트를 반환한다. (DB 저장값 우선, 없으면 기본 멘트)
     *
     * @return array{title: string, sections: list<array{no: string, heading: string, lines: list<string>}>}
     */
    public function get(string $type): array
    {
        $type = $this->normalizeType($type);

        $saved = EventScript::where('type', $type)->first();
        if ($saved !== null && is_array($saved->sections) && count($saved->sections) > 0) {
            return [
                'title'    => $saved->title ?: self::DEFAULTS[$type]['title'],
                'sections' => $saved->sections,
            ];
        }

        return self::DEFAULTS[$type];
    }

    /**
     * 유형별 발표 멘트를 저장한다. (유형별 1행 upsert)
     *
     * @param  list<array{no: string, heading: string, lines: list<string>}>  $sections
     */
    public function save(string $type, string $title, array $sections): EventScript
    {
        $type = $this->normalizeType($type);

        /** @var \App\Models\Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        return EventScript::updateOrCreate(
            ['type' => $type],
            [
                'title'    => $title !== '' ? $title : self::DEFAULTS[$type]['title'],
                'sections' => array_values($sections),
                'saved_by' => $admin?->name ?? '알 수 없음',
                'saved_at' => now(),
            ],
        );
    }

    /**
     * 코드 기본 멘트. (DB 저장값이 없을 때 사용 / 편집 페이지의 "기본값으로 되돌리기" 기준)
     *
     * @var array<string, array{title: string, sections: list<array{no: string, heading: string, lines: list<string>}>}>
     */
    public const DEFAULTS = [
        'wine' => [
            'title' => '와인파티 발표 멘트',
            'sections' => [
                [
                    'no' => '①',
                    'heading' => '인사',
                    'lines' => [
                        '안녕하세요, 로테이블입니다! 🍷',
                        '안내만 짧게 드리고 바로 시작할게요. 슬라이드를 봐 주세요!',
                    ],
                ],
                [
                    'no' => '②',
                    'heading' => '입장등록',
                    'lines' => [
                        '아직이신 분은 QR을 찍고 입장등록부터 해 주세요.',
                        '받으신 번호표와 핸드폰 번호, 비밀번호 4자리, 간단한 소개를 적으시면 됩니다.',
                        '입장등록을 하셔야 앱에서 대화 상대와 투표를 보실 수 있어요.',
                    ],
                ],
                [
                    'no' => '③',
                    'heading' => '진행 방식',
                    'lines' => [
                        '지금 앉은 테이블에서 대화하다가, 안내가 나오면 자리를 옮깁니다.',
                        '이동은 남성분만! 다음 번호 테이블로 한 칸씩, 마지막 번호는 1번으로 오세요.',
                        '여성분은 그 자리에 계속 앉아 계시면 됩니다.',
                        '내 테이블과 이번 상대는 앱 홈에서 확인하실 수 있어요.',
                    ],
                ],
                [
                    'no' => '④',
                    'heading' => '메시지와 투표',
                    'lines' => [
                        '마음에 드는 분께는 앱에서 익명 메시지를 보낼 수 있어요. (최대 5건)',
                        '불편한 점은 앱의 요청하기로 알려 주세요. 바로 도와드립니다.',
                        '대화가 끝나면 투표 시간! 마음에 드는 분을 최대 3명까지 비밀로 골라 주세요.',
                    ],
                ],
                [
                    'no' => '⑤',
                    'heading' => '결과 발표',
                    'lines' => [
                        '서로 선택했을 때만 매칭됩니다. 한쪽만 고르면 매칭이 아니에요.',
                        '결과는 내일 오전 9시에 앱 매칭하기 화면에서 확인하세요.',
                        '매칭되면 연락처가 공개되고, 안 되면 공개되지 않아요.',
                    ],
                ],
                [
                    'no' => '⑥',
                    'heading' => '약속',
                    'lines' => [
                        '서로 존중해 주세요. 이동 안내가 나오면 짐을 챙겨 바로 옮겨 주세요.',
                        '과음은 주의! 술이 부담되시면 자몽주스도 준비돼 있어요.',
                        '잔이나 집기를 파손하면 배상 책임이 있으니 조심해 주세요.',
                    ],
                ],
                [
                    'no' => '⑦',
                    'heading' => '미니게임과 2차',
                    'lines' => [
                        '어색할 땐 앱 게임 탭의 미니게임을 즐겨 보세요. 필수는 아니에요.',
                        '투표 후 2차는 자유 대화예요. 마음에 드는 분께 편하게 가서 앉으세요. 참여는 선택입니다.',
                    ],
                ],
                [
                    'no' => '⑧',
                    'heading' => '마무리',
                    'lines' => [
                        '1차가 끝나면 신청하신 플랫폼에 후기 한 줄만 남겨 주세요. 큰 힘이 됩니다.',
                        '그럼 시작하겠습니다. 감사합니다!',
                    ],
                ],
            ],
        ],
        'solo' => [
            'title' => '1:1 소개팅 발표 멘트',
            'sections' => [
                [
                    'no' => '①',
                    'heading' => '인사',
                    'lines' => [
                        '안녕하세요, 안내사항 빠르게 전달 드리고 바로 시작하도록 하겠습니다.',
                        '아직 입장등록을 하지 못한 분은 앞에 QR코드를 통해 입장등록부터 부탁드립니다.',
                    ],
                ],
                [
                    'no' => '②',
                    'heading' => '진행 방식',
                    'lines' => [
                        '오늘은 총 00테이블이지만 00명을 로테이션하며 상대를 만나게 됩니다.',
                        '상대를 한 명이라도 더 보기 위해 쉬는 시간 없이 진행되며 중간 중간 상대에 양해를 구해 화장실을 이용해주시면 되겠습니다.',
                        '화장실은 남녀구분이 되어있고 제 기준 좌측에 위치해 있으며 다과도 준비되어 있으니 편하게 가져가셔도 됩니다.',
                        '상대방과 대화 할 수 있는 시간은 00분이 주어지며 안내멘트가 나오면 자리를 옮겨주시면 되겠습니다.',
                        '이동은 남성분이 하시며 다음 번호로 한 칸씩 더해서 이동하시는데 1번은 2번으로, 00번은 1번으로 오시면 되겠습니다.',
                    ],
                ],
                [
                    'no' => '③',
                    'heading' => '어플 설명',
                    'lines' => [
                        '앱을 한 번 봐주시면 감사하겠습니다. 입장등록 후 나오는 화면을 보시면 아래 4개의 메뉴가 보일텐데요,',
                        '첫 번째 메뉴 홈에서는 상대방의 정보가 있으며 상세를 누르면 상세 정보를 볼 수 있습니다. 또 매칭 선택 후 저장 버튼으로 선택이 가능한데요, 소개팅 종료 전까지 계속해서 수정할 수 있으니까 마음에 드신 분이 있으면 중간중간 선택해두셔도 됩니다. 매칭은 최대 3명까지 가능하며 매칭이 안되어도 내가 선택한 상대에게는 내 연락처가 공유된다는 점 참고 부탁 드립니다. 질문카드는 대화가 생각안 날 때 사용하시면 되겠습니다. 매칭 결과는 마지막 로테이션이 끝나고 참여자 분들까지 다 퇴실하면 5분이내로 매칭 결과 탭에서 결과를 확인 할 수 있습니다. 마지막 어필 메뉴는 로테이션이 종료되었을 때 매칭이 안 된 이성에게 마지막으로 1회 메세지를 보낼 수 있는 기능이니까 아쉬움이 남은 상대에게 메세지를 보내주시면 되겠습니다. 안내사항은 여기까지이고요 지금부터 대화 시작하도록 하겠습니다.',
                    ],
                ],
                [
                    'no' => '④',
                    'heading' => '마지막 로테이션 후',
                    'lines' => [
                        '마지막 로테이션이 종료되었습니다. 이제 최종적으로 마음에 드신 이성 분을 선택하는 시간을 1분정도 가지도록 하겠습니다.',
                        '추가로 리뷰 이벤트 행사로 저렴한 가격에 소개팅을 진행하게되었는데요, 집 가시는 길에 리뷰 한 줄 부탁드리겠습니다.',
                        '(1분후) 매칭 결과는 남성분들까지 퇴장 후 5분 이내로 발표할 예정이며 여성 분부터 퇴장하시는데 번호표는 나가실 때 앞에 직원에게 반납해주시면 되겠습니다.',
                        '그럼 여성분들부터 퇴장하도록 하겠습니다. 수고하셨습니다.',
                        '(5분후) 남성분들도 번호표는 앞에 직원에게 반납해주시고 퇴장해주셔도 되겠습니다. 수고하셨습니다.',
                    ],
                ],
            ],
        ],
    ];
}
