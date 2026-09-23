<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\PciDssDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PciDssDocument>
 */
class PciDssDocumentFactory extends Factory
{
    protected $model = PciDssDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['PCI DSS', 'AOC', 'Scan', 'SAQ', 'Agreement gateway', 'Other'];
        $type = $this->faker->randomElement($types);

        return [
            'client_id' => Client::factory(),
            'project_id' => null,
            'document_type' => $type,
            'custom_type' => $type === 'Other' ? 'Custom Certificate' : null,
            'title' => $this->faker->words(3, true),
            'file_path' => 'pci_dss_documents/sample_'.$this->faker->uuid().'.pdf',
            'file_name' => 'sample_'.$this->faker->uuid().'.pdf',
            'file_size' => $this->faker->numberBetween(10240, 5242880),
            'mime_type' => 'application/pdf',
            'valid_until' => $this->faker->optional()->dateTimeBetween('+1 month', '+1 year'),
            'notes' => $this->faker->optional()->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }
}
