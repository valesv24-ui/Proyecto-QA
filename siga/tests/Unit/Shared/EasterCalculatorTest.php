<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Shared\Holiday\Domain\Services\EasterCalculator;

/** Verifica el cálculo del algoritmo anónimo gregoriano contra fechas. */
final class EasterCalculatorTest extends TestCase
{
    #[DataProvider('knownEasterDates')]
    public function test_it_computes_the_correct_easter_sunday(int $year, string $expectedDate): void
    {
        $calculator = new EasterCalculator;

        self::assertSame($expectedDate, $calculator->forYear($year)->format('Y-m-d'));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function knownEasterDates(): array
    {
        return [
            '2024' => [2024, '2024-03-31'],
            '2025' => [2025, '2025-04-20'],
            '2026' => [2026, '2026-04-05'],
            '2027' => [2027, '2027-03-28'],
            '2030' => [2030, '2030-04-21'],
        ];
    }
}
