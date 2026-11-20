<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Presentation\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

/** ES-02, ahora con VARIOS cursos por solicitud. */
class TransferRequestForm extends Form
{
    /**
     * Filas del formulario: cada una es un curso a convalidar.
     *
     * @var array<int, array{courseId: string, externalCourse: string, sourceInstitution: string}>
     */
    public array $courses = [
        ['courseId' => '', 'externalCourse' => '', 'sourceInstitution' => ''],
    ];

    /**
     * @var array<int, TemporaryUploadedFile>
     */
    public array $attachments = [];

    public function addCourse(): void
    {
        $this->courses[] = ['courseId' => '', 'externalCourse' => '', 'sourceInstitution' => ''];
    }

    /** Quitar una fila reindexa el arreglo a propósito. */
    public function removeCourse(int $index): void
    {
        unset($this->courses[$index]);

        $this->courses = array_values($this->courses);

        // Nunca cero filas.
        if ($this->courses === []) {
            $this->addCourse();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courses' => ['required', 'array', 'min:1', 'max:8'],
            // El tope de 8 no es arbitrario.
            'courses.*.courseId' => ['required', 'integer', 'exists:courses,id'],
            'courses.*.externalCourse' => ['required', 'string', 'max:150'],
            'courses.*.sourceInstitution' => ['required', 'string', 'max:150'],
            'attachments' => ['required', 'array', 'min:1'],
            // 10240 KB (10 MB) para que coincida con el límite.
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'courses.*.courseId.required' => __('Select the UTN course you want recognized.'),
            'courses.*.externalCourse.required' => __('Enter the name of the external course.'),
            'courses.*.sourceInstitution.required' => __('Enter the university the course comes from.'),
        ];
    }

    /** ¿Hay dos filas pidiendo el MISMO curso de la UTN?. */
    public function hasDuplicateCourses(): bool
    {
        $ids = array_filter(array_column($this->courses, 'courseId'));

        return count($ids) !== count(array_unique($ids));
    }
}
