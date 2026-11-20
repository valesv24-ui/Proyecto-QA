<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

/** StudentRequest — Aggregate Root del módulo. */
final class StudentRequest
{
    public const TYPE_WAIVER = 'Levantamiento de requisito';

    public const TYPE_TRANSFER = 'Convalidación';

    public const RESULT_AUTOMATIC = 'Procede automáticamente';

    public const RESULT_DENIED = 'No procede';

    public const RESULT_MANUAL_REVIEW = 'Requiere revisión manual';

    public const STATUS_PENDING = 'Pendiente de revisión';

    /** Visto bueno de Registro Universitario. */
    public const STATUS_VERIFIED = 'Verificada por Registro';

    public const STATUS_IN_REVIEW = 'En revisión';

    /** Docencia ya resolvió y la solicitud volvió al escritorio. */
    public const STATUS_DECIDED = 'Resuelta por Docencia';

    public const STATUS_APPROVED = 'Aprobada';

    public const STATUS_DENIED = 'Denegada';

    private const TYPES = [self::TYPE_WAIVER, self::TYPE_TRANSFER];

    /**
     * Todos los estados del circuito, en orden.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VERIFIED,
        self::STATUS_IN_REVIEW,
        self::STATUS_DECIDED,
        self::STATUS_APPROVED,
        self::STATUS_DENIED,
    ];

    /**
     * Trámites todavía abiertos.
     *
     * @var array<int, string>
     */
    public const OPEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VERIFIED,
        self::STATUS_IN_REVIEW,
        self::STATUS_DECIDED,
    ];

    /**
     * Trámites ya cerrados: el archivo histórico de Registro y Docencia.
     *
     * @var array<int, string>
     */
    public const RESOLVED_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_DENIED,
    ];

    /**
     * Estados que le tocan a REGISTRO en la bandeja.
     *
     * @var array<int, string>
     */
    public const REGISTRAR_DESK_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_DECIDED,
        self::STATUS_APPROVED,
        self::STATUS_DENIED,
    ];

    /**
     * Estados que le tocan a DOCENCIA en la bandeja.
     *
     * @var array<int, string>
     */
    public const TEACHING_DESK_STATUSES = [
        self::STATUS_VERIFIED,
        self::STATUS_IN_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_DENIED,
    ];

    /** Lo que ve el estudiante, no el estado interno. */
    public const PUBLIC_RECEIVED = 'Recibida';

    public const PUBLIC_IN_PROGRESS = 'En trámite';

    /**
     * Transiciones permitidas del circuito real.
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_VERIFIED, self::STATUS_DENIED],
        self::STATUS_VERIFIED => [self::STATUS_IN_REVIEW, self::STATUS_DENIED],
        // Docencia resuelve y devuelve a Registro.
        self::STATUS_IN_REVIEW => [self::STATUS_DECIDED, self::STATUS_DENIED],
        // Registro publica lo que Docencia ya decidió.
        self::STATUS_DECIDED => [self::STATUS_APPROVED, self::STATUS_DENIED],
        self::STATUS_APPROVED => [],
        self::STATUS_DENIED => [],
    ];

    private function __construct(
        private readonly ?int $id,
        private readonly int $studentId,
        private readonly string $type,
        private readonly int $courseId,
        private readonly ?int $prerequisiteCourseId,
        private readonly ?string $sourceInstitution,
        private readonly ?string $externalCourse,
        private ?int $transferCreditPrecedentId,
        private ?string $engineResult,
        private ?int $failedRuleId,
        private string $status,
        private ?DateTimeImmutable $estimatedResolutionDate,
        private ?int $reviewerId,
        private readonly ?DateTimeImmutable $createdAt,
        private readonly ?string $justification = null,
        private readonly ?DateTimeImmutable $termsAcceptedAt = null,
        private ?string $teachingDecision = null,
    ) {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Tipo de solicitud inválido: {$type}");
        }
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Estado inválido: {$status}");
        }
        // Una convalidación exige institución y curso externo.
        if ($type === self::TYPE_TRANSFER && ($sourceInstitution === null || $externalCourse === null)) {
            throw new InvalidArgumentException('Una Convalidación exige institución y curso externo.');
        }
        if ($type === self::TYPE_WAIVER && $prerequisiteCourseId === null) {
            throw new InvalidArgumentException('Un Levantamiento de requisito exige el requisito no cumplido.');
        }
        // Justificación y aceptación solo se exigen al presentar.
    }

    /** Único camino para presentar un levantamiento NUEVO. */
    public static function forWaiver(
        int $studentId,
        int $courseId,
        int $prerequisiteCourseId,
        string $justification,
        DateTimeImmutable $termsAcceptedAt,
    ): self {
        // Las justificaciones válidas viven en WaiverRule.
        if (! in_array($justification, WaiverRule::STUDENT_JUSTIFICATIONS, true)) {
            throw new InvalidArgumentException("La justificación no corresponde a la boleta SLR-002: {$justification}");
        }

        return new self(
            id: null,
            studentId: $studentId,
            type: self::TYPE_WAIVER,
            courseId: $courseId,
            prerequisiteCourseId: $prerequisiteCourseId,
            sourceInstitution: null,
            externalCourse: null,
            transferCreditPrecedentId: null,
            engineResult: null,
            failedRuleId: null,
            status: self::STATUS_PENDING,
            estimatedResolutionDate: null,
            reviewerId: null,
            createdAt: null,
            justification: $justification,
            termsAcceptedAt: $termsAcceptedAt,
        );
    }

    public static function forTransfer(int $studentId, int $courseId, string $sourceInstitution, string $externalCourse): self
    {
        return new self(
            id: null,
            studentId: $studentId,
            type: self::TYPE_TRANSFER,
            courseId: $courseId,
            prerequisiteCourseId: null,
            sourceInstitution: $sourceInstitution,
            externalCourse: $externalCourse,
            transferCreditPrecedentId: null,
            engineResult: null,
            failedRuleId: null,
            status: self::STATUS_PENDING,
            estimatedResolutionDate: null,
            reviewerId: null,
            createdAt: null,
        );
    }

    public static function reconstitute(
        int $id,
        int $studentId,
        string $type,
        int $courseId,
        ?int $prerequisiteCourseId,
        ?string $sourceInstitution,
        ?string $externalCourse,
        ?int $transferCreditPrecedentId,
        ?string $engineResult,
        ?int $failedRuleId,
        string $status,
        ?DateTimeImmutable $estimatedResolutionDate,
        ?int $reviewerId,
        ?DateTimeImmutable $createdAt = null,
        ?string $justification = null,
        ?DateTimeImmutable $termsAcceptedAt = null,
        ?string $teachingDecision = null,
    ): self {
        return new self(
            $id, $studentId, $type, $courseId, $prerequisiteCourseId, $sourceInstitution, $externalCourse,
            $transferCreditPrecedentId, $engineResult, $failedRuleId, $status, $estimatedResolutionDate, $reviewerId,
            $createdAt, $justification, $termsAcceptedAt, $teachingDecision,
        );
    }

    /** ES-01: primer resultado concluyente del motor de reglas. */
    public function resolveByEngine(string $result, ?int $failedRuleId = null): void
    {
        if (! in_array($result, [self::RESULT_AUTOMATIC, self::RESULT_DENIED, self::RESULT_MANUAL_REVIEW], true)) {
            throw new InvalidArgumentException("Resultado de motor inválido: {$result}");
        }

        $this->engineResult = $result;
        $this->failedRuleId = $result === self::RESULT_DENIED ? $failedRuleId : null;

        if ($result === self::RESULT_AUTOMATIC) {
            $this->status = self::STATUS_APPROVED;
        } elseif ($result === self::RESULT_DENIED) {
            $this->status = self::STATUS_DENIED;
        }
        // Revisión manual deja el estado pendiente para Docencia.
    }

    /** Justificación de la boleta SLR-002 que marcó el estudiante. */
    public function justification(): ?string
    {
        return $this->justification;
    }

    /** Momento en que el estudiante aceptó las notas importantes. */
    public function termsAcceptedAt(): ?DateTimeImmutable
    {
        return $this->termsAcceptedAt;
    }

    public function attachPrecedent(int $transferCreditPrecedentId): void
    {
        $this->transferCreditPrecedentId = $transferCreditPrecedentId;
    }

    /** Estado que ve el estudiante: tres momentos. */
    public function publicStatus(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => self::PUBLIC_RECEIVED,
            self::STATUS_APPROVED, self::STATUS_DENIED => $this->status,
            default => self::PUBLIC_IN_PROGRESS,
        };
    }

    /** ¿El trámite terminó?. */
    public function isResolved(): bool
    {
        // Misma lista que usa el Historial.
        return in_array($this->status, self::RESOLVED_STATUSES, true);
    }

    public function isOpen(): bool
    {
        return ! $this->isResolved();
    }

    /** El revisor cambia el estado, respetando el grafo. */
    public function changeStatus(string $newStatus, int $reviewerId, bool $bypassWorkflow = false): void
    {
        if (! in_array($newStatus, self::STATUSES, true)) {
            throw new InvalidArgumentException("Estado inválido: {$newStatus}");
        }

        if ($bypassWorkflow) {
            $this->status = $newStatus;
            $this->reviewerId = $reviewerId;

            return;
        }

        // Denegar se admite desde cualquier estado abierto.
        if (! in_array($newStatus, self::ALLOWED_TRANSITIONS[$this->status], true)) {
            throw new InvalidArgumentException(
                "No se puede pasar de «{$this->status}» a «{$newStatus}»."
            );
        }

        $this->status = $newStatus;
        $this->reviewerId = $reviewerId;
    }

    /** Docencia resuelve; el estado queda esperando publicación. */
    public function decideByTeaching(string $decision, int $reviewerId): void
    {
        if (! in_array($decision, [self::STATUS_APPROVED, self::STATUS_DENIED], true)) {
            throw new InvalidArgumentException("Decisión inválida: {$decision}");
        }

        $this->changeStatus(self::STATUS_DECIDED, $reviewerId);
        $this->teachingDecision = $decision;
    }

    /** Registro publica lo que Docencia resolvió. */
    public function publish(int $reviewerId): void
    {
        if ($this->teachingDecision === null) {
            throw new InvalidArgumentException('No hay decisión de Docencia que publicar.');
        }

        $this->changeStatus($this->teachingDecision, $reviewerId);
    }

    public function teachingDecision(): ?string
    {
        return $this->teachingDecision;
    }

    /** ¿Está esperando que Registro publique?. */
    public function awaitsPublication(): bool
    {
        return $this->status === self::STATUS_DECIDED;
    }

    /**
     * Estados a los que esta solicitud puede pasar ahora mismo.
     *
     * @return array<int, string>
     */
    public function allowedNextStatuses(): array
    {
        return self::ALLOWED_TRANSITIONS[$this->status];
    }

    /**
     * Todos los estados menos el actual.
     *
     * @return array<int, string>
     */
    public function anyOtherStatus(): array
    {
        return array_values(array_filter(
            self::STATUSES,
            fn (string $status): bool => $status !== $this->status,
        ));
    }

    public function assignEstimatedResolutionDate(DateTimeImmutable $date): void
    {
        $this->estimatedResolutionDate = $date;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function studentId(): int
    {
        return $this->studentId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function courseId(): int
    {
        return $this->courseId;
    }

    public function prerequisiteCourseId(): ?int
    {
        return $this->prerequisiteCourseId;
    }

    public function sourceInstitution(): ?string
    {
        return $this->sourceInstitution;
    }

    public function externalCourse(): ?string
    {
        return $this->externalCourse;
    }

    public function transferCreditPrecedentId(): ?int
    {
        return $this->transferCreditPrecedentId;
    }

    public function engineResult(): ?string
    {
        return $this->engineResult;
    }

    public function failedRuleId(): ?int
    {
        return $this->failedRuleId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function estimatedResolutionDate(): ?DateTimeImmutable
    {
        return $this->estimatedResolutionDate;
    }

    public function reviewerId(): ?int
    {
        return $this->reviewerId;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
}
