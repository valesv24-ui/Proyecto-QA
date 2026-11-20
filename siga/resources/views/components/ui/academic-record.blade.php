@props(['file'])

{{-- Contenido del expediente del estudiante: datos personales, resumen y
     cursos. Extraído del detalle de solicitud (ES-04) para poder mostrar lo
     mismo en la pestaña "Expediente académico" del propio estudiante, sin
     duplicar el marcado. El plegado de ese uso original (record-toggle,
     $showStudentFile) se queda en quien llama a este componente — acá solo
     vive el contenido. --}}
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-top:12px;">
    <div>
        <label>{{ __('Full name') }}</label>
        <div>{{ $file['fullName'] }}</div>
    </div>
    <div>
        {{-- En la UTN la cédula ES el carné. --}}
        <label>{{ __('National ID / Student ID') }}</label>
        <div>{{ $file['nationalId'] }}</div>
    </div>
    <div>
        <label>{{ __('Institutional Email') }}</label>
        {{-- La cuenta de acceso es opcional en el esquema. --}}
        <div>{{ $file['email'] ?? '—' }}</div>
    </div>
    <div>
        <label>{{ __('Enrollment') }}</label>
        <div>
            <span class="status-badge {{ $file['isActive'] ? 'success' : 'danger' }}">
                {{ $file['isActive'] ? __('Active') : __('Inactive') }}
            </span>
        </div>
    </div>
    <div>
        <label>{{ __('Current degree program') }}</label>
        <div>{{ $file['degreeProgram'] ?? __('Not enrolled') }}</div>
        @if ($file['studyPlan'])
        <div style="color:var(--textMuted); font-size:12.5px;">{{ $file['studyPlan'] }}</div>
        @endif
    </div>
    <div>
        {{-- Dos cuatrimestres distintos y a propósito juntos. --}}
        <label>{{ __('Term in the program') }}</label>
        <div>
            {{ $file['currentLevel'] ? __('Term :n', ['n' => $file['currentLevel']]) : __('Not recorded') }}
            @if ($file['currentTerm'])
            <span style="color:var(--textMuted); font-size:12.5px;">· {{ __('Calendar term') }}: {{ $file['currentTerm'] }}</span>
            @endif
        </div>
    </div>
</div>

<div class="summary-breakdown" style="margin-top:12px;">
    <div><span class="status-badge success">{{ __('Passed') }}</span> <strong>{{ $file['approved'] }}</strong></div>
    <div><span class="status-badge danger">{{ __('Failed') }}</span> <strong>{{ $file['failed'] }}</strong></div>
    <div><span class="status-badge info">{{ __('Accredited') }}</span> <strong>{{ $file['accredited'] }}</strong></div>
    {{-- Cursando ahora: no suma créditos ni habilita requisitos. --}}
    <div><span class="status-badge warning">{{ __('In progress') }}</span> <strong>{{ $file['inProgress'] }}</strong></div>
    @if ($file['average'] !== null)
    <div><span class="status-badge neutral">{{ __('Average') }}</span> <strong>{{ $file['average'] }}</strong></div>
    @endif
    {{-- Créditos ganados sobre el total del plan. --}}
    <div>
        <span class="status-badge neutral">{{ __('Credits') }}</span>
        <strong>{{ $file['earnedCredits'] }}{{ $file['planCredits'] ? ' / '.$file['planCredits'] : '' }}</strong>
    </div>
</div>

{{-- Tabla propia y no <x-ui.data-table>: ese componente trae búsqueda. --}}
<div class="table-scroll" style="margin-top:12px;">
    <table class="record-table">
        <thead>
            <tr>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Course') }}</th>
                <th>{{ __('Program term') }}</th>
                <th>{{ __('Taken in') }}</th>
                <th>{{ __('Status') }}</th>
                <th style="text-align:right;">{{ __('Grade') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($file['courses'] as $record)
            <tr>
                <td><strong>{{ $record['code'] }}</strong></td>
                <td>{{ $record['name'] }}</td>
                {{-- Un guion y no un vacío. --}}
                <td>{{ $record['planLevel'] ?? '—' }}</td>
                <td>{{ $record['term'] ?? '—' }}</td>
                <td>{{ $record['status'] }}</td>
                <td style="text-align:right;">{{ $record['grade'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="color:var(--textMuted);">{{ __('This student has no academic record yet.') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
