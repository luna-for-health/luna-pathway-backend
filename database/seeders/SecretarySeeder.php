<?php

namespace Database\Seeders;

use App\Models\Secretary;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SecretarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'secretary']);
        Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'secretary']);

        $secretaries = [
            [
                'name' => 'Admin',
                'email' => 'admin@envast.tn',
                'password' => 'testtest',
                'role' => 'admin',
            ],
            [
                'name' => 'Secretary',
                'email' => 'secretary@envast.tn',
                'password' => 'testtest',
                'role' => 'secretary',
            ],
        ];

        foreach ($secretaries as $data) {
            $secretary = Secretary::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                ],
            );

            $secretary->syncRoles([$data['role']]);
        }
    }
}
