@props([
    'wireModel',
    'hint' => '',
    'accept' => '.pdf,.jpg,.jpeg,.png',
])

{{-- Una sola zona de arrastrar-y-soltar para una lista ABIERTA. --}}
@php
    $hasError = $errors->has($wireModel) || $errors->has($wireModel.'.*');
@endphp

<div class="dropzone dropzone-multi {{ $hasError ? 'has-error' : '' }}"
    x-data="openFileDropzone('{{ $wireModel }}')"
    x-on:dragover.prevent="onDragEnter()"
    x-on:dragenter.prevent="onDragEnter()"
    x-on:dragleave.prevent="onDragLeave()"
    x-on:drop.prevent="onDrop($event)"
    :class="{ 'is-dragging': isDragging }"
    x-on:click="openPicker()">

    {{-- Puerta de entrada del selector nativo. --}}
    <input type="file" x-ref="picker" multiple accept="{{ $accept }}"
        x-on:change="onPickerChange($event)" x-on:click.stop class="dropzone-input">

    {{-- El único input que Livewire conoce. --}}
    <input type="file" x-ref="realInput" wire:model="{{ $wireModel }}" multiple accept="{{ $accept }}"
        x-on:click.stop class="dropzone-input">

    <svg class="dropzone-icon" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 15V3" />
        <path d="M7 8l5-5 5 5" />
        <path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4" />
    </svg>

    <span class="dropzone-text" x-show="fileNames.length === 0">{{ __('Upload the files your request needs, or drag and drop them here') }}</span>
    <span class="dropzone-text" x-show="fileNames.length > 0">{{ __('Documents added') }}</span>
    @if ($hint)
    <span class="dropzone-hint">{{ $hint }}</span>
    @endif

    {{-- x-on:click.stop en la lista: sin eso. --}}
    <div class="dropzone-file-list" x-on:click.stop>
        <template x-for="(name, index) in fileNames" :key="index">
            <span class="dropzone-file-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                </svg>
                <span x-text="name"></span>
                {{-- type="button" explícito: dentro de un <form>. --}}
                <button type="button" class="dropzone-file-remove"
                    x-on:click="removeFile(index)"
                    :aria-label="'{{ __('Remove') }} ' + name"
                    :title="'{{ __('Remove') }} ' + name">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </span>
        </template>
    </div>
</div>

@error($wireModel)
<span class="form-error">{{ $message }}</span>
@enderror
@foreach ($errors->get($wireModel.'.*') as $fieldMessages)
@foreach ($fieldMessages as $message)
<span class="form-error">{{ $message }}</span>
@endforeach
@endforeach
