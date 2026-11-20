<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Services;

use DateTimeImmutable;

/** Fechas REALES de convalidación (ES-02) según el Calendario Institucional. */
final class InstitutionalRequestCalendar
{
    /**
     * @var array<int, array{
     *     cuatrimestre: string,
     *     solicitudInicio: string,
     *     solicitudFin: string,
     *     publicacion: string,
     *     revocatoriaInicio: string,
     *     revocatoriaFin: string,
     *     publicacionRevocatoria: string,
     * }>
     */
    private const TRANSFER_CYCLES = [
        [
            'cuatrimestre' => 'I Cuatrimestre 2026',
            'solicitudInicio' => '2026-02-09',
            'solicitudFin' => '2026-02-13',
            'publicacion' => '2026-03-16',
            'revocatoriaInicio' => '2026-03-16',
            'revocatoriaFin' => '2026-03-18',
            'publicacionRevocatoria' => '2026-04-14',
        ],
        [
            'cuatrimestre' => 'III Cuatrimestre 2026',
            'solicitudInicio' => '2026-10-05',
            'solicitudFin' => '2026-10-09',
            'publicacion' => '2026-11-09',
            'revocatoriaInicio' => '2026-11-09',
            'revocatoriaFin' => '2026-11-11',
            'publicacionRevocatoria' => '2026-11-30',
        ],
    ];

    /** La próxima fecha en que Registro publica resultados de convalidación. */
    public function nextTransferPublicationDate(DateTimeImmutable $now): ?DateTimeImmutable
    {
        $today = $now->setTime(0, 0);
        $candidates = [];

        foreach (self::TRANSFER_CYCLES as $cycle) {
            foreach ([$cycle['publicacion'], $cycle['publicacionRevocatoria']] as $date) {
                $candidate = new DateTimeImmutable($date);
                if ($candidate >= $today) {
                    $candidates[] = $candidate;
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static fn (DateTimeImmutable $a, DateTimeImmutable $b): int => $a <=> $b);

        return $candidates[0];
    }
}
