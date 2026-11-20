<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Student;
use App\Models\StudentRequest;
use Database\Factories\Concerns\GeneratesWordPhrases;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentRequest>
 */
class StudentRequestFactory extends Factory
{
    use GeneratesWordPhrases;

    protected $model = StudentRequest::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'type' => 'Levantamiento de requisito',
            'course_id' => Course::factory(),
            'prerequisite_course_id' => Course::factory(),
            'source_institution' => null,
            'external_course' => null,
            'transfer_credit_precedent_id' => null,
            'engine_result' => null,
            'failed_rule_id' => null,
            'status' => 'Pendiente de revisión',
            'estimated_resolution_date' => null,
            'reviewer_id' => null,
        ];
    }

    public function waiver(): static
    {
        return $this->state(fn () => [
            'type' => 'Levantamiento de requisito',
            'prerequisite_course_id' => Course::factory(),
            'source_institution' => null,
            'external_course' => null,
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn () => [
            'type' => 'Convalidación',
            'prerequisite_course_id' => null,
            'source_institution' => fake()->company(),
            'external_course' => $this->capitalizedWordPhrase(3),
        ]);
    }

    public function resolved(string $status = 'Aprobada'): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
