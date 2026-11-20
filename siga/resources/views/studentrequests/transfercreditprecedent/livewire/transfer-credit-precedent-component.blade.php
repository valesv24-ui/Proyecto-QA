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
        $transferCreditPrecedentClass = \Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent::class;
    @endphp

    <x-ui.data-table
        :headers="[
            ['key' => 'institution', 'label' => __('Institution'), 'sortable' => true],
            ['key' => 'externalCourse', 'label' => __('External course'), 'sortable' => true],
            ['key' => 'courseLabel', 'label' => __('Equivalent course'), 'sortable' => false],
            ['key' => 'outcome', 'label' => __('Outcome'), 'sortable' => true],
            ['key' => 'resolutionNumber', 'label' => __('Resolution number'), 'sortable' => false],
        ]"
        :mode="$tableMode"
        :rows="$rows ?? []"
        :paginator="$precedents ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="1.8fr 1.8fr 2fr 1fr 1.4fr 1fr"
        :can-create="Auth::user()->can('create', $transferCreditPrecedentClass)"
        :can-search="Auth::user()->can('search', $transferCreditPrecedentClass)"
        :can-export-pdf="Auth::user()->can('exportPdf', $transferCreditPrecedentClass)"
        :can-export-excel="Auth::user()->can('exportExcel', $transferCreditPrecedentClass)"
        :title="__('Transfer credit precedents management')">

        @if ($tableMode === 'client')
        <template x-for="row in pageRows" :key="row.id">
            <div class="data-row" role="row">
                <span x-text="row.institution"></span>
                <span x-text="row.externalCourse"></span>
                <span x-text="row.courseLabel"></span>
                <span>
                    <span class="status-badge info" x-show="row.outcome === 'Aprobada'" x-text="row.outcome"></span>
                    <span class="status-badge danger" x-show="row.outcome !== 'Aprobada'" x-text="row.outcome"></span>
                </span>
                <span x-text="row.resolutionNumber"></span>
                <div class="actions-cell">
                    <x-ui.row-actions
                        :can-view="Auth::user()->hasPermissionTo('transfer_credit_precedents.view')"
                        :can-edit="Auth::user()->hasPermissionTo('transfer_credit_precedents.edit')"
                        :can-delete="Auth::user()->hasPermissionTo('transfer_credit_precedents.delete')"
                        view-action="$wire.openDetailModal(row.id)"
                        edit-action="$wire.openEditModal(row.id)"
                        delete-id="row.id" />
                </div>
            </div>
        </template>
        <div class="empty-row" x-show="pageRows.length === 0">{{ __('No records found') }}</div>
        @else
        @forelse ($precedents as $precedent)
        <div class="data-row" role="row">
            <span>{{ $precedent->institution() }}</span>
            <span>{{ $precedent->externalCourse() }}</span>
            <span>{{ $courseOptions[$precedent->courseId()]?->code() }} — {{ $courseOptions[$precedent->courseId()]?->name() }}</span>
            <span>
                @if ($precedent->isApproved())
                <span class="status-badge info">{{ $precedent->outcome() }}</span>
                @else
                <span class="status-badge danger">{{ $precedent->outcome() }}</span>
                @endif
            </span>
            <span>{{ $precedent->resolutionNumber() }}</span>
            <div class="actions-cell">
                <x-ui.row-actions
                    :can-view="Auth::user()->can('view', $precedent)"
                    :can-edit="Auth::user()->can('update', $precedent)"
                    :can-delete="Auth::user()->can('delete', $precedent)"
                    view-action="$wire.openDetailModal({{ $precedent->id() }})"
                    edit-action="$wire.openEditModal({{ $precedent->id() }})"
                    delete-id="{{ $precedent->id() }}" />
            </div>
        </div>
        @empty
        <div class="empty-row">{{ __('No records found') }}</div>
        @endforelse
        @endif
    </x-ui.data-table>

    <x-ui.modal :show="$showModal" :title="$editingId === null ? __('New transfer credit precedent') : __('Edit transfer credit precedent')">
        <div class="form-field">
            <label for="precedentInstitution">{{ __('Institution') }}</label>
            <input type="text" id="precedentInstitution" wire:model="form.institution" class="{{ $errors->has('form.institution') ? 'has-error' : '' }}">
            @error('form.institution') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field">
            <label for="precedentExternalCourse">{{ __('External course') }}</label>
            <input type="text" id="precedentExternalCourse" wire:model="form.externalCourse" class="{{ $errors->has('form.externalCourse') ? 'has-error' : '' }}">
            @error('form.externalCourse') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        {{-- Filtro en cascada. --}}
        <div class="form-field">
            <label for="precedentDegreeProgram">{{ __('Degree program') }}</label>
            <select id="precedentDegreeProgram" wire:model.live="formDegreeProgramId">
                <option value="">{{ __('All degree programs') }}</option>
                @foreach ($degreeProgramOptions as $degreeProgram)
                <option value="{{ $degreeProgram->id() }}">{{ $degreeProgram->name() }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label for="precedentCourse">{{ __('Equivalent course') }}</label>
            <x-ui.course-select id="precedentCourse" wire-model="form.courseId" :options="$courseOptions" />
        </div>

        <div class="form-field">
            <label for="precedentOutcome">{{ __('Outcome') }}</label>
            <select id="precedentOutcome" wire:model="form.outcome">
                <option value="Aprobada">{{ __('Approved') }}</option>
                <option value="Denegada">{{ __('Denied') }}</option>
            </select>
        </div>

        <div class="form-field">
            <label for="precedentResolutionNumber">{{ __('Resolution number') }}</label>
            <input type="text" id="precedentResolutionNumber" wire:model="form.resolutionNumber" class="{{ $errors->has('form.resolutionNumber') ? 'has-error' : '' }}">
            @error('form.resolutionNumber') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        {{-- Los adjuntos cuelgan del precedente por su id. --}}
        @if ($editingId !== null)
        <div class="form-field">
            <label>{{ __('Attachments') }}</label>
            @forelse ($attachments as $attachment)
            <div>
                <button type="button" class="btn btn-secondary" style="margin:4px 4px 0 0;" wire:click="downloadAttachment({{ $attachment['id'] }})">
                    {{ $attachment['documentType'] }} — {{ $attachment['originalName'] }} ({{ $attachment['sizeLabel'] }})
                </button>
                @if ($attachment['previewUrl'])
                <a href="{{ $attachment['previewUrl'] }}" target="_blank" rel="noopener"
                   class="btn btn-secondary" style="margin:4px 4px 0 0;">{{ __('View') }}</a>
                @endif
            </div>
            @empty
            <div>{{ __('No attachments.') }}</div>
            @endforelse
        </div>

        <div class="form-field">
            <label for="precedentAttachmentType">{{ __('Document type') }}</label>
            <input type="text" id="precedentAttachmentType" wire:model="newAttachmentType"
                placeholder="{{ __('E.g. Technical criterion, Resolution') }}"
                class="{{ $errors->has('newAttachmentType') ? 'has-error' : '' }}">
            @error('newAttachmentType') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field">
            <label for="precedentAttachmentFile">{{ __('Attach document (PDF, image, Word or Excel, max :size)', ['size' => $uploadLimit]) }}</label>
            <input type="file" id="precedentAttachmentFile" wire:model="newAttachment"
                class="{{ $errors->has('newAttachment') ? 'has-error' : '' }}">
            @error('newAttachment') <span class="form-error">{{ $message }}</span> @enderror
            <div wire:loading wire:target="newAttachment" style="margin-top:6px;">{{ __('Uploading...') }}</div>
            <button type="button" class="btn btn-secondary" style="margin-top:8px;" wire:click="uploadAttachment">{{ __('Attach') }}</button>
        </div>
        @endif

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Detalle de solo lectura. --}}
    <x-ui.modal :show="$showDetailModal" :title="__('Transfer credit precedent detail')" close-action="closeDetailModal">
        @if ($detail)
        <div class="form-field">
            <label>{{ __('Institution') }}</label>
            <div>{{ $detail['institution'] }}</div>
        </div>
        <div class="form-field">
            <label>{{ __('External course') }}</label>
            <div>{{ $detail['externalCourse'] }}</div>
        </div>
        <div class="form-field">
            <label>{{ __('Equivalent course') }}</label>
            <div>{{ $detail['courseLabel'] }}</div>
        </div>
        <div class="form-field">
            <label>{{ __('Outcome') }}</label>
            <div>
                <span class="status-badge {{ $detail['outcome'] === 'Aprobada' ? 'info' : 'danger' }}">{{ $detail['outcome'] }}</span>
            </div>
        </div>
        <div class="form-field">
            <label>{{ __('Resolution number') }}</label>
            <div>{{ $detail['resolutionNumber'] }}</div>
        </div>
        <div class="form-field">
            <label>{{ __('Attachments') }}</label>
            @forelse ($detail['attachments'] as $attachment)
            <div>
                <button type="button" class="btn btn-secondary" style="margin:4px 4px 0 0;" wire:click="downloadAttachment({{ $attachment['id'] }})">
                    {{ $attachment['documentType'] }} — {{ $attachment['originalName'] }} ({{ $attachment['sizeLabel'] }})
                </button>
                @if ($attachment['previewUrl'])
                <a href="{{ $attachment['previewUrl'] }}" target="_blank" rel="noopener"
                   class="btn btn-secondary" style="margin:4px 4px 0 0;">{{ __('View') }}</a>
                @endif
            </div>
            @empty
            <div>{{ __('No attachments.') }}</div>
            @endforelse
        </div>
        @endif

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeDetailModal">{{ __('Close') }}</button>
            @if ($detail && $detail['canUpdate'])
            <button type="button" class="btn btn-primary" wire:click="editFromDetail">{{ __('Edit') }}</button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-delete-modal :success-text="__('The transfer credit precedent has been deleted.')" />
</div>
