<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed configuration tables first (order matters due to foreign keys)
        $this->call([
            InstitutionSeeder::class,
            ApplicationStatusSeeder::class,
            VettingTypeSeeder::class,
            VettingStatusSeeder::class,
            DecisionValueSeeder::class,
            DocumentTypeSeeder::class,
            DecisionTypeSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // Seed test data (only in non-production environments)
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                TestUserSeeder::class,
                TestApplicationSeeder::class,
            ]);
        }
    }
}
