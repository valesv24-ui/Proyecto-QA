<?php

declare(strict_types=1);

namespace Tests\Unit\AcademicCatalog;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\AcademicCatalog\AcademicRecord\Domain\Entities\AcademicRecord;

/**
 * Qué significa cada estado del expediente para los selectores.
 *
 * El estado Matriculado se agregó para cargar las materias que el estudiante
 * está cursando. El riesgo no es que falle: es que se cuele como "ganado" y
 * empiece a sumar créditos y a habilitar requisitos de materias que todavía
 * se pueden perder. Por eso se prueba estado por estado y no en bloque.
 */
final class AcademicRecordStatusTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool, 2: bool}>
     */
    public static function statuses(): array
    {
        //                            estado                          ganado, en curso
        return [
            'aprobado' => ['Aprobado', true, false],
            'reprobado' => ['Reprobado', false, false],
            'equiparación' => ['Acreditado por equiparación', true, false],
            'convalidación' => ['Acreditado por convalidación', true, false],
            'requisito levantado' => ['Requisito levantado', true, false],
            'matriculado' => ['Matriculado', false, true],
        ];
    }

    #[DataProvider('statuses')]
    public function test_each_status_says_whether_the_course_is_earned_or_in_progress(
        string $status,
        bool $earned,
        bool $inProgress,
    ): void {
        $record = AcademicRecord::create(1, 10, null, $status, null);

        self::assertSame($earned, $record->isPassed(), "«{$status}» ganado");
        self::assertSame($inProgress, $record->isInProgress(), "«{$status}» en curso");
    }

    #[DataProvider('statuses')]
    public function test_only_a_failed_course_can_be_requested_again(
        string $status,
        bool $earned,
        bool $inProgress,
    ): void {
        // Ganado o en curso, el trámite no tiene objeto. Reprobado sí: se
        // puede convalidar después uno equivalente de otra institución.
        $record = AcademicRecord::create(1, 10, null, $status, null);

        self::assertSame($earned || $inProgress, $record->blocksNewRequest());
    }

    public function test_an_enrolled_course_has_no_passing_grade_yet(): void
    {
        // Sin esto, una materia en curso con nota parcial contaría para la
        // regla de "aprobado con nota mínima" del motor de ES-01.
        $record = AcademicRecord::create(1, 10, null, 'Matriculado', 9.5);

        self::assertNull($record->passingGrade());
        self::assertFalse($record->satisfiesMinimumGrade(7.0));
    }
}
