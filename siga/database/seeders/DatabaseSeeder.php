<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Src\StudentRequests\StudentRequest\Application\UseCases\EnsureEstimatedResolutionDateUseCase;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Seed the application's database. */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $superadminUser = $this->demoUser('prueba@gmail.com', 'prueba ISW-521');

        $superadminUser->roles()->sync(
            Role::query()->where('name', 'Superadmin')->pluck('id')
        );

        // Segundo usuario demo con rol Admin (no Superadmin).
        $adminRole = Role::query()->where('name', 'Admin')->firstOrFail();
        $adminRole->permissions()->sync(Permission::query()->pluck('id'));

        $adminUser = $this->demoUser('admin@gmail.com', 'admin prueba ISW-521');

        // Sin esto el usuario entraba SIN ningún rol.
        $adminUser->roles()->sync([$adminRole->id]);

        // Las currículas reales (2 carreras UTN + catálogo UCR) van.
        $this->call([
            // Los cuatrimestres van primero.
            AcademicTermSeeder::class,
            AcademicCurriculaSeeder::class,
            // Las mallas oficiales de los 8 PDF: completa cursos y niveles,
            // agrega la carrera FP y siembra los requisitos con su tipo.
            // Va después de AcademicCurriculaSeeder y es idempotente: lo que
            // ya existe se reconoce por código, no se duplica.
            CurriculaFromPdfSeeder::class,
            StudentRequestsDemoSeeder::class,
            // Cuenta de estudiante para verificaciones centrales.
            CentralVerificationStudentSeeder::class,
            // Expediente real de Jose Ortiz (ISW B06), del estudio de
            // graduación: 24 materias ganadas y 5 en curso.
            JoseOrtizRecordSeeder::class,
        ]);

        // Las convalidaciones de demo se insertan con DB.
        $this->call(TransferRequestCourseBackfillSeeder::class);

        $this->assignEstimatedResolutionDates();
        $this->creditApprovedTransfers();
    }

    /** Usuario demo por correo: resembrar no debe abortar la cadena. */
    private function demoUser(string $email, string $name): User
    {
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            return $existing;
        }

        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('12345678'),
        ]);
    }

    /** Los cursos reconocidos de las convalidaciones aprobadas de demo. */
    private function creditApprovedTransfers(): void
    {
        // La salida va directo a la consola y no vía $this->command: esa
        // propiedad solo existe cuando se siembra desde db:seed.
        Artisan::call('convalidaciones:acreditar', [], new ConsoleOutput);
    }

    /** ES-03: las solicitudes de demo se insertan con DB::table. */
    private function assignEstimatedResolutionDates(): void
    {
        $requests = app(StudentRequestRepositoryInterface::class);
        $ensureDate = app(EnsureEstimatedResolutionDateUseCase::class);

        foreach ($requests->idsWithoutEstimatedResolutionDate() as $id) {
            $ensureDate->handle($id);
        }
    }
}
