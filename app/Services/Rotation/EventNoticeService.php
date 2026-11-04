<?php

namespace App\Services\Rotation;

use App\Models\EventRound;

/**
 * 참가자 채팅방(오픈채팅 등)에 올리는 행사 공지 안내 문구 생성.
 *
 * - 시작 시간은 행사(event_rounds.event_time)에서 자동으로 채운다.
 * - 행사 타입(와인파티 / 1:1 소개팅)에 따라 진행 안내·리뷰 안내·매너 문구가 달라진다.
 */
class EventNoticeService
{
    /** 행사 장소 기본값 (화면에서 수정 가능) */
    public const DEFAULT_PLACE = '서울 강남구 언주로98길 23, B1';

    /**
     * 공지 문구를 만든다.
     *
     * @param  string|null  $place  장소. 비어 있으면 기본 장소를 사용한다.
     */
    public function build(EventRound $event, ?string $place = null): string
    {
        $place    = trim((string) $place) !== '' ? trim((string) $place) : self::DEFAULT_PLACE;
        $isOne    = $event->isOneToOne();
        $startAt  = $event->event_time ? $event->event_time->format('H:i') : '시간 미정';

        $lines = [
            '[공지 안내]',
            '안녕하세요, 로테이블에 참여해 주셔서 감사합니다 😊',
            '행사 관련 안내드리니 꼭 확인 부탁드려요!',
            '',
            '📍 장소: ' . $place,
            '🕖 시작 시간: ' . $startAt . ' (원활한 진행을 위해 지각하지 않게 부탁드려요!)',
            '🚇 교통: 선릉역 도보 4분 / 전용 주차가 없어 대중교통 이용 권장드려요',
            '',
            '👔 남성 준비물: 신분증, 사원증(명함) 또는 재직 증명 서류',
            '👗 여성 준비물: 신분증',
            '',
            '📱 진행 안내',
            '',
            '- 모든 진행(프로필 등록·비밀 투표·매칭 결과 확인)이 스마트폰으로 이뤄집니다.',
            '- 종이 작성이 없으니 편하게 참여하시면 돼요. 배터리는 넉넉히 충전해 와 주세요!',
        ];

        if ($isOne) {
            $lines[] = '- 한 분씩 1:1로 마주 앉아 대화하고, 로테이션이 끝나면 비밀 투표를 진행해요.';
            $lines[] = '- 로테이션 종료 후 전원 퇴실하면 10분 이내에 매칭 결과를 바로 확인할 수 있어요.';
            $lines[] = '- 다과 및 음료가 제공됩니다.';
        } else {
            $lines[] = '- 2차 자유 대화 시간까지 추가 비용은 전부 무료입니다.';
        }

        $lines[] = '';
        $lines[] = '⭐ 리뷰 안내';
        $lines[] = '';
        $lines[] = '- 이번 행사는 오픈 기념 할인 행사로 진행돼요.';
        $lines[] = $isOne
            ? '- 행사 종료 후, 모집된 플랫폼에 좋은 후기 한 줄 꼭 부탁드립니다 🙏'
            : '- 2차 시작 전, 모집된 플랫폼에 좋은 후기 한 줄 꼭 부탁드립니다 🙏';

        $lines[] = '';
        $lines[] = '🤝 함께 지켜요';
        $lines[] = '';
        $lines[] = '- 서로 존중하며 매너 있게 대화해 주세요.';
        $lines[] = $isOne
            ? '- 원활한 진행을 방해하는 행동 시 퇴장 조치될 수 있습니다.'
            : '- 과음으로 인한 난동 시 퇴장 조치될 수 있습니다.';
        $lines[] = '- 시설·집기 파손 시 배상 책임이 발생할 수 있으니 주의 부탁드려요.';

        $lines[] = '';
        $lines[] = '📞 문의 / 지각·불참 시: 따로 DM 부탁드려요';
        $lines[] = '';
        $lines[] = '로테이블에서 좋은 인연 만나시길 응원할게요 💘';

        return implode("\n", $lines);
    }
}
