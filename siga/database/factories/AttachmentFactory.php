<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\StudentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => null,
            'attachable_type' => StudentRequest::class,
            'attachable_id' => StudentRequest::factory(),
            'document_type' => 'Certificación',
            'original_name' => fake()->word().'.pdf',
            'disk' => 'local',
            'path' => 'attachments/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(10_000, 5_000_000),
            'sha256_hash' => hash('sha256', fake()->uuid()),
        ];
    }
}
