<?php

namespace Database\Factories;

use App\Models\EventRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventRound>
 */
class EventRoundFactory extends Factory
{
    protected $model = EventRound::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'      => '로테이블 ' . $this->faker->randomElement(['와인파티', '1:1 매칭']),
            'type'       => $this->faker->randomElement([EventRound::TYPE_WINE, EventRound::TYPE_ONE]),
            'status'     => EventRound::STATUS_PENDING,
            'event_time' => $this->faker->dateTimeBetween('now', '+2 months'),
        ];
    }

    /**
     * 진행중 상태.
     */
    public function started(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventRound::STATUS_STARTED,
        ]);
    }

    /**
     * 종료 상태.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventRound::STATUS_CLOSED,
        ]);
    }
}
