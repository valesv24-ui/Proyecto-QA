<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    protected $model = AcademicTerm::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2020, 2027);
        $term = fake()->numberBetween(1, 3);
        $start = Carbon::create($year, $term === 1 ? 1 : ($term === 2 ? 5 : 9), 1);

        return [
            'year' => $year,
            'term_number' => $term,
            'start_date' => $start,
            'end_date' => $start->copy()->addMonths(4),
        ];
    }
}
