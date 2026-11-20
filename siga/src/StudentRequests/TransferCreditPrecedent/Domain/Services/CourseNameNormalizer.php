<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Domain\Services;

/** ES-02, tarea 4.1. */
final class CourseNameNormalizer
{
    /**
     * Números romanos I..X.
     *
     * @var array<string, string>
     */
    private const ROMAN_TO_ARABIC = [
        'i' => '1',
        'ii' => '2',
        'iii' => '3',
        'iv' => '4',
        'v' => '5',
        'vi' => '6',
        'vii' => '7',
        'viii' => '8',
        'ix' => '9',
        'x' => '10',
    ];

    /**
     * @var array<string, string>
     */
    private const ACCENTED_TO_PLAIN = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n',
    ];

    /** Forma canónica de $value para comparar por igualdad. */
    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, self::ACCENTED_TO_PLAIN);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($this->romanNumeralsToArabic($value));
    }

    private function romanNumeralsToArabic(string $value): string
    {
        $tokens = explode(' ', $value);

        foreach ($tokens as $index => $token) {
            if (isset(self::ROMAN_TO_ARABIC[$token])) {
                $tokens[$index] = self::ROMAN_TO_ARABIC[$token];
            }
        }

        return implode(' ', $tokens);
    }
}
