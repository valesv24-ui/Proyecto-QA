<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseEquivalence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseEquivalence>
 */
class CourseEquivalenceFactory extends Factory
{
    protected $model = CourseEquivalence::class;

    public function definition(): array
    {
        return [
            'source_course_id' => Course::factory(),
            'target_course_id' => Course::factory(),
            'direction' => 'Anterior a nuevo',
            'resolution_number' => 'RES-'.fake()->unique()->numerify('####/##'),
            'status' => 'Vigente',
            'superseded_by_id' => null,
        ];
    }

    public function superseded(): static
    {
        return $this->state(fn () => [
            'status' => 'Sustituida',
            'superseded_by_id' => CourseEquivalence::factory(),
        ]);
    }
}
