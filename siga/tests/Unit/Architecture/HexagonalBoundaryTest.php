<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/** Tarea 8.5 del Plan de Trabajo. */
final class HexagonalBoundaryTest extends TestCase
{
    /**
     * Se busca la sentencia "use X\" completa.
     *
     * @var array<int, string>
     */
    private const FORBIDDEN_IMPORTS = [
        'use Illuminate\\',
        'use Livewire\\',
        'use Flux\\',
    ];

    public function test_no_domain_class_imports_a_framework_namespace(): void
    {
        $domainFiles = $this->findDomainPhpFiles();

        self::assertNotEmpty($domainFiles, 'No se encontró ningún archivo Domain/ — la ruta de búsqueda puede estar mal.');

        $violations = [];

        foreach ($domainFiles as $file) {
            $contents = file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            foreach (self::FORBIDDEN_IMPORTS as $forbiddenImport) {
                if (str_contains($contents, $forbiddenImport)) {
                    $violations[] = sprintf('%s contiene "%s"', $file, rtrim($forbiddenImport, '\\'));
                }
            }
        }

        self::assertSame(
            [],
            $violations,
            "El dominio no debe importar clases del framework:\n".implode("\n", $violations),
        );
    }

    public function test_the_known_bounded_contexts_each_have_a_domain_layer(): void
    {
        foreach (['AcademicCatalog', 'IdentityAccess', 'Shared', 'StudentRequests'] as $context) {
            self::assertDirectoryExists(
                $this->srcPath().DIRECTORY_SEPARATOR.$context.DIRECTORY_SEPARATOR,
                "Bounded context esperado no encontrado: {$context}",
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function findDomainPhpFiles(): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->srcPath(), RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            // Solo el segmento "Domain" de cada bounded context, no Application/Infrastructure/Presentation.
            if (! preg_match('#[/\\\\]Domain[/\\\\]#', $fileInfo->getPathname())) {
                continue;
            }

            $files[] = $fileInfo->getPathname();
        }

        return $files;
    }

    private function srcPath(): string
    {
        return dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'src';
    }
}
