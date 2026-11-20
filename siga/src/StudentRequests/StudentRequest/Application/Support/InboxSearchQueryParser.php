<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\Support;

/** Tarea 6.2 del Plan de Trabajo. */
final class InboxSearchQueryParser
{
    /** Palabra reservada en el texto de búsqueda -> clave. */
    private const KEYWORDS = [
        'tipo' => 'type',
        'estado' => 'status',
        'carrera' => 'career',
        'desde' => 'dateFrom',
        'hasta' => 'dateTo',
    ];

    /**
     * Valores de `estado.
     *
     * @var array<int, string>
     */
    private const OPEN_ALIASES = ['abierta', 'abiertas', 'sin resolver', 'sin-resolver', 'pendientes'];

    /**
     * @return array{search?: string, type?: string, status?: string, career?: string, dateFrom?: string, dateTo?: string, openOnly?: bool}
     */
    public static function parse(string $raw): array
    {
        /** @var array<string, mixed> $filters */
        $filters = [];

        $remainder = preg_replace_callback(
            pattern: '/(tipo|estado|carrera|desde|hasta):(?:"([^"]*)"|(\S+))/iu',
            callback: function (array $match) use (&$filters): string {
                // PREG_UNMATCHED_AS_NULL.
                $keyword = mb_strtolower($match[1]);
                $value = trim($match[2] ?? $match[3] ?? '');
                $filterKey = self::KEYWORDS[$keyword];

                if ($value === '') {
                    // "tipo:" sin nada detrás no es un filtro utilizable.
                    return $match[0];
                }

                if (in_array($filterKey, ['dateFrom', 'dateTo'], true) && ! self::isValidDate($value)) {
                    // Una fecha con formato distinto de yyyy-mm-dd no se adivina.
                    return $match[0];
                }

                if ($filterKey === 'status' && in_array(mb_strtolower($value), self::OPEN_ALIASES, true)) {
                    $filters['openOnly'] = true;

                    return '';
                }

                // Si la palabra clave se repite.
                $filters[$filterKey] = $value;

                return '';
            },
            subject: $raw,
            flags: PREG_UNMATCHED_AS_NULL,
        );

        $freeText = trim(preg_replace('/\s+/u', ' ', (string) $remainder));

        if ($freeText !== '') {
            $filters['search'] = $freeText;
        }

        return self::shape($filters);
    }

    /**
     * Arma la forma tipada del retorno campo por campo.
     *
     * @param  array<string, mixed>  $filters
     * @return array{search?: string, type?: string, status?: string, career?: string, dateFrom?: string, dateTo?: string, openOnly?: bool}
     */
    private static function shape(array $filters): array
    {
        $shaped = [];

        // 'search' se arma AL FINAL a propósito.
        foreach (['type', 'status', 'career', 'dateFrom', 'dateTo'] as $key) {
            if (isset($filters[$key])) {
                $shaped[$key] = (string) $filters[$key];
            }
        }

        if (isset($filters['openOnly'])) {
            $shaped['openOnly'] = (bool) $filters['openOnly'];
        }

        if (isset($filters['search'])) {
            $shaped['search'] = (string) $filters['search'];
        }

        return $shaped;
    }

    private static function isValidDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }
}
