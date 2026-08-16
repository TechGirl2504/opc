<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationStatus;
use App\Models\NameChangeReason;
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
        $reason = NameChangeReason::inRandomOrder()->first();
        $fallbackReasons = [
            'To match with academic certificates',
            'To match with clan name',
            'To match with bank details',
            'To match with employers details',
            'To match with religious beliefs',
        ];

        return [
            'full_name' => fake()->name(),
            'national_id' => fake()->unique()->regexify('[A-Z0-9]{8}'),
            'date_of_birth' => fake()->date('Y-m-d', '-18 years'),
            'phone_number' => fake()->optional()->numerify('09########'),
            'district' => fake()->city(),
            'traditional_authority' => fake()->lastName() . ' T/A',
            'village' => fake()->citySuffix() . ' Village',
            'requested_name' => fake()->name(),
            'name_change_reason_id' => $reason?->id,
            'reason' => $reason?->name ?? fake()->randomElement($fallbackReasons),
            'status_id' => ApplicationStatus::inRandomOrder()->first()?->id ?? 1,
            'created_by' => User::factory(),
            'submitted_at' => now(),
        ];
    }
}
