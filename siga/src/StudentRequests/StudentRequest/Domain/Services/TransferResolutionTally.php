<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Services;

use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** Traduce las decisiones curso por curso al único estado. */
final class TransferResolutionTally
{
    /**
     * @param  array<int, RequestedCourse>  $courses
     */
    public function isFullyResolved(array $courses): bool
    {
        if ($courses === []) {
            return false;
        }

        foreach ($courses as $course) {
            if (! $course->isResolved()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Estado que corresponde a la solicitud.
     *
     * @param  array<int, RequestedCourse>  $courses
     */
    public function statusFor(array $courses): ?string
    {
        if (! $this->isFullyResolved($courses)) {
            return null;
        }

        return $this->recognizedCount($courses) > 0
            ? StudentRequest::STATUS_APPROVED
            : StudentRequest::STATUS_DENIED;
    }

    /**
     * @param  array<int, RequestedCourse>  $courses
     */
    public function recognizedCount(array $courses): int
    {
        return count(array_filter(
            $courses,
            fn (RequestedCourse $course): bool => $course->isRecognized(),
        ));
    }

    /**
     * @param  array<int, RequestedCourse>  $courses
     */
    public function isPartial(array $courses): bool
    {
        $recognized = $this->recognizedCount($courses);

        return $this->isFullyResolved($courses)
            && $recognized > 0
            && $recognized < count($courses);
    }
}
