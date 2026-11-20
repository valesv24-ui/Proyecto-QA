<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use PHPUnit\Framework\TestCase;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\WaiverEvaluationEngine;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

/** ES-01 con la boleta SLR-002. */
final class WaiverEvaluationEngineTest extends TestCase
{
    private WaiverEvaluationEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new WaiverEvaluationEngine;
    }

    public function test_a_course_without_rules_goes_to_manual_review(): void
    {
        // Criterio de aceptación original de ES-01, conservado.
        $outcome = $this->engine->evaluate([], WaiverRule::TYPE_ONLY_PENDING_COURSE);

        self::assertSame(StudentRequest::RESULT_MANUAL_REVIEW, $outcome->result());
        self::assertNull($outcome->matchedRuleId());
    }

    public function test_an_admitted_justification_goes_to_manual_review_naming_the_rule(): void
    {
        $rules = [$this->rule(7, WaiverRule::TYPE_ONLY_PENDING_COURSE)];

        $outcome = $this->engine->evaluate($rules, WaiverRule::TYPE_ONLY_PENDING_COURSE);

        self::assertSame(StudentRequest::RESULT_MANUAL_REVIEW, $outcome->result());
        // Deja constancia de QUÉ regla la admitió.
        self::assertSame(7, $outcome->matchedRuleId());
    }

    public function test_a_justification_the_course_does_not_admit_is_denied(): void
    {
        $rules = [$this->rule(3, WaiverRule::TYPE_ONLY_PENDING_COURSE)];

        $outcome = $this->engine->evaluate($rules, WaiverRule::TYPE_PRIOR_KNOWLEDGE);

        self::assertSame(StudentRequest::RESULT_DENIED, $outcome->result());
        self::assertSame(3, $outcome->matchedRuleId());
    }

    public function test_the_special_case_rule_admits_any_justification(): void
    {
        // El sexto tipo es el comodín de Docencia.
        $rules = [$this->rule(1, WaiverRule::TYPE_SPECIAL_CASE)];

        foreach (WaiverRule::STUDENT_JUSTIFICATIONS as $justification) {
            $outcome = $this->engine->evaluate($rules, $justification);

            self::assertSame(
                StudentRequest::RESULT_MANUAL_REVIEW,
                $outcome->result(),
                "El caso especial debería admitir: {$justification}",
            );
        }
    }

    public function test_it_matches_any_of_the_configured_rules_not_just_the_first(): void
    {
        $rules = [
            $this->rule(1, WaiverRule::TYPE_ONLY_PENDING_COURSE),
            $this->rule(2, WaiverRule::TYPE_LAST_LEVEL_PARALLEL),
            $this->rule(3, WaiverRule::TYPE_PRIOR_KNOWLEDGE),
        ];

        $outcome = $this->engine->evaluate($rules, WaiverRule::TYPE_PRIOR_KNOWLEDGE);

        self::assertSame(StudentRequest::RESULT_MANUAL_REVIEW, $outcome->result());
        self::assertSame(3, $outcome->matchedRuleId());
    }

    public function test_an_inactive_rule_does_not_admit_its_own_justification(): void
    {
        $rule = $this->rule(4, WaiverRule::TYPE_DELAYED_OFFERING);
        $rule->deactivate();

        $outcome = $this->engine->evaluate([$rule], WaiverRule::TYPE_DELAYED_OFFERING);

        // El repositorio ya filtra por activas.
        self::assertSame(StudentRequest::RESULT_DENIED, $outcome->result());
    }

    public function test_the_denial_points_at_the_first_configured_rule(): void
    {
        // La primera es la de menor orden de evaluación.
        $rules = [
            $this->rule(11, WaiverRule::TYPE_ONLY_PENDING_COURSE),
            $this->rule(12, WaiverRule::TYPE_LAST_LEVEL_PARALLEL),
        ];

        $outcome = $this->engine->evaluate($rules, WaiverRule::TYPE_MINIMUM_ACADEMIC_LOAD);

        self::assertSame(StudentRequest::RESULT_DENIED, $outcome->result());
        self::assertSame(11, $outcome->matchedRuleId());
    }

    public function test_the_engine_never_approves_on_its_own(): void
    {
        // Cambio de criterio deliberado.
        $rules = [
            $this->rule(1, WaiverRule::TYPE_SPECIAL_CASE),
            $this->rule(2, WaiverRule::TYPE_ONLY_PENDING_COURSE),
        ];

        foreach (WaiverRule::STUDENT_JUSTIFICATIONS as $justification) {
            $outcome = $this->engine->evaluate($rules, $justification);

            self::assertNotSame(StudentRequest::RESULT_AUTOMATIC, $outcome->result());
        }
    }

    private function rule(int $id, string $type): WaiverRule
    {
        return WaiverRule::reconstitute(
            id: $id,
            courseId: 10,
            evaluationOrder: $id,
            type: $type,
            isActive: true,
        );
    }
}
