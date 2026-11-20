<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Shared\Holiday\Domain\Services\EasterCalculator;
use Src\Shared\Holiday\Infrastructure\CostaRicaHolidayProvider;

/** Tarea 5.5, criterio de aceptación. */
final class CostaRicaHolidayProviderTest extends TestCase
{
    #[DataProvider('holidays2026')]
    public function test_it_recognizes_2026_holidays(string $date): void
    {
        $provider = new CostaRicaHolidayProvider(new EasterCalculator);

        self::assertTrue($provider->isHoliday(new DateTimeImmutable($date)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function holidays2026(): array
    {
        return [
            'Año Nuevo' => ['2026-01-01'],
            'Jueves Santo (móvil, derivado de Pascua 2026-04-05)' => ['2026-04-02'],
            'Viernes Santo (móvil, derivado de Pascua 2026-04-05)' => ['2026-04-03'],
            'Día de Juan Santamaría' => ['2026-04-11'],
            'Día Internacional del Trabajador' => ['2026-05-01'],
            'Anexión de Nicoya' => ['2026-07-25'],
            'Día de la Madre' => ['2026-08-15'],
            'Independencia' => ['2026-09-15'],
            'Navidad' => ['2026-12-25'],
        ];
    }

    #[DataProvider('ordinaryDays2026')]
    public function test_it_does_not_flag_ordinary_days_as_holidays(string $date): void
    {
        $provider = new CostaRicaHolidayProvider(new EasterCalculator);

        self::assertFalse($provider->isHoliday(new DateTimeImmutable($date)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function ordinaryDays2026(): array
    {
        return [
            'un martes cualquiera' => ['2026-08-11'],
            'el día antes del Jueves Santo' => ['2026-04-01'],
            'el día después del Viernes Santo (Sábado Santo, no es feriado de pago obligatorio)' => ['2026-04-04'],
            'un viernes cualquiera de noviembre' => ['2026-11-20'],
        ];
    }

    public function test_the_time_of_day_does_not_affect_the_comparison(): void
    {
        $provider = new CostaRicaHolidayProvider(new EasterCalculator);

        self::assertTrue($provider->isHoliday(new DateTimeImmutable('2026-12-25 23:59:00')));
    }
}
