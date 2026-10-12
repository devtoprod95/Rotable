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
            'password' => 'password',
        ];
    }
}
