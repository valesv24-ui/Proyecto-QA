<?php

declare(strict_types=1);

namespace Tests\Unit\QA;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

/**
 * Proyecto QA ISW-622 — Pruebas unitarias (Persona A).
 *
 * Clase bajo prueba: WaiverRule (regla de levantamiento de requisitos, boleta SLR-002).
 * Técnicas: partición de equivalencia, valores límite y tabla de decisión.
 */
final class WaiverRuleTest extends TestCase
{
    // PU-WR-01
    public function test_una_regla_nueva_queda_activa(): void
    {
        $rule = WaiverRule::create(5, 1, WaiverRule::TYPE_ONLY_PENDING_COURSE);

        $this->assertTrue($rule->isActive());
        $this->assertNull($rule->id());
        $this->assertSame(5, $rule->courseId());
        $this->assertSame(1, $rule->evaluationOrder());
    }

    // PU-WR-02
    public function test_el_estudiante_puede_elegir_cinco_justificaciones(): void
    {
        $this->assertCount(5, WaiverRule::STUDENT_JUSTIFICATIONS);
        $this->assertNotContains(WaiverRule::TYPE_SPECIAL_CASE, WaiverRule::STUDENT_JUSTIFICATIONS);
    }

    // PU-WR-03
    public function test_docencia_puede_configurar_seis_tipos_incluido_caso_especial(): void
    {
        $this->assertCount(6, WaiverRule::TYPES);
        $this->assertContains(WaiverRule::TYPE_SPECIAL_CASE, WaiverRule::TYPES);
    }

    // PU-WR-04
    public function test_rechaza_un_tipo_de_regla_desconocido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de regla inválido');

        WaiverRule::create(5, 1, 'Porque sí');
    }

    /**
     * @return array<string, array{int}>
     */
    public static function ordenesInvalidos(): array
    {
        return [
            'cero (límite)' => [0],
            'negativo' => [-3],
        ];
    }

    // PU-WR-05
    #[DataProvider('ordenesInvalidos')]
    public function test_rechaza_un_orden_de_evaluacion_menor_que_uno(int $order): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El orden de evaluación debe ser mayor que cero.');

        WaiverRule::create(5, $order, WaiverRule::TYPE_PRIOR_KNOWLEDGE);
    }

    /**
     * Tabla de decisión de admits(): tipo de la regla × estado × justificación elegida.
     *
     * @return array<string, array{string, bool, string, bool}>
     */
    public static function tablaDeDecision(): array
    {
        return [
            'activa, misma justificación' => [WaiverRule::TYPE_DELAYED_OFFERING, true, WaiverRule::TYPE_DELAYED_OFFERING, true],
            'activa, otra justificación' => [WaiverRule::TYPE_DELAYED_OFFERING, true, WaiverRule::TYPE_PRIOR_KNOWLEDGE, false],
            'activa, caso especial admite cualquiera' => [WaiverRule::TYPE_SPECIAL_CASE, true, WaiverRule::TYPE_MINIMUM_ACADEMIC_LOAD, true],
            'inactiva, misma justificación' => [WaiverRule::TYPE_DELAYED_OFFERING, false, WaiverRule::TYPE_DELAYED_OFFERING, false],
            'inactiva, caso especial' => [WaiverRule::TYPE_SPECIAL_CASE, false, WaiverRule::TYPE_MINIMUM_ACADEMIC_LOAD, false],
        ];
    }

    // PU-WR-06
    #[DataProvider('tablaDeDecision')]
    public function test_admite_justificaciones_segun_tipo_y_estado(
        string $ruleType,
        bool $isActive,
        string $justification,
        bool $expected,
    ): void {
        $rule = WaiverRule::reconstitute(1, 5, 1, $ruleType, $isActive);

        $this->assertSame($expected, $rule->admits($justification));
    }

    // PU-WR-07
    public function test_desactivar_y_activar_cambia_si_la_regla_admite(): void
    {
        $rule = WaiverRule::create(5, 1, WaiverRule::TYPE_LAST_LEVEL_PARALLEL);

        $rule->deactivate();
        $this->assertFalse($rule->isActive());
        $this->assertFalse($rule->admits(WaiverRule::TYPE_LAST_LEVEL_PARALLEL));

        $rule->activate();
        $this->assertTrue($rule->isActive());
        $this->assertTrue($rule->admits(WaiverRule::TYPE_LAST_LEVEL_PARALLEL));
    }
}
