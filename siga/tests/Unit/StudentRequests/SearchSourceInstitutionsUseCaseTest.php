<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\SearchSourceInstitutionsUseCase;
use Src\StudentRequests\StudentRequest\Domain\Contracts\ExternalInstitutionDirectoryInterface;

/** El caso de uso en sí no tiene lógica propia. */
final class SearchSourceInstitutionsUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_returns_whatever_the_directory_port_returns(): void
    {
        $directory = Mockery::mock(ExternalInstitutionDirectoryInterface::class);
        $directory->shouldReceive('search')
            ->once()
            ->with('Universidad de Costa')
            ->andReturn(['Universidad de Costa Rica', 'Universidad de Costa Rica - Sede Occidente']);

        $useCase = new SearchSourceInstitutionsUseCase($directory);

        $result = $useCase->handle('Universidad de Costa');

        $this->assertSame(
            ['Universidad de Costa Rica', 'Universidad de Costa Rica - Sede Occidente'],
            $result,
        );
    }

    public function test_it_returns_an_empty_array_when_the_directory_has_no_matches(): void
    {
        $directory = Mockery::mock(ExternalInstitutionDirectoryInterface::class);
        $directory->shouldReceive('search')->once()->with('xyz123')->andReturn([]);

        $useCase = new SearchSourceInstitutionsUseCase($directory);

        $this->assertSame([], $useCase->handle('xyz123'));
    }
}
