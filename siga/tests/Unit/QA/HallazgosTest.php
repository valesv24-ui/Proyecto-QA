<?php

declare(strict_types=1);

namespace Tests\Unit\QA;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Src\AcademicCatalog\CourseEquivalence\Domain\Entities\CourseEquivalence;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;

/**
 * Proyecto QA ISW-622 — Pruebas unitarias que evidencian defectos (Persona A).
 *
 * Cada prueba describe el comportamiento ESPERADO. Mientras el defecto exista, la prueba falla.
 * Cuando el equipo de SIGA lo corrija, la prueba pasa y sirve como prueba de regresión.
 *
 * Ejecutar solo este grupo:   php artisan test --group=hallazgos
 * Ejecutar sin este grupo:    php artisan test --exclude-group=hallazgos
 */
#[Group('hallazgos')]
final class HallazgosTest extends TestCase
{
    // DEF-002 — Una equiparación puede quedar sustituida por sí misma.
    public function test_una_equiparacion_no_puede_sustituirse_a_si_misma(): void
    {
        $equivalence = CourseEquivalence::reconstitute(5, 1, 2, 'Bidireccional', 'RES-2026-01', 'Vigente', null);

        $this->expectException(InvalidArgumentException::class);

        $equivalence->supersedeBy(5);
    }

    // DEF-003 — Una equiparación ya sustituida se puede sustituir otra vez y pierde la referencia original.
    public function test_una_equiparacion_sustituida_no_puede_sustituirse_de_nuevo(): void
    {
        $equivalence = CourseEquivalence::reconstitute(5, 1, 2, 'Bidireccional', 'RES-2026-01', 'Sustituida', 8);

        $this->expectException(InvalidArgumentException::class);

        $equivalence->supersedeBy(9);
    }

    // DEF-004 — Un precedente de convalidación acepta institución, curso y número de resolución vacíos,
    // mientras que RequestedCourse sí exige institución y curso.
    public function test_un_precedente_exige_institucion_curso_y_resolucion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TransferCreditPrecedent::create('', '', 10, 'Aprobada', '');
    }

    // DEF-005 — La validación de la nota (0 a 100) deja pasar NAN, porque NAN < 0 y NAN > 100 son falsos.
    public function test_rechaza_una_nota_que_no_es_un_numero(): void
    {
        $course = RequestedCourse::requested(10, 'UCR', 'Cálculo I');

        $this->expectException(InvalidArgumentException::class);

        $course->completeExternalData('MA-1001', 4, NAN);
    }
}
