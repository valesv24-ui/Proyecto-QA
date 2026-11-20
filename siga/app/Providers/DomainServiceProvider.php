<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Src\AcademicCatalog\AcademicRecord\Domain\Contracts\AcademicRecordRepositoryInterface;
use Src\AcademicCatalog\AcademicRecord\Infrastructure\Persistence\Repositories\EloquentAcademicRecordRepository;
use Src\AcademicCatalog\AcademicTerm\Domain\Contracts\AcademicTermRepositoryInterface;
use Src\AcademicCatalog\AcademicTerm\Infrastructure\Persistence\Repositories\EloquentAcademicTermRepository;
use Src\AcademicCatalog\Course\Domain\Contracts\CourseRepositoryInterface;
use Src\AcademicCatalog\Course\Infrastructure\Persistence\Repositories\EloquentCourseRepository;
use Src\AcademicCatalog\CourseEquivalence\Domain\Contracts\CourseEquivalenceRepositoryInterface;
use Src\AcademicCatalog\CourseEquivalence\Infrastructure\Persistence\Repositories\EloquentCourseEquivalenceRepository;
use Src\AcademicCatalog\DegreeProgram\Domain\Contracts\DegreeProgramRepositoryInterface;
use Src\AcademicCatalog\DegreeProgram\Infrastructure\Persistence\Repositories\EloquentDegreeProgramRepository;
use Src\AcademicCatalog\Level\Domain\Contracts\LevelRepositoryInterface;
use Src\AcademicCatalog\Level\Infrastructure\Persistence\Repositories\EloquentLevelRepository;
use Src\AcademicCatalog\Prerequisite\Domain\Contracts\PrerequisiteRepositoryInterface;
use Src\AcademicCatalog\Prerequisite\Infrastructure\Persistence\Repositories\EloquentPrerequisiteRepository;
use Src\AcademicCatalog\Student\Domain\Contracts\StudentRepositoryInterface;
use Src\AcademicCatalog\Student\Infrastructure\Persistence\Repositories\EloquentStudentRepository;
use Src\AcademicCatalog\StudentFile\Domain\Contracts\StudentFileRepositoryInterface;
use Src\AcademicCatalog\StudentFile\Infrastructure\Persistence\Repositories\EloquentStudentFileRepository;
use Src\AcademicCatalog\StudyPlan\Domain\Contracts\StudyPlanRepositoryInterface;
use Src\AcademicCatalog\StudyPlan\Infrastructure\Persistence\Repositories\EloquentStudyPlanRepository;
use Src\IdentityAccess\Jwt\Infrastructure\JwtTokenService;
use Src\IdentityAccess\Permission\Domain\Contracts\PermissionRepositoryInterface;
use Src\IdentityAccess\Permission\Domain\Entities\Permission;
use Src\IdentityAccess\Permission\Infrastructure\Persistence\Repositories\EloquentPermissionRepository;
use Src\IdentityAccess\Permission\Presentation\Policies\PermissionPolicy;
use Src\IdentityAccess\Role\Domain\Contracts\RoleRepositoryInterface;
use Src\IdentityAccess\Role\Domain\Entities\Role;
use Src\IdentityAccess\Role\Infrastructure\Persistence\Repositories\EloquentRoleRepository;
use Src\IdentityAccess\Role\Presentation\Policies\RolePolicy;
use Src\Shared\Attachment\Domain\Contracts\AttachmentRepositoryInterface;
use Src\Shared\Attachment\Infrastructure\Persistence\Repositories\EloquentAttachmentRepository;
use Src\Shared\Clock\Domain\Contracts\ClockInterface;
use Src\Shared\Clock\Infrastructure\SystemClock;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Src\Shared\Export\Infrastructure\SpatieExcelExporter;
use Src\Shared\Export\Infrastructure\SpatiePdfExporter;
use Src\Shared\Holiday\Domain\Contracts\HolidayProviderInterface;
use Src\Shared\Holiday\Infrastructure\CostaRicaHolidayProvider;
use Src\StudentRequests\StudentRequest\Application\Contracts\StudentRequestNotifierInterface;
use Src\StudentRequests\StudentRequest\Application\Contracts\TransferResolutionDocumentInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\ExternalInstitutionDirectoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Infrastructure\Documents\PdfTransferResolutionDocument;
use Src\StudentRequests\StudentRequest\Infrastructure\ExternalApis\HipolabsInstitutionDirectory;
use Src\StudentRequests\StudentRequest\Infrastructure\Notifications\MailStudentRequestNotifier;
use Src\StudentRequests\StudentRequest\Infrastructure\Persistence\Repositories\EloquentRequestedCourseRepository;
use Src\StudentRequests\StudentRequest\Infrastructure\Persistence\Repositories\EloquentStudentRequestRepository;
use Src\StudentRequests\StudentRequest\Presentation\Livewire\StudentRequestSummaryComponent;
use Src\StudentRequests\StudentRequest\Presentation\Policies\StudentRequestPolicy;
use Src\StudentRequests\StudentRequestStatusHistory\Domain\Contracts\StudentRequestStatusHistoryRepositoryInterface;
use Src\StudentRequests\StudentRequestStatusHistory\Infrastructure\Persistence\Repositories\EloquentStudentRequestStatusHistoryRepository;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Contracts\TransferCreditPrecedentRepositoryInterface;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Src\StudentRequests\TransferCreditPrecedent\Infrastructure\Persistence\Repositories\EloquentTransferCreditPrecedentRepository;
use Src\StudentRequests\TransferCreditPrecedent\Presentation\Policies\TransferCreditPrecedentPolicy;
use Src\StudentRequests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Src\StudentRequests\WaiverRule\Infrastructure\Persistence\Repositories\EloquentWaiverRuleRepository;
use Src\StudentRequests\WaiverRule\Presentation\Policies\WaiverRulePolicy;

final class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $domainBindings = [
        RoleRepositoryInterface::class => EloquentRoleRepository::class,
        PermissionRepositoryInterface::class => EloquentPermissionRepository::class,
        ExcelExporterInterface::class => SpatieExcelExporter::class,
        PdfExporterInterface::class => SpatiePdfExporter::class,
        AttachmentRepositoryInterface::class => EloquentAttachmentRepository::class,
        ClockInterface::class => SystemClock::class,
        HolidayProviderInterface::class => CostaRicaHolidayProvider::class,
        StudentRequestNotifierInterface::class => MailStudentRequestNotifier::class,
        ExternalInstitutionDirectoryInterface::class => HipolabsInstitutionDirectory::class,

        // AcademicCatalog.
        DegreeProgramRepositoryInterface::class => EloquentDegreeProgramRepository::class,
        AcademicTermRepositoryInterface::class => EloquentAcademicTermRepository::class,
        CourseRepositoryInterface::class => EloquentCourseRepository::class,
        StudyPlanRepositoryInterface::class => EloquentStudyPlanRepository::class,
        LevelRepositoryInterface::class => EloquentLevelRepository::class,
        CourseEquivalenceRepositoryInterface::class => EloquentCourseEquivalenceRepository::class,
        // Requisitos entre cursos.
        PrerequisiteRepositoryInterface::class => EloquentPrerequisiteRepository::class,
        StudentRepositoryInterface::class => EloquentStudentRepository::class,
        AcademicRecordRepositoryInterface::class => EloquentAcademicRecordRepository::class,
        // Expediente: proyección de lectura sobre las tablas de arriba.
        StudentFileRepositoryInterface::class => EloquentStudentFileRepository::class,

        // StudentRequests — núcleo del módulo (ES-01 a ES-04).
        WaiverRuleRepositoryInterface::class => EloquentWaiverRuleRepository::class,
        TransferCreditPrecedentRepositoryInterface::class => EloquentTransferCreditPrecedentRepository::class,
        StudentRequestRepositoryInterface::class => EloquentStudentRequestRepository::class,
        // Los cursos de una convalidación: tabla hija.
        RequestedCourseRepositoryInterface::class => EloquentRequestedCourseRepository::class,
        // El RSREC-001: la resolución de una convalidación es un documento.
        TransferResolutionDocumentInterface::class => PdfTransferResolutionDocument::class,
        StudentRequestStatusHistoryRepositoryInterface::class => EloquentStudentRequestStatusHistoryRepository::class,
    ];

    /**
     * @var array<class-string, class-string>
     */
    private array $domainPolicies = [
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        WaiverRule::class => WaiverRulePolicy::class,
        TransferCreditPrecedent::class => TransferCreditPrecedentPolicy::class,
        StudentRequest::class => StudentRequestPolicy::class,
    ];

    public function register(): void
    {
        foreach ($this->domainBindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        $this->registerJwtTokenService();
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerSuperAdminBypass();
        $this->loadContextRoutes();
        $this->registerContextLivewireComponents();
    }

    /** Alias de Livewire para los componentes que se INCRUSTAN. */
    private function registerContextLivewireComponents(): void
    {
        Livewire::component('student-request-summary', StudentRequestSummaryComponent::class);
    }

    private function registerPolicies(): void
    {
        foreach ($this->domainPolicies as $entity => $policy) {
            Gate::policy($entity, $policy);
        }
    }

    /** Superadmin passes every authorization check unconditionally. */
    private function registerSuperAdminBypass(): void
    {
        Gate::before(function (Authenticatable $user): ?bool {
            return method_exists($user, 'hasRole') && $user->hasRole('Superadmin')
                ? true
                : null;
        });
    }

    /** Tarea 2.4: JwtTokenService no es una interfaz con un adaptador. */
    private function registerJwtTokenService(): void
    {
        $this->app->singleton(JwtTokenService::class, function ($app) {
            return new JwtTokenService(
                secret: (string) config('jwt.secret'),
                ttlMinutes: (int) config('jwt.ttl_minutes'),
                clock: $app->make(ClockInterface::class),
            );
        });
    }

    private function loadContextRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        foreach (File::glob(base_path('src/*/*/Presentation/Routes/web.php')) as $routeFile) {
            $this->loadRoutesFrom($routeFile);
        }

        // Tarea 2.4: igual patrón que los web.php de arriba.
        foreach (File::glob(base_path('src/*/*/Presentation/Routes/api.php')) as $routeFile) {
            $this->loadRoutesFrom($routeFile);
        }
    }
}
