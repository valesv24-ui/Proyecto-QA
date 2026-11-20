<?php

declare(strict_types=1);

namespace Tests\Unit\AcademicCatalog;

use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Src\AcademicCatalog\Prerequisite\Application\UseCases\ListCoursePrerequisitesUseCase;
use Src\AcademicCatalog\Prerequisite\Domain\Contracts\PrerequisiteRepositoryInterface;
use Src\AcademicCatalog\Prerequisite\Domain\Entities\Prerequisite;

/** El grafo de requisitos de la malla. */
final class PrerequisiteTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_a_course_cannot_be_its_own_prerequisite(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Prerequisite::create(studyPlanId: 1, requiredCourseId: 10, targetCourseId: 10);
    }

    public function test_the_self_reference_is_rejected_when_rehydrating_too(): void
    {
        // Invariante de EXISTENCIA, no solo de creación.
        $this->expectException(InvalidArgumentException::class);

        Prerequisite::reconstitute(id: 1, studyPlanId: 1, requiredCourseId: 10, targetCourseId: 10);
    }

    public function test_it_answers_the_two_directions_of_the_same_table(): void
    {
        $repository = Mockery::mock(PrerequisiteRepositoryInterface::class);

        // ISW-421 exige ISW-321; ISW-521 exige ISW-421.
        $repository->shouldReceive('requiredFor')->with(421, 1)->andReturn([
            Prerequisite::reconstitute(1, 1, 321, 421),
        ]);
        $repository->shouldReceive('unlockedBy')->with(421, 1)->andReturn([
            Prerequisite::reconstitute(2, 1, 421, 521),
            Prerequisite::reconstitute(3, 1, 421, 522),
        ]);

        $useCase = new ListCoursePrerequisitesUseCase($repository);

        self::assertSame([321], $useCase->requiredFor(421, 1));
        self::assertSame([521, 522], $useCase->unlockedBy(421, 1));
    }

    public function test_it_asks_for_many_courses_in_one_call(): void
    {
        // El selector de levantamiento pregunta por el catálogo entero: una
        // consulta, no una por curso.
        $repository = Mockery::mock(PrerequisiteRepositoryInterface::class);
        $repository->shouldReceive('requiredForMany')->once()->with([421, 521, 100], null)->andReturn([
            421 => [321],
            521 => [421, 422],
        ]);

        $useCase = new ListCoursePrerequisitesUseCase($repository);
        $result = $useCase->requiredForMany([421, 521, 100]);

        self::assertSame([321], $result[421]);
        self::assertSame([421, 422], $result[521]);
        // Un curso sin requisitos no aparece: la clave falta, no viene vacía.
        self::assertArrayNotHasKey(100, $result);
    }

    public function test_an_unknown_curriculum_answers_null_instead_of_false(): void
    {
        // Un curso sin requisitos registrados NO es un curso.
        $repository = Mockery::mock(PrerequisiteRepositoryInterface::class);
        $repository->shouldReceive('requiredFor')->with(999, 1)->andReturn([]);
        $repository->shouldNotReceive('exists');

        $useCase = new ListCoursePrerequisitesUseCase($repository);

        self::assertNull($useCase->confirms(1, 321, 999));
    }

    public function test_it_confirms_or_denies_when_the_curriculum_is_known(): void
    {
        $repository = Mockery::mock(PrerequisiteRepositoryInterface::class);
        $repository->shouldReceive('requiredFor')->with(421, 1)->andReturn([
            Prerequisite::reconstitute(1, 1, 321, 421),
        ]);
        $repository->shouldReceive('exists')->with(1, 321, 421)->andReturn(true);
        $repository->shouldReceive('exists')->with(1, 999, 421)->andReturn(false);

        $useCase = new ListCoursePrerequisitesUseCase($repository);

        self::assertTrue($useCase->confirms(1, 321, 421));
        // El curso sí tiene requisitos registrados.
        self::assertFalse($useCase->confirms(1, 999, 421));
    }
}
