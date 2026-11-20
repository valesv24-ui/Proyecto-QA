{{-- Ventana de confirmación para el resultado de cualquier acción: crear. --}}
<div x-data="{
        open: false,
        variant: 'success',
        text: '',
        show(detail) {
            this.variant = detail?.variant ?? 'success';
            this.text = detail?.text ?? '';
            this.open = true;
        },
        get isSuccess() { return this.variant === 'success'; },
        get isDanger() { return this.variant === 'danger'; },
        get title() {
            if (this.isDanger) return @js(__('Action could not be completed'));
            if (this.variant === 'warning') return @js(__('Warning'));
            return @js(__('Done successfully'));
        },
     }"
    x-on:toast.window="show($event.detail)"
    x-on:keydown.escape.window="open = false">

    <div class="del-overlay" :class="{ 'open': open }">
        <div class="del-card">
            {{-- Éxito. --}}
            <template x-if="isSuccess">
                <div class="del-icon-success">
                    <svg width="38" height="38" viewBox="0 0 52 52">
                        <path d="M14 27l8 8 16-16" fill="none" stroke="#a5dc86" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </div>
            </template>

            {{-- Advertencia y error comparten el ícono de atención. --}}
            <template x-if="!isSuccess">
                <div class="del-icon-warn" :style="isDanger ? 'color:#d92d20; border-color:#d92d20;' : ''">!</div>
            </template>

            <p class="del-title" x-text="title"></p>
            <p class="del-text" x-text="text"></p>

            <button type="button" class="del-btn-ok" @click="open = false">{{ __('Accept') }}</button>
        </div>
    </div>
</div>
