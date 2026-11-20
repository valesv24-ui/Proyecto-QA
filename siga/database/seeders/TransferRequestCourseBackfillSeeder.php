<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Le da su fila en `student_request_courses` a toda convalidación. */
class TransferRequestCourseBackfillSeeder extends Seeder
{
    use WithoutModelEvents;

    /** ¿Ya pasó por el escritorio de Registro? Recién verificada. */
    private function hasBeenVerified(string $status): bool
    {
        return $status !== 'Pendiente de revisión';
    }

    public function run(): void
    {
        $now = now();
        $created = 0;

        DB::table('student_requests')
            ->where('type', 'Convalidación')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('student_request_courses')
                    ->whereColumn('student_request_courses.student_request_id', 'student_requests.id');
            })
            ->orderBy('id')
            ->chunkById(200, function ($requests) use ($now, &$created): void {
                $rows = [];

                foreach ($requests as $request) {
                    $rows[] = [
                        'student_request_id' => $request->id,
                        'course_id' => $request->course_id,
                        'external_institution' => $request->source_institution ?? 'No consta',
                        'external_course_name' => $request->external_course ?? 'No consta',
                        'external_course_code' => $this->hasBeenVerified($request->status)
                            ? 'EXT-'.str_pad((string) ($request->id % 1000), 3, '0', STR_PAD_LEFT)
                            : null,
                        'external_credits' => $this->hasBeenVerified($request->status) ? 4 : null,
                        // El resultado por curso se deduce de lo que DOCENCIA.
                        'outcome' => match ($request->teaching_decision) {
                            'Aprobada' => 'Reconocido',
                            'Denegada' => 'No reconocido',
                            default => 'Pendiente',
                        },
                        'denial_reason' => $request->teaching_decision === 'Denegada'
                            ? 'Los contenidos del curso externo no cubren los objetivos del curso de la UTN.'
                            : null,
                        'created_at' => $request->created_at ?? $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('student_request_courses')->insert($rows);
                    $created += count($rows);
                }
            });

        if ($created > 0) {
            $this->command->info($created.' convalidaciones recibieron su fila de curso.');
        }
    }
}
