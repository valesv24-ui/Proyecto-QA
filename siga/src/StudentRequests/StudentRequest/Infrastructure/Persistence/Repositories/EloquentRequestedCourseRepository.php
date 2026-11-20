<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Infrastructure\Persistence\Repositories;

use App\Models\StudentRequest as StudentRequestModel;
use App\Models\StudentRequestCourse as RequestedCourseModel;
use DateTimeImmutable;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;

final class EloquentRequestedCourseRepository implements RequestedCourseRepositoryInterface
{
    public function forRequest(int $studentRequestId): array
    {
        return RequestedCourseModel::query()
            ->where('student_request_id', $studentRequestId)
            // Por id y no por created_at.
            ->orderBy('id')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function addAll(int $studentRequestId, array $courses): void
    {
        foreach ($courses as $course) {
            $this->save($studentRequestId, $course);
        }
    }

    public function save(int $studentRequestId, RequestedCourse $course): RequestedCourse
    {
        $model = $course->id() !== null
            ? RequestedCourseModel::query()->findOrFail($course->id())
            : new RequestedCourseModel;

        $model->student_request_id = $studentRequestId;
        $model->course_id = $course->courseId();
        $model->external_institution = $course->externalInstitution();
        $model->external_course_name = $course->externalCourseName();
        $model->external_course_code = $course->externalCourseCode();
        $model->external_credits = $course->externalCredits();
        $model->external_grade = $course->externalGrade();
        $model->outcome = $course->outcome();
        $model->denial_reason = $course->denialReason();
        $model->save();

        return $this->toDomain($model);
    }

    public function find(int $id): ?RequestedCourse
    {
        $model = RequestedCourseModel::query()->find($id);

        return $model === null ? null : $this->toDomain($model);
    }

    public function recordResolutionDetails(
        int $studentRequestId,
        string $resolutionNumber,
        string $sessionNumber,
        string $minutesNumber,
        DateTimeImmutable $sessionDate,
    ): void {
        StudentRequestModel::query()->where('id', $studentRequestId)->update([
            'resolution_number' => $resolutionNumber,
            'session_number' => $sessionNumber,
            'minutes_number' => $minutesNumber,
            'session_date' => $sessionDate->format('Y-m-d'),
        ]);
    }

    public function resolutionDetails(int $studentRequestId): array
    {
        $row = StudentRequestModel::query()
            ->where('id', $studentRequestId)
            ->first(['resolution_number', 'session_number', 'minutes_number', 'session_date']);

        return [
            'resolutionNumber' => $row?->resolution_number,
            'sessionNumber' => $row?->session_number,
            'minutesNumber' => $row?->minutes_number,
            'sessionDate' => $row?->session_date?->format('Y-m-d'),
        ];
    }

    private function toDomain(RequestedCourseModel $model): RequestedCourse
    {
        return RequestedCourse::reconstitute(
            id: $model->id,
            courseId: $model->course_id,
            externalInstitution: $model->external_institution,
            externalCourseName: $model->external_course_name,
            externalCourseCode: $model->external_course_code,
            externalCredits: $model->external_credits,
            // Cast explícito.
            externalGrade: $model->external_grade === null ? null : (float) $model->external_grade,
            outcome: $model->outcome,
            denialReason: $model->denial_reason,
        );
    }
}
