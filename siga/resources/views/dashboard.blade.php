<x-layouts.dashboard :title="__('Main Panel')" :subtitle="__('General overview of the academic system')">
    {{-- El panel estaba vacío (un .blank-panel sin contenido). --}}
    <livewire:student-request-summary />
</x-layouts.dashboard>
