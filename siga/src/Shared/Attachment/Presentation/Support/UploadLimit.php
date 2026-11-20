<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Presentation\Support;

use Src\Shared\Attachment\Domain\Entities\Attachment;

/** El límite de subida REAL. */
final class UploadLimit
{
    /** Bytes que se pueden subir de verdad en esta instalación. */
    public static function bytes(): int
    {
        $candidates = [Attachment::MAX_SIZE_BYTES];

        foreach (['upload_max_filesize', 'post_max_size'] as $directive) {
            $value = self::iniBytes($directive);

            if ($value > 0) {
                $candidates[] = $value;
            }
        }

        return min($candidates);
    }

    /** Etiqueta corta para la interfaz, en MB enteros. */
    public static function label(): string
    {
        return (string) (int) floor(self::bytes() / 1024 / 1024).' MB';
    }

    /** ¿PHP está por debajo de lo que el módulo acepta?. */
    public static function isConstrainedByPhp(): bool
    {
        return self::bytes() < Attachment::MAX_SIZE_BYTES;
    }

    /** Convierte "2M", "8M", "512K" o "1G" a bytes. */
    private static function iniBytes(string $directive): int
    {
        $raw = trim((string) ini_get($directive));

        if ($raw === '') {
            return 0;
        }

        $value = (int) $raw;

        return match (strtolower(substr($raw, -1))) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
