<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Student as StudentModel;
use Illuminate\Console\Command;
use Src\StudentRequests\StudentRequest\Application\Contracts\StudentRequestNotifierInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Throwable;

/** Envía de verdad la notificación de ES-03 para una solicitud. */
final class SendTestStudentRequestNotification extends Command
{
    protected $signature = 'solicitudes:probar-correo
                            {id? : Id de la solicitud. Si se omite, se usa la más reciente.}';

    protected $description = 'Envía la notificación de cambio de estado (ES-03) de una solicitud, mostrando el error real si falla.';

    public function handle(
        StudentRequestRepositoryInterface $requests,
        StudentRequestNotifierInterface $notifier,
    ): int {
        $studentRequest = $this->resolveRequest($requests);

        if ($studentRequest === null) {
            $this->error('No se encontró la solicitud. Corré primero: php artisan migrate --seed');

            return self::FAILURE;
        }

        $this->line('Solicitud #'.$studentRequest->id().' — '.$studentRequest->type());
        $this->line('Estado: '.$studentRequest->status());

        // Se resuelve y se muestra el destinatario ANTES de enviar.
        $recipient = $this->recipientFor($studentRequest->studentId());

        if ($recipient === null) {
            $this->error('Ese estudiante no tiene cuenta de usuario, así que no hay a dónde escribir.');
            $this->line('Probá con una solicitud de un estudiante con cuenta, por ejemplo la del portal de verificación.');

            return self::FAILURE;
        }

        $this->line('Destinatario: '.$recipient);
        $this->line('Enviando con MAIL_MAILER='.config('mail.default').'...');
        $this->newLine();

        try {
            // Se prueba el acuse de recibo y no la resolución.
            $notifier->notifySubmitted($studentRequest);
        } catch (Throwable $exception) {
            // El notificador ya atrapa sus propias excepciones.
            $this->error('Falló el envío: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Envío ejecutado hacia '.$recipient.'.');
        $this->newLine();
        $this->line('Si no aparece en la bandeja, revisá storage/logs/laravel.log:');
        $this->line('el notificador registra ahí cualquier fallo de SMTP con el id de la solicitud.');

        return self::SUCCESS;
    }

    private function recipientFor(int $studentId): ?string
    {
        $student = StudentModel::query()->with('user')->find($studentId);

        return $student?->user?->email;
    }

    private function resolveRequest(StudentRequestRepositoryInterface $requests): ?StudentRequest
    {
        $id = $this->argument('id');

        if ($id !== null) {
            return $requests->find((int) $id);
        }

        // Sin id, la más reciente.
        $latest = $requests->paginateForInbox([], 'created_at', 'desc', 1, 1);

        return $latest['items'][0] ?? null;
    }
}
