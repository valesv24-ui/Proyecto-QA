<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\Contracts;

use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** Puerto de salida de las notificaciones al estudiante (ES-03). */
interface StudentRequestNotifierInterface
{
    /** Acuse de recibo: la solicitud entró al sistema. */
    public function notifySubmitted(StudentRequest $studentRequest): void;

    /** Resolución publicada: aprobada o denegada. */
    public function notifyResolved(StudentRequest $studentRequest, ?string $statement = null): void;
}
