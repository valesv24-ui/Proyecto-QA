<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'national_id' => fake()->unique()->numerify('1########'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'second_last_name' => fake()->lastName(),
            'is_active' => true,
        ];
    }

    public function withUserAccount(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory(),
        ]);
    }
}
