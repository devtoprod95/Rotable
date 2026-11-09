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
     * 유형 정규화. 'solo' 외에는 전부 'wine' 으로 귀속.
     */
    public function normalizeType(?string $type): string
    {
        return $type === 'solo' ? 'solo' : 'wine';
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
                    'heading' => '로테이블 인사',
                    'lines' => [
                        '안녕하세요, 로테이블입니다! 🍷',
                        '잠깐만 슬라이드에 집중해 주시면 안내만 빠르게 드리고 바로 시작하겠습니다!',
                    ],
                ],
                [
                    'no' => '②',
                    'heading' => '오늘의 진행 순서',
                    'lines' => [
                        '먼저 오늘 진행 순서부터 말씀드릴게요.',
                        '지금 앉아 계신 테이블에서 대화를 나누시다가, 안내가 나오면 자리를 이동하시게 됩니다.',
                        '이동은 남성분들만 하시는데, 지금 테이블에서 바로 다음 번호 테이블로 한 칸씩 옮겨 주시면 돼세요.',
                        '그리고 마지막 번호 테이블에 계신 남성분은 다시 1번 테이블로 돌아오시면 됩니다. 여성분들은 계속 그 자리에 앉아 계시면 되겠습니다.',
                        '대화 중에 마음에 드는 분이 계시면 앱에서 메시지도 보내시고, 매칭 투표 시간에 투표를 해주시면 됩니다.',
                        '투표가 끝나면 2차 자유 대화 시간이 이어지는데, 2차는 필수 참여가 아니니 편하게 선택하시면 돼요. 자리도 따로 정해져 있지 않으니, 마음에 드는 이성분께 자유롭게 가서 앉으시면 됩니다.',
                        '매칭 결과는 내일 오전 9시에 매칭하기 화면에서 확인하실 수 있습니다.',
                        '서로 매칭이 되면 그때 상대방 연락처가 공개되고, 매칭이 안 되면 연락처는 공개되지 않아요. 내가 받은 투표수도 함께 확인하실 수 있습니다.',
                    ],
                ],
                [
                    'no' => '③',
                    'heading' => '함께 지킬 약속',
                    'lines' => [
                        '모두가 편하게 즐기실 수 있도록, 몇 가지만 부탁드릴게요.',
                        '서로 존중해 주시고, 이동 안내가 나오면 자기 식기도구와 짐을 챙겨서 신속하게 다음 자리로 옮겨 주세요.',
                        '술이 부담되시는 분을 위해 자몽주스도 준비돼 있으니 편하게 드시고, 과음은 조금만 주의 부탁드려요.',
                        '그리고 와인잔이나 집기 같은 시설물이 파손되면 배상 책임이 생길 수 있으니, 다루실 때 조금만 조심해 주세요.',
                        '진행 중에 불편한 점이 있으시면 언제든 앱에 있는 요청하기로 알려 주시면 바로 도와 드리겠습니다.',
                    ],
                ],
                [
                    'no' => '④',
                    'heading' => '미니게임 소개',
                    'lines' => [
                        '대화가 조금 어색하실 때를 위해 미니게임도 준비했어요.',
                        '꼭 하셔야 하는 건 아니에요.',
                        '다만 2차에서는 대화에 집중하는 시간이라, 그때는 게임이 열리지 않습니다.',
                    ],
                ],
                [
                    'no' => '⑤',
                    'heading' => '오픈 기념 안내',
                    'lines' => [
                        '마지막으로, 오늘은 오픈 기념 할인으로 모신 자리예요.',
                        '1차가 끝난 뒤에, 신청하셨던 플랫폼에 후기 한 줄만 남겨 주시면 저희한테 큰 힘이 됩니다.',
                        '그럼 지금부터 로테이블 시작하겠습니다. 감사합니다!',
                    ],
                ],
            ],
        ],
        'solo' => [
            'title' => '1:1 소개팅 발표 멘트',
            'sections' => [
                [
                    'no' => '①',
                    'heading' => '로테이블 인사',
                    'lines' => [
                        '안녕하세요, 로테이블입니다! 💗',
                        '자리 빛내 주셔서 감사합니다! 안내만 빠르게 드리고 바로 시작할게요!',
                    ],
                ],
                [
                    'no' => '②',
                    'heading' => '서비스 이용안내',
                    'lines' => [
                        '아직 입장등록 안 하셨으면, 앱에서 번호표·내 소개부터 빠르게 적어 주세요. 안하시면 참여를 못합니다.',
                        '1:1로 대화하시다가 안내가 나오면, 남성분만 다음 번호 자리로 한 칸씩 이동! 여성분은 그대로 계시면 되며 마지막 번호면 1번으로 오시면 되겠습니다.',
                        '마음에 드는 분은 대화 중에 앱에서 몰래 선택해 두시면 되겠습니다.',
                        '대화가 다 끝나면 여성분 먼저, 남성분 나중에 퇴장하시고, 10분 안에 매칭 결과를 발표해 드립니다!',
                    ],
                ],
                [
                    'no' => '③',
                    'heading' => '함께 지킬 약속',
                    'lines' => [
                        '이동 안내가 나오면 바로 다음 자리로 옮겨 주세요.',
                        '누굴 골랐는지는 서로 비밀! 묻지 않기로 해요.',
                        '화장실은 쉬는 시간에, 대화 중이면 가볍게 양해 구하고 다녀오세요.',
                        '시설물 파손 시 배상 책임이 있으니 조심해 주세요.',
                    ],
                ],
                [
                    'no' => '④',
                    'heading' => '오픈 기념 안내',
                    'lines' => [
                        '오늘은 오픈 기념 할인 자리예요.',
                        '신청하신 플랫폼에 후기 한 줄 남겨 주시고, 퇴장하실 때 그 화면만 저에게 보여 주시면 됩니다!',
                    ],
                ],
                [
                    'no' => '⑤',
                    'heading' => '매칭은 이렇게 돼요',
                    'lines' => [
                        '마음에 드는 분 최대 세 분까지, 종료 전엔 언제든 변경 가능!',
                        '서로 선택해야 매칭 성사돼요. 한쪽만 선택하면 매칭 안 됩니다.',
                        '대신 나를 선택한 분의 연락처는 공개되어 신중하게 선택해주세요!',
                        '자 그럼 지금부터 로테이블 시작하겠습니다. 감사합니다!',
                    ],
                ],
            ],
        ],
    ];
}
