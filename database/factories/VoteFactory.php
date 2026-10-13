<?php

namespace Database\Factories;

use App\Models\EventRound;
use App\Models\Member;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id'  => EventRound::factory(),
            'voter_id'  => Member::factory(),
            'target_id' => Member::factory(),
        ];
    }
}
