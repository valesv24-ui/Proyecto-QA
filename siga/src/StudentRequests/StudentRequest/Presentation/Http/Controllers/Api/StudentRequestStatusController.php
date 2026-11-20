<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Presentation\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\AcademicCatalog\Student\Domain\Contracts\StudentRepositoryInterface;
use Src\StudentRequests\StudentRequest\Application\UseCases\ListStudentRequestsForStudentUseCase;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** GET /api/student-requests, protegido por AuthenticateJwt. */
final class StudentRequestStatusController
{
    public function __construct(
        private readonly StudentRepositoryInterface $students,
        private readonly ListStudentRequestsForStudentUseCase $listRequests,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user !== null ? $this->students->findByUserId($user->id) : null;

        if ($student === null || $student->id() === null) {
            return response()->json([
                'message' => 'La cuenta autenticada no tiene un expediente de estudiante asociado.',
            ], 403);
        }

        $requests = $this->listRequests->handle($student->id());

        return response()->json([
            'data' => array_map($this->toArray(...), $requests),
        ]);
    }

    /**
     * @return array{id: ?int, type: string, status: string, estimated_resolution_date: ?string, created_at: ?string}
     */
    private function toArray(StudentRequest $studentRequest): array
    {
        return [
            'id' => $studentRequest->id(),
            'type' => $studentRequest->type(),
            'status' => $studentRequest->status(),
            'estimated_resolution_date' => $studentRequest->estimatedResolutionDate()?->format('Y-m-d'),
            'created_at' => $studentRequest->createdAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
