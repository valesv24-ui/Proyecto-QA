<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Cuatrimestres del calendario académico. */
class AcademicTermSeeder extends Seeder
{
    private const FIRST_YEAR = 2023;

    private const LAST_YEAR = 2027;

    /**
     * Ventana de cada cuatrimestre como [mes-día de inicio.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private const WINDOWS = [
        1 => ['02-10', '05-20'],
        2 => ['05-25', '09-05'],
        3 => ['09-10', '12-15'],
    ];

    public function run(): void
    {
        foreach (range(self::FIRST_YEAR, self::LAST_YEAR) as $year) {
            foreach (self::WINDOWS as $number => [$start, $end]) {
                // updateOrInsert contra la clave única (year, term_number).
                DB::table('academic_terms')->updateOrInsert(
                    ['year' => $year, 'term_number' => $number],
                    [
                        'start_date' => "{$year}-{$start}",
                        'end_date' => "{$year}-{$end}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }
}
