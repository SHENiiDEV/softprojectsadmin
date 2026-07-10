<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'status' => $this->faker->randomElement(['active', 'onboarding', 'suspended']),
            'integration_status' => $this->faker->randomElement(['completed', 'in_progress', 'pending']),
            'ubo' => $this->faker->name(),
            'mcc' => (string) $this->faker->numberBetween(1000, 9999),
            'phones' => [
                'Krisp' => $this->faker->phoneNumber(),
                'Zadarma' => $this->faker->phoneNumber(),
            ],
            'emails' => [
                'Corporate' => $this->faker->companyEmail(),
                'Private' => $this->faker->safeEmail(),
            ],
            'notes' => $this->faker->paragraph(),
            'manager_id' => \App\Models\User::role(['admin', 'manager'])->inRandomOrder()->first()?->id ?? \App\Models\User::factory(),
        ];
    }
}
