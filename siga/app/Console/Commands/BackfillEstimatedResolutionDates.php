<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Src\StudentRequests\StudentRequest\Application\UseCases\EnsureEstimatedResolutionDateUseCase;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Throwable;

/** Rellena la fecha estimada de resolución de las solicitudes. */
final class BackfillEstimatedResolutionDates extends Command
{
    protected $signature = 'solicitudes:asignar-fechas
                            {--dry-run : Solo informa cuántas faltan, sin escribir nada.}';

    protected $description = 'Asigna la fecha estimada de resolución (ES-03) a las solicitudes que no la tienen.';

    public function handle(
        StudentRequestRepositoryInterface $requests,
        EnsureEstimatedResolutionDateUseCase $ensureDate,
    ): int {
        $ids = $requests->idsWithoutEstimatedResolutionDate();

        if ($ids === []) {
            $this->info('No hay solicitudes sin fecha estimada.');

            return self::SUCCESS;
        }

        $this->line(count($ids).' solicitudes sin fecha estimada.');

        if ($this->option('dry-run')) {
            $this->comment('Modo simulación: no se escribió nada.');

            return self::SUCCESS;
        }

        $assigned = 0;
        $skipped = 0;

        $this->withProgressBar($ids, function (int $id) use ($ensureDate, &$assigned, &$skipped): void {
            try {
                $updated = $ensureDate->handle($id);

                // handle() es idempotente y también se retira cuando la solicitud.
                $updated->estimatedResolutionDate() !== null ? $assigned++ : $skipped++;
            } catch (Throwable) {
                $skipped++;
            }
        });

        $this->newLine(2);
        $this->info($assigned.' actualizadas.');

        if ($skipped > 0) {
            $this->warn($skipped.' omitidas (sin fecha de recepción o no encontradas).');
        }

        return self::SUCCESS;
    }
}
