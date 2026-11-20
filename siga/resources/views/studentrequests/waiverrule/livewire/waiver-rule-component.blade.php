<div x-data="{
    confirmDelete: { open: false, step: 'confirm', id: null },
    askDelete(id) {
        this.confirmDelete = { open: true, step: 'confirm', id };
    },
    runDelete() {
        $wire.delete(this.confirmDelete.id)
            .then(() => { this.confirmDelete.step = 'success'; })
            .catch(() => { this.confirmDelete.open = false; });
    },
    closeDeleteModal() {
        this.confirmDelete.open = false;
    },
}">
    @php
        $waiverRuleClass = \Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule::class;
    @endphp

    <x-ui.data-table
        :headers="[
            ['key' => 'courseLabel', 'label' => __('Course'), 'sortable' => false],
            ['key' => 'evaluationOrder', 'label' => __('Order'), 'sortable' => true],
            ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
            ['key' => 'isActive', 'label' => __('Active'), 'sortable' => true],
        ]"
        :mode="$tableMode"
        :rows="$rows ?? []"
        :paginator="$waiverRules ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        {{-- Cinco columnas, no seis: se fue "Parámetros". --}}
        table-cols="1.8fr 0.6fr 4fr 0.9fr 1fr"
        :can-create="Auth::user()->can('create', $waiverRuleClass)"
        :can-search="Auth::user()->can('search', $waiverRuleClass)"
        :can-export-pdf="Auth::user()->can('exportPdf', $waiverRuleClass)"
        :can-export-excel="Auth::user()->can('exportExcel', $waiverRuleClass)"
        :title="__('Waiver rules management')">

        @if ($tableMode === 'client')
        <template x-for="row in pageRows" :key="row.id">
            <div class="data-row" role="row">
                <span x-text="row.courseLabel"></span>
                <span x-text="row.evaluationOrder"></span>
                <span x-text="row.type"></span>
                <span>
                    <span class="status-badge success" x-show="row.isActiveRaw">{{ __('Active') }}</span>
                    <span class="status-badge neutral" x-show="!row.isActiveRaw">{{ __('Inactive') }}</span>
                </span>
                <div class="actions-cell">
                    <x-ui.row-actions
                        :can-view="Auth::user()->hasPermissionTo('waiver_rules.view')"
                        :can-edit="Auth::user()->hasPermissionTo('waiver_rules.edit')"
                        :can-delete="Auth::user()->hasPermissionTo('waiver_rules.delete')"
                        view-action="$wire.openDetailModal(row.id)"
                        edit-action="$wire.openEditModal(row.id)"
                        delete-id="row.id" />
                </div>
            </div>
        </template>
        <div class="empty-row" x-show="pageRows.length === 0">{{ __('No records found') }}</div>
        @else
        @forelse ($waiverRules as $waiverRule)
        <div class="data-row" role="row">
            <span>{{ $courseOptions[$waiverRule->courseId()]?->code() }} — {{ $courseOptions[$waiverRule->courseId()]?->name() }}</span>
            <span>{{ $waiverRule->evaluationOrder() }}</span>
            <span>{{ $waiverRule->type() }}</span>
            <span>
                @if ($waiverRule->isActive())
                <span class="status-badge success">{{ __('Active') }}</span>
                @else
                <span class="status-badge neutral">{{ __('Inactive') }}</span>
                @endif
            </span>
            <div class="actions-cell">
                <x-ui.row-actions
                    :can-view="Auth::user()->can('view', $waiverRule)"
                    :can-edit="Auth::user()->can('update', $waiverRule)"
                    :can-delete="Auth::user()->can('delete', $waiverRule)"
                    view-action="$wire.openDetailModal({{ $waiverRule->id() }})"
                    edit-action="$wire.openEditModal({{ $waiverRule->id() }})"
                    delete-id="{{ $waiverRule->id() }}" />
            </div>
        </div>
        @empty
        <div class="empty-row">{{ __('No records found') }}</div>
        @endforelse
        @endif
    </x-ui.data-table>

    <x-ui.modal :show="$showModal" :title="$editingId === null ? __('New waiver rule') : __('Edit waiver rule')">
        {{-- Filtro en cascada. --}}
        <div class="form-field">
            <label for="waiverRuleDegreeProgram">{{ __('Degree program') }}</label>
            <select id="waiverRuleDegreeProgram" wire:model.live="formDegreeProgramId">
                <option value="">{{ __('All degree programs') }}</option>
                @foreach ($degreeProgramOptions as $degreeProgram)
                <option value="{{ $degreeProgram->id() }}">{{ $degreeProgram->name() }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label for="waiverRuleCourse">{{ __('Course') }}</label>
            <x-ui.course-select id="waiverRuleCourse" wire-model="form.courseId" :options="$courseOptions" />
        </div>

        <div class="form-field">
            <label for="waiverRuleOrder">{{ __('Evaluation order') }}</label>
            <input type="number" min="1" id="waiverRuleOrder" wire:model="form.evaluationOrder" class="{{ $errors->has('form.evaluationOrder') ? 'has-error' : '' }}">
            @error('form.evaluationOrder') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field">
            <label for="waiverRuleType">{{ __('Rule type') }}</label>
            {{-- Los SEIS tipos salen de la Entidad. --}}
            <select id="waiverRuleType" wire:model="form.type">
                @foreach ($waiverRuleClass::TYPES as $ruleType)
                <option value="{{ $ruleType }}">{{ $ruleType }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label>
                <input type="checkbox" wire:model="form.isActive">
                {{ __('Active') }}
            </label>
        </div>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Detalle de solo lectura. --}}
    <x-ui.modal :show="$showDetailModal" :title="__('Waiver rule detail')" close-action="closeDetailModal">
        @if ($detail)
        <div class="form-field">
            <label>{{ __('Course') }}</label>
            <div>{{ $detail['courseLabel'] }}</div>
        </div>
        <div class="form-field">
            <label>{{ __('Evaluation order') }}</label>
            <div>{{ $detail['evaluationOrder'] }}</div>
        </div>
        <div class="form-field">
            <label>{{ __('Rule type') }}</label>
            <div>{{ $detail['type'] }}</div>
        </div>
        <div class="form-field">
            <label>{{ __('Status') }}</label>
            <div>
                @if ($detail['isActive'])
                <span class="status-badge success">{{ __('Active') }}</span>
                @else
                <span class="status-badge neutral">{{ __('Inactive') }}</span>
                @endif
            </div>
        </div>
        @endif

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeDetailModal">{{ __('Close') }}</button>
            @if ($detail && $detail['canUpdate'])
            <button type="button" class="btn btn-primary" wire:click="editFromDetail">{{ __('Edit') }}</button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-delete-modal :success-text="__('The waiver rule has been deleted.')" />
</div>
