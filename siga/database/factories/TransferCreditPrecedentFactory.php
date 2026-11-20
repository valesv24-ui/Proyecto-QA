<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\TransferCreditPrecedent;
use Database\Factories\Concerns\GeneratesWordPhrases;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferCreditPrecedent>
 */
class TransferCreditPrecedentFactory extends Factory
{
    use GeneratesWordPhrases;

    protected $model = TransferCreditPrecedent::class;

    public function definition(): array
    {
        return [
            'institution' => fake()->company(),
            'external_course' => $this->capitalizedWordPhrase(3),
            'course_id' => Course::factory(),
            'outcome' => 'Aprobada',
            'resolution_number' => 'CTC-'.fake()->unique()->numerify('####-###'),
        ];
    }

    public function denied(): static
    {
        return $this->state(fn () => ['outcome' => 'Denegada']);
    }
}
