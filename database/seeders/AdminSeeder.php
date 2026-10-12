<?php

namespace Database\Seeders;

use App\Enums\Admin as EnumsAdmin;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    # php artisan db:seed --class=AdminSeeder
    public function run(): void
    {
        $admin = Admin::updateOrCreate(['username' => 'admin'], [
            'password' => Hash::make('1234'),
            'name'     => '관리자',
            'is_active'=> EnumsAdmin::ACTIVE->value,
        ]);
        $admin->roles()->sync(Role::all()->pluck('id'));

        $tester = Admin::updateOrCreate(['username' => 'tester'], [
            'password' => Hash::make('1234'),
            'name'     => '테스트사용자',
            'is_active'=> EnumsAdmin::ACTIVE->value,
        ]);
        $tester->roles()->sync(Role::first()?->id);

        Admin::factory()->count(200)->create();
    }
}
