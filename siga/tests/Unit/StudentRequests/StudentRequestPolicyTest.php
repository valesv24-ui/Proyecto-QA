<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use DateTimeImmutable;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Presentation\Policies\StudentRequestPolicy;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Tests\TestCase;

/** ES-04 / tarea 7.7. */
final class StudentRequestPolicyTest extends TestCase
{
    private StudentRequestPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new StudentRequestPolicy;
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    private function userWithPermissions(array $permissionNames, ?Student $student = null): User
    {
        $permissions = collect($permissionNames)->map(
            fn (string $name) => new Permission(['module' => 'student_requests', 'action' => $name, 'name' => $name]),
        );

        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->setRelation('permissions', $permissions);
        $user->setRelation('roles', collect());
        $user->setRelation('student', $student);

        return $user;
    }

    public function test_view_any_requires_the_inbox_permission(): void
    {
        $withPermission = $this->userWithPermissions(['student_requests.view']);
        $withoutPermission = $this->userWithPermissions(['student_requests.edit']);

        self::assertTrue($this->policy->viewAny($withPermission));
        self::assertFalse($this->policy->viewAny($withoutPermission));
    }

    public function test_view_allows_docencia_with_permission_regardless_of_ownership(): void
    {
        $staff = $this->userWithPermissions(['student_requests.view']);
        $studentRequest = StudentRequest::forWaiver(
            studentId: 99,
            courseId: 1,
            prerequisiteCourseId: 2,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            termsAcceptedAt: new DateTimeImmutable('2026-08-10 09:00:00'),
        );

        self::assertTrue($this->policy->view($staff, $studentRequest));
    }

    public function test_view_allows_the_owning_student_without_any_permission(): void
    {
        $student = new Student(['national_id' => '1-1111-1111', 'first_name' => 'Ana', 'last_name' => 'Ramirez', 'is_active' => true]);
        $student->id = 99;

        $owner = $this->userWithPermissions([], $student);
        $studentRequest = StudentRequest::forWaiver(
            studentId: 99,
            courseId: 1,
            prerequisiteCourseId: 2,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            termsAcceptedAt: new DateTimeImmutable('2026-08-10 09:00:00'),
        );

        self::assertTrue($this->policy->view($owner, $studentRequest));
    }

    public function test_view_rejects_a_student_who_does_not_own_the_request(): void
    {
        $student = new Student(['national_id' => '1-1111-1111', 'first_name' => 'Ana', 'last_name' => 'Ramirez', 'is_active' => true]);
        $student->id = 5;

        $someoneElse = $this->userWithPermissions([], $student);
        $studentRequest = StudentRequest::forWaiver(
            studentId: 99,
            courseId: 1,
            prerequisiteCourseId: 2,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            termsAcceptedAt: new DateTimeImmutable('2026-08-10 09:00:00'),
        );

        self::assertFalse($this->policy->view($someoneElse, $studentRequest));
    }

    public function test_view_rejects_a_staff_account_with_no_permission_and_no_student_record(): void
    {
        $staffWithoutPermission = $this->userWithPermissions([]);
        $studentRequest = StudentRequest::forWaiver(
            studentId: 99,
            courseId: 1,
            prerequisiteCourseId: 2,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            termsAcceptedAt: new DateTimeImmutable('2026-08-10 09:00:00'),
        );

        self::assertFalse($this->policy->view($staffWithoutPermission, $studentRequest));
    }

    public function test_create_requires_a_linked_student_record_not_a_permission(): void
    {
        $student = new Student(['national_id' => '1-1111-1111', 'first_name' => 'Ana', 'last_name' => 'Ramirez', 'is_active' => true]);
        $studentAccount = $this->userWithPermissions([], $student);
        $staffAccount = $this->userWithPermissions(['student_requests.view', 'student_requests.edit']);

        self::assertTrue($this->policy->create($studentAccount));
        self::assertFalse($this->policy->create($staffAccount));
    }

    public function test_update_requires_the_edit_permission(): void
    {
        $reviewer = $this->userWithPermissions(['student_requests.edit']);
        $readOnlyStaff = $this->userWithPermissions(['student_requests.view']);
        $studentRequest = StudentRequest::forWaiver(
            studentId: 1,
            courseId: 1,
            prerequisiteCourseId: 2,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            termsAcceptedAt: new DateTimeImmutable('2026-08-10 09:00:00'),
        );

        self::assertTrue($this->policy->update($reviewer, $studentRequest));
        self::assertFalse($this->policy->update($readOnlyStaff, $studentRequest));
    }

    public function test_verify_is_a_separate_permission_from_edit(): void
    {
        // Registro verifica; Docencia resuelve.
        $registro = $this->userWithPermissions(['student_requests.verify']);
        $docencia = $this->userWithPermissions(['student_requests.edit']);
        $studentRequest = StudentRequest::forWaiver(
            studentId: 1,
            courseId: 1,
            prerequisiteCourseId: 2,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            termsAcceptedAt: new DateTimeImmutable('2026-08-10 09:00:00'),
        );

        self::assertTrue($this->policy->verify($registro, $studentRequest));
        self::assertFalse($this->policy->verify($docencia, $studentRequest));
        self::assertFalse($this->policy->update($registro, $studentRequest));
    }

    public function test_export_permissions_are_independent_of_each_other(): void
    {
        $pdfOnly = $this->userWithPermissions(['student_requests.export_pdf']);

        self::assertTrue($this->policy->exportPdf($pdfOnly));
        self::assertFalse($this->policy->exportExcel($pdfOnly));
    }

    public function test_a_role_granted_permission_is_honored_without_a_direct_grant(): void
    {
        $permission = new Permission(['module' => 'student_requests', 'action' => 'view', 'name' => 'student_requests.view']);
        $role = new Role(['name' => 'Docencia']);
        $role->setRelation('permissions', collect([$permission]));

        $user = new User(['name' => 'Docencia User', 'email' => 'docencia@example.com']);
        $user->setRelation('permissions', collect());
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('student', null);

        self::assertTrue($this->policy->viewAny($user));
    }
}
