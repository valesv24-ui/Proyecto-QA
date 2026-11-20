<?php

namespace Database\Factories;

use App\Models\ExternalReferenceCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalReferenceCourse>
 */
class ExternalReferenceCourseFactory extends Factory
{
    protected $model = ExternalReferenceCourse::class;

    public function definition(): array
    {
        return [
            'institution' => 'Universidad de Costa Rica',
            'program_reference' => 'Bachillerato en Computación con varios énfasis',
            'track' => 'Bloque Común',
            'code' => 'CI'.fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->unique()->sentence(3),
            'cycle' => fake()->numberBetween(1, 8),
            'credits' => fake()->numberBetween(2, 4),
        ];
    }
}
