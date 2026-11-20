{{-- ES-03: los dos correos del trámite. --}}
<x-mail::message>
{{ __('Dear student:') }}

@if ($submitted)
{{ __('The UTN confirms that your :type request for the course :course was received.', [
    'type' => $typeLabel,
    'course' => $courseLabel,
]) }}

@if ($justification)
{{ __('Justification you selected') }}: {{ $justification }}
@endif

@if ($estimatedResolutionDate)
{{ __('Estimated resolution date') }}: {{ $estimatedResolutionDate }}
@endif

{{ __('Your request will be reviewed by Registro Universitario and then by Docencia. You will be informed once it is completed.') }}
@else
{{ __('The UTN informs you that your :type request for the course :course is :status.', [
    'type' => $typeLabel,
    'course' => $courseLabel,
    'status' => $publicStatus,
]) }}

@if ($statement)
{{-- La constancia explica la decisión. --}}
**{{ __('Resolution statement') }}**

{{ $statement }}
@endif
@endif

{{ __('Regards') }},<br>
{{ __('National Technical University') }}
</x-mail::message>
