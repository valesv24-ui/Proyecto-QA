@props([
    'wireModel',
    'options' => [],
    'placeholder' => null,
    'live' => false,
    'disabled' => false,
    'id' => null,
])

{{-- Reemplazo del <select> nativo para elegir curso: ver
     resources/js/course-select.ts para el motivo (el popup nativo se sale de
     pantalla con listas largas de cursos y eso no se puede arreglar con
     CSS). --}}
@php
    $normalizedOptions = collect($options)->map(fn ($course) => [
        'value' => (string) $course->id(),
        'label' => $course->code().' — '.$course->name(),
    ])->values()->all();

    $resolvedPlaceholder = $placeholder ?? __('Select a course');
    $hasError = $errors->has($wireModel);
@endphp

{{-- entangle() se pasa desde acá, NO desde init(): ver course-select.ts. --}}
<div class="course-select {{ $hasError ? 'has-error' : '' }}"
    x-data="courseSelect({
        options: @js($normalizedOptions),
        placeholder: @js($resolvedPlaceholder),
    }, $wire.entangle(@js($wireModel)){{ $live ? '.live' : '' }})"
    x-on:click.outside="close()"
    x-on:keydown.escape.window="close()"
    {{ $attributes }}>

    <button type="button"
        @if ($id) id="{{ $id }}" @endif
        x-ref="trigger"
        class="course-select-trigger {{ $hasError ? 'has-error' : '' }}"
        :class="{ 'is-open': open, 'is-placeholder': !value }"
        :aria-expanded="open"
        aria-haspopup="listbox"
        @disabled($disabled)
        @click="toggle()"
        x-text="selectedLabel()">
    </button>

    <div x-ref="menu" class="course-select-menu" role="listbox" :class="{ 'open': open }" :style="style" x-on:click.stop>
        <template x-if="options.length === 0">
            <div class="course-select-empty">{{ __('No options available') }}</div>
        </template>
        <template x-for="option in options" :key="option.value">
            <button type="button" class="course-select-option" role="option"
                :class="{ 'is-selected': currentValue() === option.value }"
                :aria-selected="currentValue() === option.value"
                x-text="option.label"
                @click="choose(option)">
            </button>
        </template>
    </div>
</div>

@error($wireModel) <span class="form-error">{{ $message }}</span> @enderror
