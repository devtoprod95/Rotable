<?php

namespace Database\Factories;

use App\Models\EventRound;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /** 남성 한글 이름 풀 */
    private const MALE_NAMES = [
        '김민준', '이서준', '박도윤', '최예준', '정시우', '강주원', '조하준', '윤지호',
        '장건우', '임우진', '한선우', '오현우', '서준서', '신동현', '권태양', '황지한',
        '안성민', '송재윤', '전민석', '홍준혁',
    ];

    /** 여성 한글 이름 풀 */
    private const FEMALE_NAMES = [
        '김서연', '이지우', '박서윤', '최하은', '정민서', '강지유', '조수아', '윤지아',
        '장채원', '임다은', '한예은', '오소율', '서하린', '신예린', '권지안', '황나윤',
        '안유나', '송서아', '전가은', '홍아린',
    ];

    /** 한글 직업 풀 */
    private const JOBS = [
        '회사원', '간호사', '교사', '디자이너', '개발자', '마케터', '공무원', '약사',
        '승무원', '변호사', '회계사', '요리사', '미용사', '건축가', '물리치료사',
        '사진작가', '바리스타', '프리랜서', '연구원', '영업사원', '의사', '세무사',
        '방송작가', '유치원교사', '피트니스트레이너',
    ];

    /** 나의 장점 풀 */
    private const MERITS = [
        '잘 웃고 리액션이 좋아요', '잘 들어주는 편이에요', '약속은 꼭 지켜요',
        '요리를 꽤 잘해요', '긍정적이고 에너지가 넘쳐요', '배려심이 많다는 말을 들어요',
        '유머감각이 있어요', '한결같고 진중해요', '새로운 걸 두려워하지 않아요',
        '정리정돈을 잘해요',
    ];

    /** 취미/요즘 빠진 것 풀 */
    private const HOBBIES = [
        '요즘 러닝에 빠졌어요', '주말마다 등산해요', '카페 투어를 좋아해요',
        '넷플릭스 정주행이 취미예요', '사진 찍는 걸 좋아해요', '베이킹에 빠졌어요',
        '클라이밍 시작했어요', '플레이리스트 만드는 걸 좋아해요', '전시회 다니는 걸 좋아해요',
        '요즘 테니스 배우는 중이에요',
    ];

    /** 이상형 풀 */
    private const IDEAL_TYPES = [
        '유머러스하고 다정한 사람', '대화가 잘 통하는 사람', '취미를 함께할 수 있는 사람',
        '배려심 깊은 사람', '자기 일에 열정 있는 사람', '편안하게 함께 있을 수 있는 사람',
        '긍정적인 에너지를 가진 사람', '솔직하게 소통하는 사람', '서로 존중할 줄 아는 사람',
        '웃음 코드가 잘 맞는 사람',
    ];

    /** TMI 풀 */
    private const TMIS = [
        '민트초코 파입니다 🍫', '사실 길치예요 😅', '강아지를 세 마리 키워요',
        '매운 걸 전혀 못 먹어요', '아침형 인간이에요', '커피는 하루 세 잔 필수예요',
        '노래방 애창곡이 20곡 넘어요', '여행 가면 꼭 엽서를 사요', '빵을 정말 좋아해요 🥐',
        '겜알못이지만 보드게임은 좋아해요',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => EventRound::factory(),
            'phone'    => '010' . $this->faker->unique()->numerify('########'),
            'name'     => $this->faker->randomElement([...self::MALE_NAMES, ...self::FEMALE_NAMES]),
            // 비밀번호는 평문 저장한다.
            'password'   => 'q',
            'gender'     => $this->faker->randomElement([Member::GENDER_MALE, Member::GENDER_FEMALE]),
            'birth_year' => $this->faker->numberBetween(1980, (int) now()->year - 19),
            'job'        => $this->faker->randomElement(self::JOBS),
            'height'     => $this->faker->numberBetween(150, 190),
            'mbti'       => $this->faker->randomElement([
                'ISTJ', 'ISFJ', 'INFJ', 'INTJ',
                'ISTP', 'ISFP', 'INFP', 'INTP',
                'ESTP', 'ESFP', 'ENFP', 'ENTP',
                'ESTJ', 'ESFJ', 'ENFJ', 'ENTJ',
            ]),
            'region'    => $this->faker->randomElement([
                '서울', '경기', '인천', '부산', '대구', '대전', '광주', '울산',
                '세종', '강원', '충북', '충남', '전북', '전남', '경북', '경남', '제주',
            ]),
            'is_smoker' => $this->faker->boolean(),
            // 추가 프로필 (모든 유형 공통) — 장점/취미/이상형/TMI
            'merit'      => $this->faker->randomElement(self::MERITS),
            'hobby'      => $this->faker->randomElement(self::HOBBIES),
            'ideal_type' => $this->faker->randomElement(self::IDEAL_TYPES),
            'tmi'        => $this->faker->randomElement(self::TMIS),
        ];
    }

    /**
     * 남성 이름 풀 반환. (Seeder 에서 중복 없는 배정에 사용)
     *
     * @return list<string>
     */
    public function maleNames(): array
    {
        return self::MALE_NAMES;
    }

    /**
     * 여성 이름 풀 반환. (Seeder 에서 중복 없는 배정에 사용)
     *
     * @return list<string>
     */
    public function femaleNames(): array
    {
        return self::FEMALE_NAMES;
    }

    /**
     * 남성 회원 상태. (남자 한글 이름 사용)
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes): array => [
            'gender' => Member::GENDER_MALE,
            'name'   => $this->faker->randomElement(self::MALE_NAMES),
            'height' => $this->faker->numberBetween(165, 190),
        ]);
    }

    /**
     * 여성 회원 상태. (여자 한글 이름 사용)
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes): array => [
            'gender' => Member::GENDER_FEMALE,
            'name'   => $this->faker->randomElement(self::FEMALE_NAMES),
            'height' => $this->faker->numberBetween(150, 175),
        ]);
    }
}
