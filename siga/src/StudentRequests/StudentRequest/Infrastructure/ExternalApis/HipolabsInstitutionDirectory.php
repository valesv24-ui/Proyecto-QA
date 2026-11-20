<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Infrastructure\ExternalApis;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\StudentRequests\StudentRequest\Domain\Contracts\ExternalInstitutionDirectoryInterface;
use Throwable;

/** Adaptador del puerto ExternalInstitutionDirectoryInterface contra la API pública. */
final class HipolabsInstitutionDirectory implements ExternalInstitutionDirectoryInterface
{
    /** Tope de sugerencias devueltas. */
    private const MAX_RESULTS = 8;

    /** Por debajo de esto no vale la pena ni llamar. */
    private const MIN_QUERY_LENGTH = 3;

    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        try {
            $response = Http::timeout(3)->get(
                (string) config('services.hipolabs_universities.url'),
                ['name' => $query],
            );
        } catch (Throwable $exception) {
            Log::warning('No se pudo consultar el directorio externo de instituciones.', [
                'query' => $query,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('El directorio externo de instituciones respondió con error.', [
                'query' => $query,
                'status' => $response->status(),
            ]);

            return [];
        }

        return $this->namesFrom($response->json());
    }

    /**
     * @return array<int, string>
     */
    private function namesFrom(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $names = [];

        foreach ($payload as $entry) {
            $name = is_array($entry) ? ($entry['name'] ?? null) : null;

            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        // array_slice() ya reindexa el resultado.
        return array_slice(array_unique($names), 0, self::MAX_RESULTS);
    }
}
