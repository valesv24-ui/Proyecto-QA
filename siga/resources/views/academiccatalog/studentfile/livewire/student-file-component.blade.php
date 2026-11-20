<div>
    @if (! $this->isStudent())
    <div class="card">
        <div class="card-head"><span class="card-title">{{ __('No academic record available') }}</span></div>
        <div style="padding:20px;">{{ __('Your account has no linked student record.') }}</div>
    </div>
    @elseif ($file)
    <div class="card">
        <div class="card-head"><span class="card-title">{{ __('Academic record') }}</span></div>
        <div style="padding:20px;">
            <x-ui.academic-record :file="$file" />
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-head"><span class="card-title">{{ __('Academic record') }}</span></div>
        <div style="padding:20px;">
            <div class="empty-row">{{ __('This student has no academic record yet.') }}</div>
        </div>
    </div>
    @endif
</div>
