<div>
    @php
        $studentRequestClass = \Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest::class;
    @endphp

    {{-- La barra de pestañas solo aparece si hay MÁS. --}}
    @php
        $availableTabs = ($this->isStudent() ? 3 : 0) + ($this->canReviewInbox() ? 2 : 0);
    @endphp

    @if ($availableTabs > 1)
    <div class="card" style="margin-bottom:16px;">
        <div class="request-tabs">
            @if ($this->isStudent())
            <button type="button" class="request-tab {{ $activeTab === 'waiver' ? 'active' : '' }}" wire:click="setTab('waiver')">{{ __('New waiver request') }}</button>
            <button type="button" class="request-tab {{ $activeTab === 'transfer' ? 'active' : '' }}" wire:click="setTab('transfer')">{{ __('New transfer request') }}</button>
            <button type="button" class="request-tab {{ $activeTab === 'mine' ? 'active' : '' }}" wire:click="setTab('mine')">{{ __('My requests') }}</button>
            @endif
            @if ($this->canReviewInbox())
            {{-- Dos secciones y no un filtro dentro de una sola. --}}
            <button type="button" class="request-tab {{ $activeTab === 'inbox' ? 'active' : '' }}" wire:click="setTab('inbox')">{{ __('Waiver inbox') }}</button>
            <button type="button" class="request-tab {{ $activeTab === 'inbox-transfer' ? 'active' : '' }}" wire:click="setTab('inbox-transfer')">{{ __('Transfer inbox') }}</button>
            {{-- El archivo, al final y no entre las dos bandejas. --}}
            <button type="button" class="request-tab {{ $activeTab === 'archive' ? 'active' : '' }}" wire:click="setTab('archive')">{{ __('History') }}</button>
            @endif
        </div>
    </div>
    @endif

    @if (! $this->isStudent() && ! $this->canReviewInbox())
    <div class="card">
        <div class="card-head"><span class="card-title">{{ __('No requests available') }}</span></div>
        <div style="padding:20px;">{{ __('Your account has neither a linked student record nor permission to review the inbox.') }}</div>
    </div>
    @endif

    {{-- ===== ES-01: Nueva solicitud de levantamiento ===== --}}
    @if ($activeTab === 'waiver' && $this->isStudent())
    <div class="card">
        <div class="card-head">
            <span class="card-title">{{ __('New waiver request') }}</span>
            <span style="color:var(--textMuted); font-size:12.5px;">SLR-002 V.3</span>
        </div>
        <div class="request-form-body" style="padding:20px;">
            @if ($waiverOutcomeBanner)
            <div class="status-badge {{ $waiverOutcomeBanner['variant'] === 'success' ? 'success' : ($waiverOutcomeBanner['variant'] === 'danger' ? 'danger' : ($waiverOutcomeBanner['variant'] === 'warning' ? 'warning' : 'info')) }}" style="margin-bottom:16px; display:inline-block;">
                {{ $waiverOutcomeBanner['text'] }}
            </div>
            @endif

            <div class="form-field">
                <label for="waiverReqCourse">{{ __('Course to enroll') }}</label>
                {{-- .live: el segundo selector depende de este. --}}
                <x-ui.course-select id="waiverReqCourse" wire-model="waiverForm.courseId" :live="true"
                    :options="$studentWaiverCourseOptions" />
                @if (count($studentWaiverCourseOptions) === 0)
                <span class="form-hint">{{ __('You have no courses with requirements left to waive.') }}</span>
                @else
                <span class="form-hint">{{ __('Only courses with at least one requirement you have not earned.') }}</span>
                @endif
            </div>

            <div class="form-field">
                <label for="waiverReqPrereq">{{ __('Unmet requirement') }}</label>
                {{-- Solo los requisitos REALES del curso elegido, según la malla. Este
                     select depende de waiverForm.courseId: wire:key fuerza a Alpine a
                     reinicializarse (y por lo tanto releer las opciones) cada vez que
                     el curso elegido cambia — ver el comentario en course-select.ts. --}}
                <x-ui.course-select id="waiverReqPrereq" wire-model="waiverForm.prerequisiteCourseId"
                    :options="$studentRequirementOptions" :disabled="! $waiverForm->courseId"
                    wire:key="waiver-req-prereq-{{ $waiverForm->courseId }}" />
                @if ($waiverForm->courseId && count($studentRequirementOptions) === 0)
                <span class="form-hint">{{ __('That course has no pending requirements in your record.') }}</span>
                @else
                <span class="form-hint">{{ __('Only the requirements of the selected course.') }}</span>
                @endif
            </div>

            {{-- JUSTIFICACIÓN. Radio y no <select>. --}}
            <div class="form-field">
                <label>{{ __('Justification (select only one option)') }}</label>
                <div class="justification-options">
                    @foreach ($studentJustifications as $index => $justification)
                    <label class="justification-option {{ $waiverForm->justification === $justification ? 'is-selected' : '' }}">
                        <input type="radio" wire:model.live="waiverForm.justification" value="{{ $justification }}">
                        <span>
                            <strong>{{ chr(97 + $index) }}.</strong>
                            {{ $justification }}
                        </span>
                    </label>
                    @endforeach
                </div>
                @error('waiverForm.justification') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            {{-- x-data en el contenedor y no en el input. --}}
            <div class="form-field" x-data>
                <label for="waiverReqFile">{{ __('Supporting document (PDF or image, max :size)', ['size' => $uploadLimit]) }}</label>
                <input type="file" id="waiverReqFile" x-ref="waiverFile" wire:model="waiverForm.attachment" class="{{ $errors->has('waiverForm.attachment') ? 'has-error' : '' }}">
                @error('waiverForm.attachment') <span class="form-error">{{ $message }}</span> @enderror

                @if ($waiverForm->attachment)
                <div class="dropzone-file-list" style="justify-content:flex-start;">
                    <span class="dropzone-file-item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                        </svg>
                        <span>{{ $waiverForm->attachment->getClientOriginalName() }}</span>
                        <button type="button" class="dropzone-file-remove"
                            x-on:click="$refs.waiverFile.value = ''"
                            wire:click="removeWaiverAttachment"
                            aria-label="{{ __('Remove') }} {{ $waiverForm->attachment->getClientOriginalName() }}"
                            title="{{ __('Remove') }}">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </span>
                </div>
                @endif
            </div>

            {{-- NOTAS IMPORTANTES de la boleta, convertidas en aceptación explícita. --}}
            <div class="terms-box">
                <div class="terms-title">{{ __('Important notes') }}</div>
                <ol class="terms-list">
                    <li>{{ __('You must enrol the course for which you request the waiver in the same term in which this request is approved. Administrative Directive DA-VDOC-01-2020.') }}</li>
                    <li>{{ __('If you fail the course for which the waiver was granted, you may not request a waiver for that course again. Administrative Directive DA-VDOC-01-2020.') }}</li>
                </ol>
                <label class="terms-accept">
                    <input type="checkbox" wire:model="waiverForm.acceptsTerms">
                    <span>{{ __('I have read and accept the important notes above.') }}</span>
                </label>
                @error('waiverForm.acceptsTerms') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <button type="button" class="btn btn-primary" wire:click="submitWaiver" wire:loading.attr="disabled">{{ __('Submit request') }}</button>
        </div>
    </div>
    @endif

    {{-- ===== ES-02: Nueva solicitud de convalidación ===== --}}
    @if ($activeTab === 'transfer' && $this->isStudent())
    <div class="card">
        <div class="card-head"><span class="card-title">{{ __('New transfer request') }}</span></div>
        <div class="request-form-body" style="padding:20px;">
            @if ($transferOutcomeBanner)
            <div class="status-badge info" style="margin-bottom:16px; display:inline-block;">
                {{ $transferOutcomeBanner['text'] }}
            </div>
            @endif

            {{-- Una solicitud, VARIOS cursos. --}}
            <div class="form-field">
                <label>{{ __('Courses to be recognized') }}</label>
                <div style="color:var(--textMuted); font-size:12.5px; margin-top:-4px;">
                    {{ __('You can request one course or several in the same resolution (up to 8).') }}
                </div>
                @error('transferForm.courses') <span class="form-error">{{ $message }}</span> @enderror

                <div class="course-rows">
                    <div class="course-row course-row-head">
                        <span>{{ __('UTN course') }}</span>
                        <span>{{ __('External course name') }}</span>
                        <span>{{ __('University of origin') }}</span>
                        <span></span>
                    </div>

                    @foreach ($transferForm->courses as $index => $row)
                    {{-- wire:key con el índice para que Livewire no reutilice. --}}
                    <div class="course-row" wire:key="transfer-course-{{ $index }}">
                        <div>
                            <x-ui.course-select wire-model="transferForm.courses.{{ $index }}.courseId"
                                :options="$studentCourseOptions" />
                        </div>

                        <div>
                            <input type="text" wire:model="transferForm.courses.{{ $index }}.externalCourse"
                                placeholder="{{ __('E.g. Programación I') }}"
                                class="{{ $errors->has('transferForm.courses.'.$index.'.externalCourse') ? 'has-error' : '' }}">
                            @error('transferForm.courses.'.$index.'.externalCourse') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            {{-- Una universidad POR FILA. --}}
                            <input type="text" list="transferReqInstitutionSuggestions"
                                wire:model="transferForm.courses.{{ $index }}.sourceInstitution"
                                x-on:input.debounce.400ms="$wire.searchSourceInstitutions($event.target.value)"
                                autocomplete="off"
                                placeholder="{{ __('E.g. Universidad de Costa Rica') }}"
                                class="{{ $errors->has('transferForm.courses.'.$index.'.sourceInstitution') ? 'has-error' : '' }}">
                            @error('transferForm.courses.'.$index.'.sourceInstitution') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            {{-- La fila única no se puede quitar. --}}
                            @if (count($transferForm->courses) > 1)
                            <button type="button" class="btn btn-secondary" title="{{ __('Remove') }}"
                                wire:click="removeTransferCourse({{ $index }})">&times;</button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <datalist id="transferReqInstitutionSuggestions">
                    @foreach ($sourceInstitutionSuggestions as $suggestion)
                    <option value="{{ $suggestion }}"></option>
                    @endforeach
                </datalist>

                @if (count($transferForm->courses) < 8)
                <button type="button" class="btn btn-secondary" style="align-self:flex-start; margin-top:8px;"
                    wire:click="addTransferCourse">+ {{ __('Add course') }}</button>
                @else
                {{-- Ocho es el número de filas del RSREC-001. --}}
                <div style="color:var(--textMuted); font-size:12.5px;">{{ __('The official resolution form holds up to 8 courses.') }}</div>
                @endif
            </div>

            <div class="form-field">
                <label>{{ __('Required documents:') }}</label>
                {{-- Lista abierta: ya no son tres documentos con nombre propio. --}}
                <x-ui.multi-file-dropzone
                    wire-model="transferForm.attachments"
                    {{-- El límite que se muestra es el REAL. --}}
                    :hint="__('PDF, JPG or PNG · Maximum :size each', ['size' => $uploadLimit])" />
                @if ($uploadLimitedByPhp)
                <div style="color:var(--textMuted); font-size:12.5px;">
                    {{ __('This server limits uploads to :size. Ask your administrator to raise upload_max_filesize.', ['size' => $uploadLimit]) }}
                </div>
                @endif
            </div>

            <button type="button" class="btn btn-primary" wire:click="submitTransfer" wire:loading.attr="disabled">{{ __('Submit request') }}</button>
        </div>
    </div>
    @endif

    {{-- ===== ES-03: Mis solicitudes ===== --}}
    @if ($activeTab === 'mine' && $this->isStudent())
    {{-- ES-03 pide que el sistema "actualice la vista de seguimiento". --}}
    <div wire:poll.15s.visible="$refresh">
        <x-ui.data-table
            :headers="[
                ['key' => 'type', 'label' => __('Type'), 'sortable' => false],
                ['key' => 'courseLabel', 'label' => __('Course'), 'sortable' => false],
                ['key' => 'status', 'label' => __('Status'), 'sortable' => false],
                ['key' => 'sentAt', 'label' => __('Sent date'), 'sortable' => false],
            ]"
            mode="client"
            :rows="$myRequests"
            :searchable="['type', 'courseLabel', 'status']"
            :table-version="$myRequestsVersion"
            {{-- Cuatro columnas más acciones: se fue "Resultado". --}}
            table-cols="1fr 2.4fr 1.4fr 1.2fr 1fr"
            :can-create="false"
            :can-search="true"
            :title="__('My requests')">

            <template x-for="row in pageRows" :key="row.id">
                <div class="data-row" role="row">
                    <span x-text="row.type"></span>
                    <span x-text="row.courseLabel"></span>
                    <span><span class="status-badge" :class="row.statusVariant" x-text="row.status"></span></span>
                    <span x-text="row.sentAt ?? '—'"></span>
                    {{-- El mismo modal de detalle sirve para el estudiante: StudentRequestPolicy. --}}
                    <div class="actions-cell">
                        <button type="button" class="btn btn-secondary" x-on:click="$wire.openRequestDetail(row.id, '')">{{ __('View') }}</button>
                    </div>
                </div>
            </template>
            <div class="empty-row" x-show="pageRows.length === 0">{{ __('No records found') }}</div>
        </x-ui.data-table>
    </div>
    @endif

    {{-- ===== ES-04: Bandeja de Docencia ===== --}}
    @if ($this->isInboxTab() && $this->canReviewInbox())
    {{-- La bandeja usa el mismo <x-ui.data-table> que Roles/Permisos/Reglas en lugar. --}}
    {{-- Tarea 6.5: una solicitud nueva debe aparecer sin recargar. --}}
    <div @if (! $this->isArchiveTab()) wire:poll.15s.visible="$refresh" @endif>

    {{-- La columna Tipo aparece SOLO en el Historial. --}}
    @php($showsTypeColumn = $this->isArchiveTab())

    <x-ui.data-table
        :headers="array_values(array_filter([
            ['key' => 'created_at', 'label' => __('Received'), 'sortable' => true],
            $showsTypeColumn ? ['key' => 'type', 'label' => __('Type'), 'sortable' => true] : null,
            {{-- Estudiante y Curso son etiquetas armadas. --}}
            ['key' => 'studentName', 'label' => __('Student'), 'sortable' => true],
            ['key' => 'courseLabel', 'label' => __('Course'), 'sortable' => true],
            ['key' => 'status', 'label' => __('Status'), 'sortable' => true],
        ]))"
        mode="server"
        :paginator="$inboxPaginator"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        :table-cols="$showsTypeColumn ? '0.9fr 1fr 1.5fr 2fr 0.8fr 0.8fr' : '0.9fr 1.5fr 2fr 0.8fr 0.8fr'"
        :can-create="false"
        :can-search="Auth::user()->can('search', $studentRequestClass)"
        :can-export-pdf="Auth::user()->can('exportPdf', $studentRequestClass)"
        :can-export-excel="Auth::user()->can('exportExcel', $studentRequestClass)"
        :title="$this->isArchiveTab() ? __('Resolved requests history') : ($activeTab === 'inbox-transfer' ? __('Transfer inbox') : __('Waiver inbox'))">

        @forelse ($inboxPaginator ?? [] as $row)
        <div class="data-row" role="row">
            <span>{{ $row['createdAt'] ?? '—' }}</span>
            @if ($showsTypeColumn)
            <span>{{ $row['type'] }}</span>
            @endif
            <span>{{ $row['studentName'] }}</span>
            <span>{{ $row['courseLabel'] }}</span>
            <span><span class="status-badge {{ $row['statusVariant'] }}">{{ $row['status'] }}</span></span>
            {{-- Menú de Acciones por fila. --}}
            {{-- El menú se renderiza con position:fixed (ver resources/js/row-actions-menu.js). --}}
            <div class="actions-cell" x-data="rowActionsMenu()"
                x-on:click.outside="close()"
                x-on:keydown.escape.window="close()"
                {{-- .capture además de .window porque los eventos de scroll. --}}
                x-on:scroll.window.capture="close()"
                x-on:resize.window="close()">
                <button type="button" class="btn btn-secondary" x-ref="trigger" @click="toggle()">
                    <span>{{ __('Actions') }}</span>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="row-actions-menu" x-ref="menu" :class="{ 'open': open }" :style="style">
                    {{-- Se pasa '' explícito y no se omite el argumento. --}}
                    {{-- El menú de Acciones de la fila solo abre. --}}
                    <button type="button" class="download-item" @click="close(); $wire.openRequestDetail({{ $row['id'] }}, '')">
                        <span>{{ __('View detail and documents') }}</span>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-row">{{ __('No records found') }}</div>
        @endforelse
    </x-ui.data-table>
    </div>
    @endif

    {{-- ===== Detalle + cambio de estado ===== --}}
    <x-ui.modal :show="$showDetailModal" :title="__('Request detail')" size="wide">
        @if ($requestDetail)
        {{-- Dos columnas cuando hay ancho. --}}
        <div class="detail-grid">
        <div class="form-field">
            <label>{{ __('Student') }}</label>
            <div>{{ $requestDetail['studentName'] }}</div>
        </div>
        <div class="form-field">
            <label>{{ __('Type') }}</label>
            <div>{{ $requestDetail['type'] }}</div>
        </div>
        <div class="form-field">
            <label>{{ __('Course') }}</label>
            <div>{{ $requestDetail['courseLabel'] }}</div>
        </div>
        @if ($requestDetail['type'] === $studentRequestClass::TYPE_WAIVER)
        <div class="form-field">
            <label>{{ __('Unmet requirement') }}</label>
            <div>{{ $requestDetail['prerequisiteLabel'] }}</div>
        </div>
        {{-- La justificación es lo que Docencia necesita leer primero. --}}
        <div class="form-field">
            <label>{{ __('Justification (SLR-002)') }}</label>
            <div>{{ $requestDetail['justification'] ?? '—' }}</div>
        </div>
        @if ($requestDetail['termsAcceptedAt'])
        <div class="form-field">
            <label>{{ __('Important notes accepted on') }}</label>
            <div>{{ $requestDetail['termsAcceptedAt'] }}</div>
        </div>
        @endif
        @else
        <div class="form-field">
            <label>{{ __('Source institution / external course') }}</label>
            <div>{{ $requestDetail['sourceInstitution'] }} — {{ $requestDetail['externalCourse'] }}</div>
        </div>
        {{-- El aviso "precedente encontrado" es información de evaluación. --}}
        @if ($requestDetail['hasPrecedent'] && $requestDetail['canReviewInbox'])
        <div class="detail-span status-badge success" style="display:inline-block; margin-bottom:12px;">{{ __('Approved precedent found — simplified evaluation possible') }}</div>
        @endif

        {{-- El precedente completo, aquí mismo. --}}
        @if ($requestDetail['precedent'] && ($requestDetail['canReviewInbox'] || $requestDetail['isResolved']))
        <div class="form-field detail-span">
            <label>{{ __('Backing precedent') }}</label>
            <div>{{ $requestDetail['precedent']['institution'] }} — {{ $requestDetail['precedent']['externalCourse'] }}</div>
            <div>{{ __('Resolution number') }}: <strong>{{ $requestDetail['precedent']['resolutionNumber'] }}</strong></div>

            @forelse ($requestDetail['precedent']['documents'] as $document)
            <div>
                @if ($document['previewUrl'])
                {{-- Enlace de verdad con target="_blank", no wire:click. --}}
                <a href="{{ $document['previewUrl'] }}" target="_blank" rel="noopener"
                   class="btn btn-secondary" style="margin:4px 4px 0 0;">
                    {{ __('View') }}: {{ $document['documentType'] }} — {{ $document['originalName'] }} ({{ $document['sizeLabel'] }})
                </a>
                @else
                {{-- Word o Excel: ningún navegador los muestra. --}}
                <button type="button" class="btn btn-secondary" style="margin:4px 4px 0 0;"
                        wire:click="downloadPrecedentAttachment({{ $document['id'] }})">
                    {{ $document['documentType'] }} — {{ $document['originalName'] }} ({{ $document['sizeLabel'] }})
                </button>
                @endif
            </div>
            @empty
            <div style="color:var(--textMuted);">{{ __('The precedent has no attached resolution document.') }}</div>
            @endforelse
        </div>
        @endif
        @endif
        <div class="form-field">
            <label>{{ __('Engine result') }}</label>
            <div>{{ $requestDetail['engineResult'] ?? __('N/A (transfer requests are always reviewed by the Commission)') }}</div>
        </div>
        {{-- El badge de estado interno. --}}
        @if ($requestDetail['canReviewInbox'] || $requestDetail['deadlineLabel'])
        <div class="form-field">
            <label>{{ __('Current status') }}</label>
            <div>
                @if ($requestDetail['canReviewInbox'])
                <span class="status-badge {{ $requestDetail['statusVariant'] }}">{{ $requestDetail['status'] }}</span>
                @endif
                @if ($requestDetail['deadlineLabel'])
                <span style="margin-left:8px; color:var(--textMuted); font-size:12.5px;">{{ $requestDetail['deadlineLabel'] }}</span>
                @endif
            </div>
        </div>
        @endif
        <div class="form-field">
            <label>{{ __('Estimated resolution date') }}</label>
            <div>{{ $requestDetail['estimatedResolutionDate'] ?? __('Not assigned yet') }}</div>
        </div>

        {{-- ===== Cursos de la convalidación (RSREC-001) ===== --}}
        @if (count($requestDetail['requestedCourses']) > 0)
        <div class="form-field detail-span">
            <label>{{ __('Courses to be recognized') }}</label>
            <div class="table-scroll">
                <table class="record-table">
                    <thead>
                        <tr>
                            <th>{{ __('UTN course') }}</th>
                            <th>{{ __('External course name') }}</th>
                            <th>{{ __('University of origin') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Credits') }}</th>
                            {{-- La nota de ORIGEN. --}}
                            <th>{{ __('Grade') }}</th>
                            <th>{{ __('Resolution') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requestDetail['requestedCourses'] as $row)
                        <tr wire:key="requested-course-{{ $row['id'] }}">
                            <td>{{ $row['courseLabel'] }}</td>
                            <td>{{ $row['externalName'] }}</td>
                            <td>{{ $row['institution'] }}</td>
                            @if ($requestDetail['canCompleteExternalData'])
                            <td><input type="text" style="width:90px;" wire:model="courseEdits.{{ $row['id'] }}.code"></td>
                            <td><input type="text" style="width:60px;" wire:model="courseEdits.{{ $row['id'] }}.credits"></td>
                            {{-- inputmode="decimal" y no type="number". --}}
                            <td><input type="text" inputmode="decimal" style="width:60px;" wire:model="courseEdits.{{ $row['id'] }}.grade"></td>
                            @else
                            <td>{{ $row['externalCode'] ?? '—' }}</td>
                            <td>{{ $row['externalCredits'] ?? '—' }}</td>
                            <td>{{ $row['externalGrade'] ?? '—' }}</td>
                            @endif
                            <td>
                                @if ($row['resolved'])
                                <span class="status-badge {{ $row['recognized'] ? 'success' : 'danger' }}">{{ $row['outcome'] }}</span>
                                @else
                                <span class="status-badge neutral">{{ $row['outcome'] }}</span>
                                @endif
                            </td>
                        </tr>
                        @if ($requestDetail['canCompleteExternalData'] || $requestDetail['canDecideCourses'])
                        {{-- La razón vive en su propia fila. --}}
                        <tr wire:key="requested-course-actions-{{ $row['id'] }}">
                            <td colspan="7" style="background:var(--bodyBg);">
                                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                    {{-- Transcribir es de Registro; marcar la casilla es de Docencia. --}}
                                    @if ($requestDetail['canCompleteExternalData'])
                                    <button type="button" class="btn btn-secondary"
                                        wire:click="saveRequestedCourseData({{ $row['id'] }})">{{ __('Save external data') }}</button>
                                    @endif
                                    @if ($requestDetail['canDecideCourses'])
                                    <input type="text" style="flex:1; min-width:220px;"
                                        placeholder="{{ __('Reason, required to reject this course') }}"
                                        wire:model="courseEdits.{{ $row['id'] }}.reason">
                                    <button type="button" class="btn btn-primary"
                                        wire:click="resolveRequestedCourse({{ $row['id'] }}, 'Reconocido')">{{ __('Recognize') }}</button>
                                    <button type="button" class="btn btn-orange"
                                        wire:click="resolveRequestedCourse({{ $row['id'] }}, 'No reconocido')">{{ __('Do not recognize') }}</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- DOCENCIA: resuelve y devuelve a Registro. --}}
        @if ($requestDetail['canDecideCourses'])
        <div class="form-field detail-span" style="border:1px solid var(--border); border-radius:10px; padding:12px;">
            <label>{{ __('Send to Registro for publication') }}</label>
            @if ($requestDetail['type'] === $studentRequestClass::TYPE_TRANSFER)
            {{-- En una convalidación la decisión NO se elige acá. --}}
            <div style="color:var(--textMuted); font-size:12.5px;">
                {{ __('The decision comes from the Yes/No boxes above. Registro publishes it; it cannot be changed afterwards.') }}
            </div>
            <button type="button" class="btn btn-primary" style="align-self:flex-start;"
                wire:click="decideRequest('')" wire:loading.attr="disabled">
                {{ __('Resolve and send to Registro') }}
            </button>
            @else
            {{-- Un levantamiento no tiene tabla de cursos. --}}
            <div style="color:var(--textMuted); font-size:12.5px;">
                {{ __('Registro publishes what you decide here; it cannot be changed afterwards.') }}
            </div>
            <div class="card-actions" style="flex-wrap:wrap;">
                <button type="button" class="btn btn-primary"
                    wire:click="decideRequest('{{ $studentRequestClass::STATUS_APPROVED }}')" wire:loading.attr="disabled">
                    {{ __('Approve and send to Registro') }}
                </button>
                <button type="button" class="btn btn-orange"
                    wire:click="decideRequest('{{ $studentRequestClass::STATUS_DENIED }}')" wire:loading.attr="disabled">
                    {{ __('Deny and send to Registro') }}
                </button>
            </div>
            @endif
        </div>
        @endif

        {{-- REGISTRO: publica lo que Docencia resolvió. --}}
        @if ($requestDetail['canPublish'])
        <div class="form-field detail-span" style="border:1px solid var(--border); border-radius:10px; padding:12px;">
            <label>{{ __('Issue resolution (RSREC-001)') }}</label>
            <div style="margin-bottom:8px;">
                {{ __('Docencia resolved:') }}
                <span class="status-badge {{ $requestDetail['teachingDecision'] === $studentRequestClass::STATUS_APPROVED ? 'success' : 'danger' }}">
                    {{ $requestDetail['teachingDecision'] }}
                </span>
            </div>
            <div class="course-row" style="grid-template-columns:repeat(4, 1fr);">
                <div>
                    <label for="resolutionNumber">{{ __('Resolution number') }}</label>
                    <input type="text" id="resolutionNumber" wire:model="resolutionForm.resolutionNumber">
                </div>
                <div>
                    <label for="sessionNumber">{{ __('Session number') }}</label>
                    <input type="text" id="sessionNumber" wire:model="resolutionForm.sessionNumber">
                </div>
                <div>
                    <label for="minutesNumber">{{ __('Minutes number') }}</label>
                    <input type="text" id="minutesNumber" wire:model="resolutionForm.minutesNumber">
                </div>
                <div>
                    <label for="sessionDate">{{ __('Session date') }}</label>
                    <input type="date" id="sessionDate" wire:model="resolutionForm.sessionDate">
                </div>
            </div>
            <div style="color:var(--textMuted); font-size:12.5px;">
                {{ __('The document is filed with the request and emailed to the student as an attachment.') }}
            </div>
            <button type="button" class="btn btn-primary" style="align-self:flex-start;"
                wire:click="publishResolution" wire:loading.attr="disabled">
                {{ __('Issue and publish resolution') }}
            </button>
        </div>
        @elseif ($requestDetail['resolutionDetails'] && $requestDetail['resolutionDetails']['resolutionNumber'])
        {{-- Le faltaba detail-span: sin eso quedaba en una sola columna de
             .detail-grid (mínimo 260px) y el texto se cortaba entre líneas. --}}
        <div class="form-field detail-span">
            <label>{{ __('Issued resolution') }}</label>
            <div>
                {{ __('Resolution number') }}: <strong>{{ $requestDetail['resolutionDetails']['resolutionNumber'] }}</strong>
                · {{ __('Session number') }}: {{ $requestDetail['resolutionDetails']['sessionNumber'] }}
                · {{ __('Minutes number') }}: {{ $requestDetail['resolutionDetails']['minutesNumber'] }}
                · {{ $requestDetail['resolutionDetails']['sessionDate'] }}
            </div>
        </div>
        @endif

        {{-- ===== Expediente del estudiante ===== --}}
        @if ($requestDetail['studentFile'])
        @php($file = $requestDetail['studentFile'])
        {{-- El plegado vive en una propiedad del componente. --}}
        <div class="form-field detail-span" style="border:1px solid var(--border); border-radius:10px; padding:12px;">
            <button type="button" class="record-toggle" wire:click="toggleStudentFile">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"
                     style="transition:transform .15s; {{ $showStudentFile ? 'transform:rotate(90deg);' : '' }}">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span>{{ __('Student academic record') }}</span>
                <span style="color:var(--textMuted); font-weight:400; font-size:12.5px;">
                    · {{ count($file['courses']) }} {{ __('courses') }}
                </span>
            </button>

            @if ($showStudentFile)
            <x-ui.academic-record :file="$file" />
            @endif
        </div>
        @endif

        {{-- Fijar la fecha estimada es de quien RESUELVE. --}}
        @if ($requestDetail['canUpdate'])
        <div class="form-field detail-span">
            <label for="manualResolutionDate">{{ __('Set estimated resolution date') }}</label>
            <input type="date" id="manualResolutionDate" wire:model="manualResolutionDate">
            @error('manualResolutionDate') <span class="form-error">{{ $message }}</span> @enderror
            <button type="button" class="btn btn-secondary" style="margin-top:8px;" wire:click="assignManualResolutionDate">{{ __('Save date') }}</button>
        </div>
        @endif

        {{-- El bloque de cambio de estado NO depende de canUpdate. --}}
        @if (count($requestDetail['genericStatuses']) > 0)
        <div class="form-field detail-span">
            <label for="statusChangeComment">{{ __('Comment (optional)') }}</label>
            <textarea id="statusChangeComment" wire:model="statusChangeComment"></textarea>
        </div>
        <div class="form-field detail-span">
            <label>{{ __('Change status to') }}</label>
            @if ($pendingStatus !== '')
            {{-- El menú de Acciones ya eligió a qué estado. --}}
            <div style="margin-bottom:8px; color:var(--textMuted);">{{ __('Selected action:') }} <strong>{{ $pendingStatus }}</strong></div>
            @endif
            <div class="card-actions" style="flex-wrap:wrap;">
                @foreach ($requestDetail['genericStatuses'] as $nextStatus)
                {{-- Denegar en rojo, el resto en azul. --}}
                <button type="button"
                    class="btn {{ $nextStatus === $studentRequestClass::STATUS_DENIED ? 'btn-orange' : ($pendingStatus === $nextStatus ? 'btn-primary' : 'btn-secondary') }}"
                    wire:click="changeRequestStatus('{{ $nextStatus }}')">{{ $nextStatus }}</button>
                @endforeach
            </div>
        </div>
        @endif

        <div class="form-field detail-span">
            <label>{{ __('Attachments') }}</label>
            @forelse ($requestDetail['attachments'] as $attachment)
            <div>
                <button type="button" class="btn btn-secondary" style="margin:4px 4px 0 0;" wire:click="downloadAttachment({{ $attachment['id'] }})">
                    {{ $attachment['documentType'] }} — {{ $attachment['originalName'] }} ({{ $attachment['sizeLabel'] }})
                </button>
                @if ($attachment['previewUrl'])
                {{-- Ver y descargar como acciones distintas. --}}
                <a href="{{ $attachment['previewUrl'] }}" target="_blank" rel="noopener"
                   class="btn btn-secondary" style="margin:4px 4px 0 0;">{{ __('View') }}</a>
                @endif
            </div>
            @empty
            <div>{{ __('No attachments.') }}</div>
            @endforelse
        </div>

        @if ($requestDetail['canUpdate'])
        {{-- Documentos que sube el PERSONAL sobre la solicitud. --}}
        <div class="form-field detail-span">
            <label for="staffAttachmentType">{{ __('Document type') }}</label>
            <input type="text" id="staffAttachmentType" wire:model="newAttachmentType"
                placeholder="{{ __('E.g. Technical criterion, Resolution') }}"
                class="{{ $errors->has('newAttachmentType') ? 'has-error' : '' }}">
            @error('newAttachmentType') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-field detail-span">
            <label for="staffAttachmentFile">{{ __('Attach document (PDF, image, Word or Excel, max :size)', ['size' => $uploadLimit]) }}</label>
            <input type="file" id="staffAttachmentFile" wire:model="newAttachment"
                class="{{ $errors->has('newAttachment') ? 'has-error' : '' }}">
            @error('newAttachment') <span class="form-error">{{ $message }}</span> @enderror
            <div wire:loading wire:target="newAttachment" style="margin-top:6px;">{{ __('Uploading...') }}</div>
            <button type="button" class="btn btn-secondary" style="margin-top:8px;" wire:click="uploadStaffAttachment">{{ __('Attach') }}</button>
        </div>
        @endif

        {{-- Avance del trámite. --}}
        <div class="form-field detail-span">
            <label>{{ __('Request progress') }}</label>
            <div class="request-progress">
                @foreach ($requestDetail['progress'] as $step)
                <div class="progress-step {{ $step['reached'] ? 'reached' : '' }} {{ $step['current'] ? 'current' : '' }}">
                    <span class="progress-dot"></span>
                    <span class="progress-label">{{ $step['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="form-field detail-span">
            <label>{{ __('Status history') }}</label>
            @forelse ($requestDetail['history'] as $entry)
            <div style="padding:8px 0; border-bottom:1px solid var(--border);">
                <div>
                    {{ $entry['previousStatus'] ?? __('(new)') }} → <strong>{{ $entry['newStatus'] }}</strong>
                    <span class="status-badge {{ $entry['userId'] === null ? 'neutral' : 'info' }}" style="margin-left:6px;">{{ $entry['author'] }}</span>
                </div>
                {{-- Fecha y hora del cambio. --}}
                @if ($entry['occurredAt'])
                <div style="color:var(--textMuted); font-size:12.5px; margin-top:2px;">{{ $entry['occurredAt'] }}</div>
                @endif
                @if ($entry['comment'])
                <div style="color:var(--textMuted); font-size:12.5px; margin-top:3px;">{{ $entry['comment'] }}</div>
                @endif
            </div>
            @empty
            <div>{{ __('No history yet.') }}</div>
            @endforelse
        </div>
        </div>
        @endif

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Close') }}</button>
        </x-slot:footer>
    </x-ui.modal>
</div>
