<?php

namespace Database\Factories;

use App\Models\Director;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Director>
 */
class DirectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'name' => $this->faker->name(),
            'fee_paid_status' => $this->faker->randomElement(['paid', 'unpaid', 'pending']),
            'managed_by' => \App\Models\User::role(['admin', 'manager', 'curator', 'worker'])->inRandomOrder()->first()?->id ?? \App\Models\User::factory(),
        ];
    }
}
