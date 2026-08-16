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
            NameChangeReasonSeeder::class,
            RejectReasonSeeder::class,
            VettingTypeSeeder::class,
            VettingStatusSeeder::class,
            DecisionValueSeeder::class,
            DocumentTypeSeeder::class,
            DecisionTypeSeeder::class,
            RolePermissionSeeder::class,
            // Optional: create initial users (staging/prod) when BOOTSTRAP_USERS=true
            BootstrapUserSeeder::class,
        ]);

        /**
         * Seed demo/test data.
         *
         * - Always in local/testing.
         * - Optional in staging if you set `SEED_TEST_DATA=true` in Coolify.
         * - Never in production unless you explicitly set the flag.
         */
        $seedTestData = app()->environment(['local', 'testing'])
            || filter_var((string) env('SEED_TEST_DATA', 'false'), FILTER_VALIDATE_BOOL);

        if ($seedTestData) {
            $this->call([
                TestUserSeeder::class,
                TestApplicationSeeder::class,
            ]);
        }
    }
}
