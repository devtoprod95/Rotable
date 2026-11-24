<?php

namespace App\Services;

/**
 * 공개 게임용 캐릭터 닉네임. 로그인이 없는 공개방에서 닉네임을 비웠을 때 쓴다.
 * 기기 id 로 고정 선택해 같은 기기는 늘 같은 이름을 받는다.
 */
class CharacterNickname
{
    public const NAMES = [
        '짱구', '철수', '훈이', '맹구', '유리', '흰둥이', '짱아', '봉미선',
        '둘리', '또치', '도우너', '마이콜', '고길동', '희동이',
        '뽀로로', '크롱', '에디', '루피', '패티', '포비',
        '피카츄', '파이리', '꼬부기', '이상해씨', '로켓단',
        '라이언', '어피치', '무지', '프로도', '네오', '튜브',
        '도라에몽', '진구', '이슬이', '퉁퉁이', '비실이',
        '엘사', '안나', '올라프', '심바', '뮬란',
        '미키', '미니', '도널드', '구피', '푸우', '피글렛',
    ];

    /** 기기 id 로 고정된 캐릭터 이름을 고른다. */
    public static function forClient(string $clientId): string
    {
        return self::NAMES[crc32($clientId) % count(self::NAMES)];
    }

    /**
     * 이미 쓰는 이름이면 숫자를 붙여 겹치지 않게 만든다. (짱구 → 짱구2)
     *
     * @param array<int, string> $taken 이미 쓰는 이름들
     */
    public static function unique(array $taken, string $label): string
    {
        if (! in_array($label, $taken, true)) {
            return $label;
        }

        for ($i = 2; ; $i++) {
            if (! in_array($label . $i, $taken, true)) {
                return $label . $i;
            }
        }
    }
}
