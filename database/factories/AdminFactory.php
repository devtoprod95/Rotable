<?php

namespace Database\Factories;

use App\Enums\Admin as EnumsAdmin;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'username'  => $this->faker->unique()->userName(),
            'password'  => Hash::make('1234'),
            'name'      => $this->faker->name(),
            'is_active' => $this->faker->randomElement([
                EnumsAdmin::ACTIVE->value,
                EnumsAdmin::INACTIVE->value
            ]),
        ];
    }

    /**
     * 모델 생성 후 실행되는 설정
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Admin $admin) {
            // Role 테이블에 데이터가 있다고 가정
            // 랜덤으로 1개에서 3개의 역할을 할당합니다.
            $roles = Role::inRandomOrder()->limit(rand(1, 3))->get();

            if ($roles->isNotEmpty()) {
                $admin->roles()->sync($roles->pluck('id')->toArray());
            }
        });
    }
}
