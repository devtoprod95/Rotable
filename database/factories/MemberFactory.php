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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => EventRound::factory(),
            'phone'    => '010' . $this->faker->unique()->numerify('########'),
            'name'     => $this->faker->name(),
            // 비밀번호는 평문 저장한다.
            'password'   => 'password',
            'gender'     => $this->faker->randomElement([Member::GENDER_MALE, Member::GENDER_FEMALE]),
            'birth_year' => $this->faker->numberBetween(1980, (int) now()->year - 19),
            'job'        => $this->faker->jobTitle(),
            'height'     => $this->faker->numberBetween(150, 190),
            'mbti'       => $this->faker->randomElement([
                'ISTJ', 'ISFJ', 'INFJ', 'INTJ',
                'ISTP', 'ISFP', 'INFP', 'INTP',
                'ESTP', 'ESFP', 'ENFP', 'ENTP',
                'ESTJ', 'ESFJ', 'ENFJ', 'ENTJ',
            ]),
            'region'    => $this->faker->city(),
            'is_smoker' => $this->faker->boolean(),
        ];
    }

    /**
     * 남성 회원 상태.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes): array => [
            'gender' => Member::GENDER_MALE,
            'height' => $this->faker->numberBetween(165, 190),
        ]);
    }

    /**
     * 여성 회원 상태.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes): array => [
            'gender' => Member::GENDER_FEMALE,
            'height' => $this->faker->numberBetween(150, 175),
        ]);
    }
}
