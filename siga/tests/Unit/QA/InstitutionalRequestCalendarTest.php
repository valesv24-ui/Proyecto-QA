<?php

declare(strict_types=1);

namespace Tests\Unit\QA;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\StudentRequests\StudentRequest\Domain\Services\InstitutionalRequestCalendar;

/**
 * Proyecto QA ISW-622 — Pruebas unitarias (Persona A).
 *
 * Clase bajo prueba: InstitutionalRequestCalendar (próxima publicación de resultados de convalidación, ES-02).
 * Técnicas: valores límite sobre las fechas del Calendario Institucional 2026.
 */
final class InstitutionalRequestCalendarTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string}>
     */
    public static function fechas(): array
    {
        return [
            'antes del I cuatrimestre' => ['2026-01-15 09:00', '2026-03-16'],
            'el mismo día de la publicación' => ['2026-03-16 08:00', '2026-03-16'],
            'al final del día de la publicación' => ['2026-03-16 23:59', '2026-03-16'],
            'un día después de la publicación' => ['2026-03-17 08:00', '2026-04-14'],
            'el día de publicación de revocatoria' => ['2026-04-14 10:00', '2026-04-14'],
            'entre cuatrimestres' => ['2026-06-01 10:00', '2026-11-09'],
            'durante el período de solicitud del III cuatrimestre' => ['2026-10-07 10:00', '2026-11-09'],
            'después de la publicación del III cuatrimestre' => ['2026-11-20 13:00', '2026-11-30'],
            'el último día del calendario' => ['2026-11-30 17:00', '2026-11-30'],
            'después del último día del calendario' => ['2026-12-01 08:00', null],
        ];
    }

    // PU-IC-01
    #[DataProvider('fechas')]
    public function test_devuelve_la_proxima_fecha_de_publicacion(string $now, ?string $expected): void
    {
        $calendar = new InstitutionalRequestCalendar();

        $next = $calendar->nextTransferPublicationDate(new DateTimeImmutable($now));

        $this->assertSame($expected, $next?->format('Y-m-d'));
    }
}
