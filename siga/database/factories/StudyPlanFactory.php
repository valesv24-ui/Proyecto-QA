<?php

namespace Database\Factories;

use App\Models\DegreeProgram;
use App\Models\StudyPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyPlan>
 */
class StudyPlanFactory extends Factory
{
    protected $model = StudyPlan::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2018, 2026);

        return [
            'degree_program_id' => DegreeProgram::factory(),
            'name' => "Plan {$year}",
            'implementation_year' => $year,
            'classification' => 'Vigente',
            'enrollment_closing_date' => null,
        ];
    }

    public function terminal(): static
    {
        return $this->state(fn () => [
            'classification' => 'Terminal',
            'enrollment_closing_date' => fake()->dateTimeBetween('now', '+2 years'),
        ]);
    }
}
