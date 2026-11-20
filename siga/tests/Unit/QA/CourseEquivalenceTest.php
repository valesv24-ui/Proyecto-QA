<?php

declare(strict_types=1);

namespace Tests\Unit\QA;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\AcademicCatalog\CourseEquivalence\Domain\Entities\CourseEquivalence;

/**
 * Proyecto QA ISW-622 — Pruebas unitarias (Persona A).
 *
 * Clase bajo prueba: CourseEquivalence (equiparación entre un curso anterior y uno nuevo).
 * Técnicas: partición de equivalencia y transición de estados (Vigente → Sustituida).
 */
final class CourseEquivalenceTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function direccionesValidas(): array
    {
        return [
            'anterior a nuevo' => ['Anterior a nuevo'],
            'nuevo a anterior' => ['Nuevo a anterior'],
            'bidireccional' => ['Bidireccional'],
        ];
    }

    // PU-CE-01
    #[DataProvider('direccionesValidas')]
    public function test_una_equiparacion_nueva_queda_vigente(string $direction): void
    {
        $equivalence = CourseEquivalence::create(1, 2, $direction, 'RES-2026-01');

        $this->assertSame('Vigente', $equivalence->status());
        $this->assertFalse($equivalence->isSuperseded());
        $this->assertNull($equivalence->supersededById());
        $this->assertSame($direction, $equivalence->direction());
    }

    // PU-CE-02
    public function test_rechaza_un_curso_equiparado_consigo_mismo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El curso origen y destino no pueden ser el mismo.');

        CourseEquivalence::create(7, 7, 'Bidireccional', 'RES-2026-01');
    }

    // PU-CE-03
    public function test_rechaza_una_direccion_desconocida(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dirección inválida: Diagonal');

        CourseEquivalence::create(1, 2, 'Diagonal', 'RES-2026-01');
    }

    // PU-CE-04
    public function test_rechaza_un_estado_desconocido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Estado inválido: Anulada');

        CourseEquivalence::reconstitute(1, 1, 2, 'Bidireccional', 'RES-2026-01', 'Anulada', null);
    }

    // PU-CE-05
    public function test_una_sustituida_debe_indicar_quien_la_reemplaza(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Una equiparación Sustituida debe referenciar a la que la reemplaza.');

        CourseEquivalence::reconstitute(1, 1, 2, 'Bidireccional', 'RES-2026-01', 'Sustituida', null);
    }

    // PU-CE-06
    public function test_una_vigente_no_puede_tener_sustituto(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Una equiparación Vigente no debe tener sustituto.');

        CourseEquivalence::reconstitute(1, 1, 2, 'Bidireccional', 'RES-2026-01', 'Vigente', 9);
    }

    // PU-CE-07
    public function test_sustituir_cambia_el_estado_y_guarda_el_reemplazo(): void
    {
        $equivalence = CourseEquivalence::reconstitute(1, 1, 2, 'Bidireccional', 'RES-2026-01', 'Vigente', null);

        $equivalence->supersedeBy(2);

        $this->assertTrue($equivalence->isSuperseded());
        $this->assertSame('Sustituida', $equivalence->status());
        $this->assertSame(2, $equivalence->supersededById());
    }
}
