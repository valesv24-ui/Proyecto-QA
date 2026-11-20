<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Las mallas oficiales de la UTN, extraídas de los 8 PDF de plan de estudio.
 *
 * Reemplaza los requisitos que antes se escribían a mano: el dataset
 * (database/data/curricula-utn.json) sale de los documentos con un extractor,
 * y este seeder solo lo carga. Un requisito mal transcrito acá se convierte en
 * un levantamiento que el sistema aprueba o rechaza por el motivo equivocado.
 *
 * Idempotente: todo entra por clave natural (código de curso, nombre de plan,
 * número de nivel), así que correrlo dos veces no duplica nada.
 */
class CurriculaFromPdfSeeder extends Seeder
{
    private const DATASET = 'database/data/curricula-utn.json';

    /** Nombre completo de cada carrera, para degree_programs. */
    private const PROGRAM_NAMES = [
        'ISW' => 'Ingeniería del Software',
        'ITI' => 'Ingeniería en Tecnologías de Información',
        'FP' => 'Enseñanza de la Especialidad Técnica',
    ];

    /** Año de implementación de cada plan, del encabezado del PDF. */
    private const PLAN_YEARS = [
        'B06' => 2025,
        'L01TM' => 2018,
        'L07B' => 2024,
        'L06T' => 2022,
        'B03' => 2016,
    ];

    public function run(): void
    {
        $rows = $this->dataset();

        $programIds = $this->programs($rows);
        $planIds = $this->plans($rows, $programIds);
        $courseIds = $this->courses($rows, $programIds);
        $this->levels($rows, $planIds, $courseIds);
        $this->prerequisites($rows, $planIds, $courseIds);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dataset(): array
    {
        $path = base_path(self::DATASET);

        if (! is_file($path)) {
            throw new RuntimeException("Falta el dataset de mallas: {$path}");
        }

        /** @var array<int, array<string, mixed>> $rows */
        $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function programs(array $rows): array
    {
        $ids = [];

        foreach (array_unique(array_column($rows, 'program')) as $code) {
            $name = self::PROGRAM_NAMES[$code] ?? $code;
            $ids[$code] = (int) DB::table('degree_programs')->where('name', $name)->value('id')
                ?: DB::table('degree_programs')->insertGetId([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return $ids;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, int>  $programIds
     * @return array<string, int>
     */
    private function plans(array $rows, array $programIds): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $code = (string) $row['plan_code'];

            if (isset($ids[$code])) {
                continue;
            }

            $programId = $programIds[$row['program']];
            $name = (string) $row['plan_name'];

            $existing = DB::table('study_plans')
                ->where('degree_program_id', $programId)
                ->where('name', $name)
                ->value('id');

            $ids[$code] = (int) $existing ?: DB::table('study_plans')->insertGetId([
                'degree_program_id' => $programId,
                'name' => $name,
                'implementation_year' => self::PLAN_YEARS[$code] ?? 2025,
                'classification' => 'Vigente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, int>  $programIds
     * @return array<string, int>
     */
    private function courses(array $rows, array $programIds): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $code = (string) $row['code'];

            if (isset($ids[$code])) {
                continue;
            }

            // Los transversales (AC-100, AD-100, FH-*, IDTEC*, TCU) no
            // pertenecen a una carrera: los administra Docencia y aparecen en
            // varias mallas. degree_program_id null es lo que ya usa el
            // catálogo para distinguirlos.
            $programId = $this->isServiceCourse($code) ? null : $programIds[$row['program']];

            $existing = DB::table('courses')->where('code', $code)->value('id');

            if ($existing !== null) {
                $ids[$code] = (int) $existing;

                continue;
            }

            $ids[$code] = DB::table('courses')->insertGetId([
                'degree_program_id' => $programId,
                'code' => $code,
                'name' => (string) $row['name'],
                'is_service_course' => $this->isServiceCourse($code),
                'is_bottleneck' => false,
                'requires_lab' => false,
                'lab_type' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }

    private function isServiceCourse(string $code): bool
    {
        return (bool) preg_match('/^(AC-|AD-|FH-|IDTEC|TCU)/', $code);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, int>  $planIds
     * @param  array<string, int>  $courseIds
     */
    private function levels(array $rows, array $planIds, array $courseIds): void
    {
        $levelIds = [];

        foreach ($rows as $row) {
            // Los electivos son un catálogo de opciones: existen como curso
            // pero no ocupan un nivel de la malla.
            if ($row['level'] === null) {
                continue;
            }

            $planId = $planIds[$row['plan_code']];
            $number = (int) $row['level'];
            $key = $planId.':'.$number;

            if (! isset($levelIds[$key])) {
                $existing = DB::table('levels')
                    ->where('study_plan_id', $planId)
                    ->where('number', $number)
                    ->value('id');

                $levelIds[$key] = (int) $existing ?: DB::table('levels')->insertGetId([
                    'study_plan_id' => $planId,
                    'number' => $number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('course_level')->updateOrInsert(
                ['level_id' => $levelIds[$key], 'course_id' => $courseIds[$row['code']]],
                ['credits' => (int) $row['credits'], 'created_at' => now()],
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, int>  $planIds
     * @param  array<string, int>  $courseIds
     */
    private function prerequisites(array $rows, array $planIds, array $courseIds): void
    {
        $levelOf = [];

        foreach ($rows as $row) {
            $levelOf[$row['plan_code'].':'.$row['code']] = $row['level'];
        }

        foreach ($rows as $row) {
            $planId = $planIds[$row['plan_code']];

            foreach ((array) $row['requires'] as $requiredCode) {
                // Un curso que se pide a sí mismo es un error de origen: el
                // PDF de la Licenciatura en ITI lista ITI-1324 entre sus
                // propios requisitos. Se salta acá para que un dato malo no
                // aborte todo el sembrado contra chk_prerequisites_distinct.
                if ($requiredCode === $row['code']) {
                    continue;
                }

                if (! isset($courseIds[$requiredCode])) {
                    continue;
                }

                DB::table('prerequisites')->updateOrInsert(
                    [
                        'study_plan_id' => $planId,
                        'required_course_id' => $courseIds[$requiredCode],
                        'target_course_id' => $courseIds[$row['code']],
                    ],
                    [
                        'kind' => $this->kindFor(
                            $levelOf[$row['plan_code'].':'.$requiredCode] ?? null,
                            $row['level'],
                        ),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }

    /**
     * Requisito o correquisito, según la distancia de niveles.
     *
     * Los PDF no lo distinguen —ISW e ITI titulan la columna "Requisitos" y FP
     * "Requisitos y Co-requisitos", pero ninguno marca cuál es cuál—, así que
     * el equipo fijó la regla: si el requisito es del cuatrimestre inmediato
     * anterior, es correquisito; si hay dos niveles o más de distancia, es
     * requisito. Es una convención del proyecto, no un dato del documento.
     */
    private function kindFor(?int $requiredLevel, ?int $targetLevel): string
    {
        if ($requiredLevel === null || $targetLevel === null) {
            return 'Requisito';
        }

        return $targetLevel - $requiredLevel === 1 ? 'Correquisito' : 'Requisito';
    }
}
