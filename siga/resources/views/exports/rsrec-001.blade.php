{{-- RSREC-001 — Resolución de la Coordinación General de Carrera. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $resolutionNumber ? 'RSREC-001 '.$resolutionNumber : 'RSREC-001' }}</title>
    <style>
        @page { size: letter; margin: 18mm 16mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5pt; color: #000; line-height: 1.45; }
        h1 { font-size: 11pt; text-align: center; margin: 0 0 2px; text-transform: uppercase; }
        h2 { font-size: 10.5pt; text-align: center; margin: 0 0 18px; font-weight: bold; }
        .meta { margin-bottom: 14px; }
        .meta div { margin-bottom: 2px; }
        p { margin: 0 0 10px; text-align: justify; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0 14px; font-size: 9.5pt; }
        th, td { border: 1px solid #000; padding: 4px 5px; }
        th { text-align: center; font-weight: bold; }
        td.center { text-align: center; }
        .group-row td { background: #eee; font-weight: bold; }
        .reasons div { border-bottom: 1px solid #000; height: 18px; margin-bottom: 6px; }
        .signatures { margin-top: 26px; }
        .sign-line { margin-top: 22px; }
        .sign-cols { width: 100%; margin-top: 14px; }
        .sign-cols td { border: none; padding: 0 10px 26px 0; vertical-align: bottom; }
        .footnote { font-size: 8.5pt; margin-top: 10px; }
    </style>
</head>
<body>

<h1>Resolución Coordinación General de Carrera o Comisión Técnica Asesora</h1>
<h2>Solicitud de reconocimiento y equiparación de cursos y bloques de cursos</h2>

<div class="meta">
    <div>Fecha: {{ $issuedOn }}</div>
    <div>Resolución No. {{ $resolutionNumber ?: '________________________' }}</div>
</div>

<div class="meta">
    <div>Señor/a</div>
    <div>Encargado</div>
    <div>Oficina de Registro</div>
</div>

<p>Estimado/a señor/a:</p>

<p>
    Esta comisión ha efectuado el estudio de equiparación de cursos del señor (a) (ita)
    <strong>{{ $studentName }}</strong> con identificación No. <strong>{{ $nationalId }}</strong>,
    perteneciente a la carrera <strong>{{ $degreeProgram ?: 'No consta' }}</strong>.
    Documentos vistos en sesión No. <strong>{{ $sessionNumber ?: '________' }}</strong>,
    acta No. <strong>{{ $minutesNumber ?: '________' }}</strong>,
    de fecha <strong>{{ $sessionDate ?: '________________' }}</strong>,
    y de acuerdo con lo que establece el Reglamento para el Reconocimiento y Equiparación de
    Estudios Realizados en la UTN y otras Instituciones de Educación Superior dictamina:
</p>

<p><strong>Reconocer o equiparar los siguientes cursos:</strong></p>

<table>
    <thead>
        <tr>
            <th colspan="3">Universidad de procedencia</th>
            <th colspan="3">Universidad Técnica Nacional</th>
            <th colspan="2">Resolución</th>
        </tr>
        <tr>
            <th>Código</th>
            <th>Nombre del curso</th>
            <th>Créditos</th>
            <th>Código</th>
            <th>Nombre del curso</th>
            <th>Créditos</th>
            <th>Sí</th>
            <th>No</th>
        </tr>
    </thead>
    <tbody>
        {{-- Agrupado por universidad. --}}
        @foreach ($groups as $institution => $rows)
        @if (count($groups) > 1)
        <tr class="group-row"><td colspan="8">{{ $institution }}</td></tr>
        @endif
        @foreach ($rows as $row)
        <tr>
            <td>{{ $row['externalCode'] ?: '—' }}</td>
            <td>{{ $row['externalName'] }}</td>
            <td class="center">{{ $row['externalCredits'] ?? '—' }}</td>
            <td>{{ $row['utnCode'] }}</td>
            <td>{{ $row['utnName'] }}</td>
            <td class="center">{{ $row['utnCredits'] ?? '—' }}</td>
            <td class="center">{{ $row['recognized'] ? 'X' : '' }}</td>
            <td class="center">{{ $row['recognized'] ? '' : 'X' }}</td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>

@if ($denialReasons !== [])
<p>Las razones por las que no se reconocen / equiparan los cursos mencionados son las siguientes:</p>
<div class="reasons">
    @foreach ($denialReasons as $reason)
    <div>{{ $reason }}</div>
    @endforeach
</div>
@endif

<div class="signatures">
    <p>Atentamente,</p>
    <p><strong>COORDINACIÓN GENERAL DE CARRERA</strong><br><strong>COMISIÓN TÉCNICA ASESORA</strong></p>

    <div class="sign-line">Coordinador ______________________________________ (nombre y firma)</div>

    <table class="sign-cols">
        <tr>
            <td><strong>Directores de Carrera</strong> (nombre y firma)</td>
            <td><strong>Profesores</strong> (nombre y firma)*</td>
        </tr>
        <tr>
            <td>_________________________________</td>
            <td>_________________________________</td>
        </tr>
        <tr>
            <td>_________________________________</td>
            <td>_________________________________</td>
        </tr>
        <tr>
            <td>_________________________________</td>
            <td>_________________________________</td>
        </tr>
    </table>

    <div class="footnote">* Solo para los casos en los que opera la CTA.</div>
</div>

</body>
</html>
