<?php

namespace Database\Factories;

use App\Models\DegreeProgram;
use Database\Factories\Concerns\GeneratesWordPhrases;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DegreeProgram>
 */
class DegreeProgramFactory extends Factory
{
    use GeneratesWordPhrases;

    protected $model = DegreeProgram::class;

    public function definition(): array
    {
        return [
            // degree_programs.name SÍ es UNIQUE en el esquema.
            'name' => 'Ingeniería en '.$this->wordPhrase(2, unique: true),
            'is_active' => true,
        ];
    }
}
