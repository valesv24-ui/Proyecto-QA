<?php

declare(strict_types=1);

namespace Database\Factories\Concerns;

/** Frases de N palabras para los nombres ficticios. */
trait GeneratesWordPhrases
{
    /**
     * @param  bool  $unique  Solo donde la columna tenga índice UNIQUE.
     */
    protected function wordPhrase(int $words, bool $unique = false): string
    {
        /** @var array<int, string> $parts */
        $parts = $unique
            ? fake()->unique()->words($words)
            : fake()->words($words);

        return implode(' ', $parts);
    }

    /** Igual que wordPhrase(), con la primera letra en mayúscula. */
    protected function capitalizedWordPhrase(int $words, bool $unique = false): string
    {
        return ucfirst($this->wordPhrase($words, $unique));
    }
}
