<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

// Mismo motivo que en ⚡profile.blade.php y ⚡security.blade.php: sin
// #[Layout], Livewire cae en su vista por defecto ('layouts.app'), que no
// existe en este proyecto. No hay una prueba que cubra esta página en
// particular, pero el defecto es el mismo — se corrige junto con las otras
// dos en vez de dejarla rota a medias.
new #[Title('Appearance settings')] #[Layout('components.layouts.dashboard')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-pages::settings.layout>
</section>
