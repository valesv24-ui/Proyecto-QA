<?php

declare(strict_types=1);

namespace Src\Shared\Holiday\Infrastructure;

use DateTimeImmutable;
use Src\Shared\Holiday\Domain\Contracts\HolidayProviderInterface;
use Src\Shared\Holiday\Domain\Services\EasterCalculator;

/** Adaptador de HolidayProviderInterface con el calendario de feriados de pago. */
final class CostaRicaHolidayProvider implements HolidayProviderInterface
{
    /**
     * Mes y día de los feriados de fecha fija.
     *
     * @var array<int, array{int, int}>
     */
    private const FIXED_HOLIDAYS = [
        [1, 1],   // Año Nuevo
        [4, 11],  // Día de Juan Santamaría
        [5, 1],   // Día Internacional del Trabajador
        [7, 25],  // Anexión del Partido de Nicoya a Costa Rica
        [8, 15],  // Día de la Madre
        [9, 15],  // Día de la Independencia
        [12, 25], // Navidad
    ];

    public function __construct(
        private readonly EasterCalculator $easter,
    ) {}

    public function isHoliday(DateTimeImmutable $date): bool
    {
        $date = $date->setTime(0, 0);

        foreach (self::FIXED_HOLIDAYS as [$month, $day]) {
            if ((int) $date->format('n') === $month && (int) $date->format('j') === $day) {
                return true;
            }
        }

        $easterSunday = $this->easter->forYear((int) $date->format('Y'));
        $holyThursday = $easterSunday->modify('-3 days');
        $goodFriday = $easterSunday->modify('-2 days');

        return $date == $holyThursday || $date == $goodFriday;
    }
}
