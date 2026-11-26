<?php

namespace Database\Factories;

use App\Models\ScamLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScamLink>
 */
class ScamLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'domain' => $this->faker->unique()->domainWord().'-'.$this->faker->unique()->numberBetween(100, 99999).'.com',
            'note' => null,
            'created_by' => null,
        ];
    }
}
