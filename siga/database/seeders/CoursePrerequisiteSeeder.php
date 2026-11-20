<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Requisitos entre cursos. */
class CoursePrerequisiteSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Plan de estudios => curso destino => cursos que exige.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private const PREREQUISITES = [
        // Plan B06 — Ingeniería del Software 2025.
        'Ingeniería del Software 2025' => [
            'ISW-223' => ['ISW-123'],
            'ISW-321' => ['ISW-221'],
            'ISW-323' => ['ISW-223'],
            'ISW-421' => ['ISW-321'],
            'ISW-422' => ['ISW-321'],
            'ISW-423' => ['ISW-123'],
            'ISW-521' => ['ISW-421'],
            'ISW-522' => ['ISW-422'],
            'ISW-523' => ['ISW-423'],
            'ISW-524' => ['ISW-322'],
            'ISW-621' => ['ISW-521'],
            'ISW-622' => ['ISW-222'],
            'ISW-623' => ['ISW-522'],
            'ISW-624' => ['ISW-523'],
            'ISW-721' => ['ISW-621'],
            'ISW-723' => ['ISW-624'],
            'ISW-821' => ['ISW-721'],
            'ISW-822' => ['ISW-722'],
            'ISW-921' => ['ISW-822'],
            'ISW-1021' => ['ISW-922'],
            'ISW-1022' => ['ISW-721', 'ISW-722', 'ISW-723', 'ISW-821', 'ISW-822', 'ISW-823', 'ISW-921', 'ISW-922', 'ISW-923'],
        ],
        // Plan L01TM — Ingeniería del Software 2018-TM.
        'Ingeniería del Software 2018-TM' => [
            'ISW-1311' => ['ISW-1211'],
            'ISW-1313' => ['ISW-1213'],
            'ISW-1314' => ['ISW-1211', 'ISW-1212', 'ISW-1213', 'ISW-1214'],
            'ISW-1412' => ['ISW-1312'],
            'ISW-1415' => ['ISW-1412', 'ISW-1413'],
        ],
        // Plan L07B — Ingeniería en Tecnologías de Información 2024.
        'Ingeniería en Tecnologías de Información 2024' => [
            'ITI-221' => ['ITI-121'],
            'ITI-223' => ['ITI-123'],
            'ITI-224' => ['ITI-124'],
            'ITI-321' => ['ITI-221'],
            'ITI-322' => ['ITI-221'],
            'ITI-323' => ['ITI-222'],
            'ITI-324' => ['ITI-224'],
            'ITI-421' => ['ITI-321', 'ITI-322'],
            'ITI-422' => ['ITI-323'],
            'ITI-423' => ['ITI-323'],
            'ITI-424' => ['ITI-321'],
            'ITI-521' => ['ITI-423'],
            'ITI-522' => ['ITI-422'],
            'ITI-523' => ['ITI-421'],
            'ITI-524' => ['ITI-421', 'ITI-523'],
            'ITI-621' => ['ITI-523'],
            'ITI-622' => ['ITI-521'],
            'ITI-623' => ['ITI-523'],
            'ITI-624' => ['ITI-124'],
            'ITI-625' => ['ITI-521', 'ITI-522', 'ITI-622'],
            'ITI-721' => ['ITI-623'],
            'ITI-722' => ['ITI-324'],
            'ITI-723' => ['ITI-624'],
            'ITI-724' => ['ITI-622'],
            'ITI-821' => ['ITI-223'],
            'ITI-822' => ['ITI-424', 'ITI-623'],
            'ITI-823' => ['ITI-721', 'ITI-821', 'ITI-822'],
            'ITI-824' => ['ITI-724'],
            'ITI-825' => ['ITI-624'],
            'ITI-921' => ['ITI-325'],
            'ITI-922' => ['ITI-824'],
            'ITI-923' => ['ITI-824'],
        ],
        // Plan L06T — Licenciatura en Ingeniería en Tecnologías de Información.
        'Licenciatura en Ingeniería en Tecnologías de Información 2022' => [
            'ITI-1221' => ['ITI-1123'],
            'ITI-1222' => ['ITI-1123'],
            'ITI-1223' => ['ITI-1221'],
            'ITI-1321' => ['ITI-1221'],
        ],
    ];

    public function run(): void
    {
        $courseIds = DB::table('courses')->pluck('id', 'code');
        $planIds = DB::table('study_plans')->pluck('id', 'name');

        if ($courseIds->isEmpty() || $planIds->isEmpty()) {
            $this->command->warn(
                'CoursePrerequisiteSeeder necesita las currículas cargadas. '
                .'Corré AcademicCurriculaSeeder antes que este seeder.'
            );

            return;
        }

        $rows = [];
        $missing = [];
        $selfReferencing = [];

        foreach (self::PREREQUISITES as $planName => $targets) {
            $planId = $planIds[$planName] ?? null;

            if ($planId === null) {
                $missing[] = "plan {$planName}";

                continue;
            }

            foreach ($targets as $targetCode => $requiredCodes) {
                $targetId = $courseIds[$targetCode] ?? null;

                if ($targetId === null) {
                    $missing[] = $targetCode;

                    continue;
                }

                foreach ($requiredCodes as $requiredCode) {
                    $requiredId = $courseIds[$requiredCode] ?? null;

                    if ($requiredId === null) {
                        $missing[] = $requiredCode;

                        continue;
                    }

                    // Un curso no puede exigirse a sí mismo.
                    if ($requiredId === $targetId) {
                        $selfReferencing[] = $targetCode;

                        continue;
                    }

                    $rows[] = [
                        'study_plan_id' => $planId,
                        'required_course_id' => $requiredId,
                        'target_course_id' => $targetId,
                        'created_at' => now(),
                    ];
                }
            }
        }

        // upsert y no insert: la clave única.
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('prerequisites')->upsert(
                $chunk,
                ['study_plan_id', 'required_course_id', 'target_course_id'],
                [],
            );
        }

        $this->command->info(count($rows).' requisitos entre cursos cargados.');

        if ($selfReferencing !== []) {
            $this->command->warn(
                'Se descartaron requisitos de un curso hacia sí mismo: '
                .implode(', ', array_unique($selfReferencing))
            );
        }

        if ($missing !== []) {
            // Se avisa en vez de fallar.
            $this->command->warn(
                'No se encontraron estos códigos: '.implode(', ', array_unique($missing))
            );
        }
    }
}
