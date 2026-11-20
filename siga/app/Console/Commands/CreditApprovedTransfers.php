<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\StudentRequest as StudentRequestModel;
use Illuminate\Console\Command;
use Src\StudentRequests\StudentRequest\Application\UseCases\CreditRecognizedCoursesUseCase;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Throwable;

/** Lleva al expediente los cursos de las convalidaciones que YA. */
final class CreditApprovedTransfers extends Command
{
    protected $signature = 'convalidaciones:acreditar
                            {--dry-run : Solo informa qué se acreditaría, sin escribir nada.}';

    protected $description = 'Acredita en el expediente los cursos reconocidos de las convalidaciones ya aprobadas.';

    public function handle(CreditRecognizedCoursesUseCase $creditCourses): int
    {
        // Solo APROBADAS. Una convalidación denegada no acredita nada.
        $approved = StudentRequestModel::query()
            ->where('type', StudentRequest::TYPE_TRANSFER)
            ->where('status', StudentRequest::STATUS_APPROVED)
            ->orderBy('id')
            ->get(['id', 'student_id']);

        if ($approved->isEmpty()) {
            $this->info('No hay convalidaciones aprobadas.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("{$approved->count()} convalidaciones aprobadas por revisar. Sin --dry-run se acreditarían sus cursos reconocidos que aún no estén en el expediente.");

            return self::SUCCESS;
        }

        $credited = 0;
        $failed = 0;

        foreach ($approved as $request) {
            try {
                $credited += $creditCourses->handle(
                    studentRequestId: (int) $request->id,
                    studentId: (int) $request->student_id,
                );
            } catch (Throwable $exception) {
                // Una solicitud rota no puede costar las demás.
                $failed++;
                $this->warn("Solicitud #{$request->id}: {$exception->getMessage()}");
            }
        }

        // El total dice cursos y no solicitudes a propósito.
        $this->info("{$credited} cursos acreditados en {$approved->count()} convalidaciones revisadas.");

        if ($failed > 0) {
            $this->warn("{$failed} solicitudes no se pudieron procesar (ver arriba).");
        }

        return self::SUCCESS;
    }
}
