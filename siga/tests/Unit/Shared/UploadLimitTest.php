<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use PHPUnit\Framework\TestCase;
use Src\Shared\Attachment\Domain\Entities\Attachment;
use Src\Shared\Attachment\Presentation\Support\UploadLimit;

/** UploadLimit existe por un fallo que ninguna prueba anterior podía. */
final class UploadLimitTest extends TestCase
{
    public function test_it_never_promises_more_than_the_module_accepts(): void
    {
        // La garantía central.
        self::assertLessThanOrEqual(Attachment::MAX_SIZE_BYTES, UploadLimit::bytes());
    }

    public function test_it_never_promises_a_limit_of_zero(): void
    {
        // ini_get() devuelve false o cadena vacía si la directiva.
        self::assertGreaterThan(0, UploadLimit::bytes());
    }

    public function test_the_php_warning_matches_the_computed_limit(): void
    {
        // El aviso extra de la pantalla y el número.
        self::assertSame(
            UploadLimit::bytes() < Attachment::MAX_SIZE_BYTES,
            UploadLimit::isConstrainedByPhp(),
        );
    }

    public function test_the_label_is_whole_megabytes(): void
    {
        // El rótulo se lee, no se calcula.
        self::assertMatchesRegularExpression('/^\d+ MB$/', UploadLimit::label());
    }
}
