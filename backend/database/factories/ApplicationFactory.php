<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'national_id' => fake()->regexify('[A-Z0-9]{8}'),
            'current_name' => fake()->optional()->name(),
            'requested_name' => fake()->name(),
            'reason' => fake()->paragraph(),
            'status_id' => ApplicationStatus::inRandomOrder()->first()?->id ?? 1,
            'created_by' => User::factory(),
            'submitted_at' => now(),
        ];
    }
}
