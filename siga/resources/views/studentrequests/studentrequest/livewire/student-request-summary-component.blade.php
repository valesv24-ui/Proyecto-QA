{{-- Cards de solicitudes del panel principal. --}}
<div wire:poll.30s.visible="$refresh">

@if ($reviewsInbox)
    {{-- ===== Docencia y Comisión: carga de trabajo ===== --}}
    <div class="summary-cards">
        <div class="summary-card">
            <span class="summary-label">{{ __('Open requests') }}</span>
            <span class="summary-value">{{ $summary['totals']['open'] }}</span>
            <span class="summary-hint">{{ __('Pending or in review') }}</span>
        </div>

        {{-- Vencidas y por vencer llevan color propio incluso en cero. --}}
        <div class="summary-card {{ $summary['totals']['overdue'] > 0 ? 'is-danger' : '' }}">
            <span class="summary-label">{{ __('Overdue') }}</span>
            <span class="summary-value">{{ $summary['totals']['overdue'] }}</span>
            <span class="summary-hint">{{ __('Past their estimated date') }}</span>
        </div>

        <div class="summary-card {{ $summary['totals']['dueSoon'] > 0 ? 'is-warning' : '' }}">
            <span class="summary-label">{{ __('Due soon') }}</span>
            <span class="summary-value">{{ $summary['totals']['dueSoon'] }}</span>
            <span class="summary-hint">{{ __('Within 2 business days') }}</span>
        </div>

        <div class="summary-card">
            <span class="summary-label">{{ __('Resolved') }}</span>
            <span class="summary-value">{{ $summary['totals']['resolved'] }}</span>
            <span class="summary-hint">{{ __('Approved or denied') }}</span>
        </div>
    </div>

    {{-- Desglose por tipo. --}}
    <div class="card-grid">
        @foreach ([$waiverType => __('Requirement waivers'), $transferType => __('Course transfers')] as $type => $title)
        @php($bucket = $summary['byType'][$type])
        <div class="card">
            <div class="card-head">
                <span class="card-title">{{ $title }}</span>
                {{-- Directo a la sección que resume esta card. --}}
                <a href="{{ route('studentrequests.studentrequest.index', ['tab' => $type === $transferType ? 'inbox-transfer' : 'inbox']) }}" class="btn btn-secondary">{{ __('Open inbox') }}</a>
            </div>
            <div class="summary-breakdown">
                <div><span class="status-badge neutral">{{ __('Pending') }}</span> <strong>{{ $bucket['pending'] }}</strong></div>
                <div><span class="status-badge info">{{ __('In review') }}</span> <strong>{{ $bucket['inReview'] }}</strong></div>
                <div><span class="status-badge success">{{ __('Approved') }}</span> <strong>{{ $bucket['approved'] }}</strong></div>
                <div><span class="status-badge danger">{{ __('Denied') }}</span> <strong>{{ $bucket['denied'] }}</strong></div>
                @if ($bucket['overdue'] > 0)
                <div><span class="status-badge danger-solid">{{ __('Overdue') }}</span> <strong>{{ $bucket['overdue'] }}</strong></div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Lista corta de lo urgente. --}}
    @if (count($pastDue) > 0)
    <div class="card">
        <div class="card-head">
            <span class="card-title">{{ __('Needs attention today') }}</span>
            <a href="{{ route('studentrequests.studentrequest.index') }}" class="btn btn-secondary">{{ __('See all') }}</a>
        </div>
        @foreach ($pastDue as $row)
        <div class="summary-row">
            <div>
                <strong>{{ $row['courseLabel'] }}</strong>
                <div style="color:var(--textMuted); font-size:12.5px;">{{ $row['type'] }}</div>
            </div>
            <div>
                <span class="status-badge {{ $row['statusVariant'] }}">{{ $row['status'] }}</span>
                @if ($row['deadlineLabel'])
                <span style="margin-left:8px; color:var(--textMuted); font-size:12.5px;">{{ $row['deadlineLabel'] }}</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

@elseif ($showsOwnRequests)
    {{-- ===== Estudiante: sus propias solicitudes ===== --}}
    <div class="summary-cards">
        <div class="summary-card">
            <span class="summary-label">{{ __('My open requests') }}</span>
            <span class="summary-value">{{ $mine['open'] }}</span>
            <span class="summary-hint">{{ __('Pending or in review') }}</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">{{ __('Approved') }}</span>
            <span class="summary-value">{{ $mine['approved'] }}</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">{{ __('Denied') }}</span>
            <span class="summary-value">{{ $mine['denied'] }}</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">{{ __('Next publication date') }}</span>
            {{-- Fecha REAL del Calendario Institucional que publica Registro. --}}
            <span class="summary-value" style="font-size:20px;">{{ $mine['nextPublicationDate'] ?? '—' }}</span>
            <span class="summary-hint">{{ __('Of your open transfer requests') }}</span>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <span class="card-title">{{ __('Student Requests') }}</span>
            <a href="{{ route('studentrequests.studentrequest.index') }}" class="btn btn-primary">{{ __('View my requests') }}</a>
        </div>
        {{-- Sin texto a propósito; se deja el espacio en blanco para que la
             tarjeta conserve el mismo alto que cuando sí lo tenía. --}}
        <div style="color:var(--textMuted);">
            &nbsp;
        </div>
    </div>
@endif

</div>
