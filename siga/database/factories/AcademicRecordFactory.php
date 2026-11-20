<?php

namespace Database\Factories;

use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicRecord>
 */
class AcademicRecordFactory extends Factory
{
    protected $model = AcademicRecord::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'academic_term_id' => null,
            'status' => 'Aprobado',
            'grade' => fake()->randomFloat(2, 70, 100),
            'course_equivalence_id' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'Reprobado',
            'grade' => fake()->randomFloat(2, 0, 69.9),
        ]);
    }

    public function waived(): static
    {
        return $this->state(fn () => [
            'status' => 'Requisito levantado',
            'grade' => null,
        ]);
    }
}
