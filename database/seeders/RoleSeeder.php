<?php

namespace Database\Seeders;

use App\Enums\Role as EnumsRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    # php artisan db:seed --class=RoleSeeder
    public function run(): void
    {
        foreach (EnumsRole::cases() as $role) {
            Role::create([
                'name'         => $role->value,
                'display_name' => $role->label(),
            ]);
        }
    }
}
