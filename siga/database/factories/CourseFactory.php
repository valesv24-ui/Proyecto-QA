<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\DegreeProgram;
use Database\Factories\Concerns\GeneratesWordPhrases;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    use GeneratesWordPhrases;

    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'degree_program_id' => DegreeProgram::factory(),
            'code' => strtoupper(fake()->unique()->bothify('???-###')),
            // courses.code ya lleva el índice UNIQUE; el nombre.
            'name' => $this->capitalizedWordPhrase(3),
            'is_service_course' => false,
            'is_bottleneck' => false,
            'requires_lab' => false,
            'lab_type' => null,
            'is_active' => true,
        ];
    }

    public function serviceCourse(): static
    {
        return $this->state(fn () => [
            'degree_program_id' => null,
            'is_service_course' => true,
        ]);
    }

    public function requiringLab(string $labType = 'Laboratorio de cómputo'): static
    {
        return $this->state(fn () => [
            'requires_lab' => true,
            'lab_type' => $labType,
        ]);
    }
}
