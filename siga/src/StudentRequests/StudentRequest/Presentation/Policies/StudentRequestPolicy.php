<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Presentation\Policies;

use App\Models\User;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** Registrada vía Gate::policy() en DomainServiceProvider::$domainPolicies. */
class StudentRequestPolicy
{
    /** ES-04: acceso a la bandeja centralizada. */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('student_requests.view');
    }

    /** Ver el detalle de una solicitud: Docencia con permiso. */
    public function view(User $user, StudentRequest $studentRequest): bool
    {
        if ($user->hasPermissionTo('student_requests.view')) {
            return true;
        }

        return $user->student !== null && $user->student->id === $studentRequest->studentId();
    }

    public function search(User $user): bool
    {
        return $user->hasPermissionTo('student_requests.search');
    }

    /** Presentar una solicitud nueva (ES-01/ES-02). */
    public function create(User $user): bool
    {
        return $user->student !== null;
    }

    /** RESOLVER una solicitud (ES-03): aprobar o denegar. */
    public function update(User $user, StudentRequest $studentRequest): bool
    {
        return $user->hasPermissionTo('student_requests.edit');
    }

    /** VERIFICAR una solicitud: el visto bueno de Registro Universitario. */
    public function verify(User $user, StudentRequest $studentRequest): bool
    {
        return $user->hasPermissionTo('student_requests.verify');
    }

    /** SALTARSE el circuito del trámite. */
    public function overrideWorkflow(User $user, StudentRequest $studentRequest): bool
    {
        return false;
    }

    public function exportPdf(User $user): bool
    {
        return $user->hasPermissionTo('student_requests.export_pdf');
    }

    public function exportExcel(User $user): bool
    {
        return $user->hasPermissionTo('student_requests.export_excel');
    }
}
