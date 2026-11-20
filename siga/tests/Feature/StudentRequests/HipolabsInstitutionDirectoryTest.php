<?php

declare(strict_types=1);

namespace Tests\Feature\StudentRequests;

use Illuminate\Support\Facades\Http;
use Src\StudentRequests\StudentRequest\Infrastructure\ExternalApis\HipolabsInstitutionDirectory;
use Tests\TestCase;

/** Prueba de integración contra Http::fake(), no contra la red real. */
final class HipolabsInstitutionDirectoryTest extends TestCase
{
    public function test_it_returns_the_names_from_a_successful_response(): void
    {
        Http::fake([
            'universities.hipolabs.com/*' => Http::response([
                ['name' => 'Universidad de Costa Rica', 'country' => 'Costa Rica'],
                ['name' => 'Universidad Nacional', 'country' => 'Costa Rica'],
            ], 200),
        ]);

        $directory = new HipolabsInstitutionDirectory;

        $this->assertSame(
            ['Universidad de Costa Rica', 'Universidad Nacional'],
            $directory->search('Universidad'),
        );
    }

    public function test_it_returns_an_empty_array_when_the_query_is_too_short(): void
    {
        Http::fake();

        $directory = new HipolabsInstitutionDirectory;

        $this->assertSame([], $directory->search('UC'));

        // Ni siquiera debió salir la petición.
        Http::assertNothingSent();
    }

    public function test_it_returns_an_empty_array_when_the_request_throws(): void
    {
        Http::fake(function (): void {
            throw new \RuntimeException('Connection timed out');
        });

        $directory = new HipolabsInstitutionDirectory;

        $this->assertSame([], $directory->search('Universidad de Costa Rica'));
    }

    public function test_it_returns_an_empty_array_on_a_non_successful_response(): void
    {
        Http::fake([
            'universities.hipolabs.com/*' => Http::response('Service Unavailable', 503),
        ]);

        $directory = new HipolabsInstitutionDirectory;

        $this->assertSame([], $directory->search('Universidad de Costa Rica'));
    }

    public function test_it_deduplicates_and_caps_results_at_eight(): void
    {
        $duplicated = array_fill(0, 20, ['name' => 'Universidad Repetida', 'country' => 'Costa Rica']);
        $unique = array_map(
            fn (int $i) => ['name' => "Universidad {$i}", 'country' => 'Costa Rica'],
            range(1, 20),
        );

        Http::fake([
            'universities.hipolabs.com/*' => Http::response([...$duplicated, ...$unique], 200),
        ]);

        $directory = new HipolabsInstitutionDirectory;

        $result = $directory->search('Universidad');

        $this->assertCount(8, $result);
        $this->assertSame(array_values(array_unique($result)), $result);
    }
}
