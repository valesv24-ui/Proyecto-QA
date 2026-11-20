/**
 * Zona única de arrastrar-y-soltar para una lista ABIERTA de documentos de
 * apoyo de una solicitud (ES-02). Ya no reparte archivos entre varios
 * documentos con nombre propio — hay un solo <input type="file" multiple>
 * atado a un wire:model de arreglo (p. ej. `transferForm.attachments`), y
 * el estudiante puede adjuntar tantos archivos como necesite.
 *
 * `accumulatedFiles` es la fuente de verdad del lado del cliente: cada vez
 * que se sueltan archivos o se elige algo con el selector nativo, se suman
 * ahí y se vuelve a armar por completo el FileList del input real
 * (x-ref="realInput") a partir de la lista acumulada — nunca se le asigna
 * directamente lo que acaba de soltarse o elegirse, porque eso
 * reemplazaría cualquier archivo ya cargado antes en vez de sumarse.
 *
 * El selector nativo vive en un input APARTE (x-ref="picker", sin
 * wire:model) por la misma razón: abrir el diálogo del sistema operativo
 * directamente sobre el input real perdería lo ya acumulado, porque un
 * diálogo nativo siempre REEMPLAZA la selección en vez de agregarle.
 *
 * El límite de tamaño (10 MB) no se valida aquí a propósito, mismo criterio
 * que el resto del formulario: el servidor (TransferRequestForm::rules(),
 * Attachment::MAX_SIZE_BYTES) es la única fuente de verdad.
 */

interface OpenFileDropzoneState {
    /**
     * Nombre de la propiedad de Livewire (p. ej. "transferForm.attachments").
     *
     * Hace falta para poder VACIARLA explícitamente: un input de archivos sin
     * ningún archivo no dispara ninguna subida, así que quitar el último
     * documento dejaría la pantalla vacía y la propiedad del servidor con lo
     * de antes — y se enviaría un archivo que el estudiante ya había quitado.
     */
    wireModel: string;

    isDragging: boolean;

    /** Fuente de verdad del lado del cliente: todo lo acumulado hasta ahora. */
    accumulatedFiles: File[];

    /** Solo para mostrar en pantalla — se deriva de accumulatedFiles. */
    fileNames: string[];

    onDragEnter(this: AlpineComponent<OpenFileDropzoneState>): void;
    onDragLeave(this: AlpineComponent<OpenFileDropzoneState>): void;
    onDrop(this: AlpineComponent<OpenFileDropzoneState>, event: DragEvent): void;
    onPickerChange(this: AlpineComponent<OpenFileDropzoneState>, event: Event): void;
    openPicker(this: AlpineComponent<OpenFileDropzoneState>): void;
    addFiles(this: AlpineComponent<OpenFileDropzoneState>, files: FileList): void;
    removeFile(this: AlpineComponent<OpenFileDropzoneState>, index: number): void;
    syncRealInput(this: AlpineComponent<OpenFileDropzoneState>): void;
}

document.addEventListener("alpine:init", () => {
    Alpine.data("openFileDropzone", (wireModel: string): OpenFileDropzoneState => ({
        wireModel,
        isDragging: false,
        accumulatedFiles: [],
        fileNames: [],

        onDragEnter(this: AlpineComponent<OpenFileDropzoneState>) {
            this.isDragging = true;
        },

        onDragLeave(this: AlpineComponent<OpenFileDropzoneState>) {
            this.isDragging = false;
        },

        onDrop(this: AlpineComponent<OpenFileDropzoneState>, event: DragEvent) {
            this.isDragging = false;

            const files = event.dataTransfer?.files;
            if (files) {
                this.addFiles(files);
            }
        },

        onPickerChange(this: AlpineComponent<OpenFileDropzoneState>, event: Event) {
            const input = event.target as HTMLInputElement;
            if (input.files) {
                this.addFiles(input.files);
            }

            // Sin esto, elegir el mismo archivo en una segunda vuelta no
            // dispara un segundo "change" — el navegador considera que el
            // valor del input no cambió.
            input.value = "";
        },

        openPicker(this: AlpineComponent<OpenFileDropzoneState>) {
            (this.$refs.picker as HTMLInputElement | undefined)?.click();
        },

        addFiles(this: AlpineComponent<OpenFileDropzoneState>, files: FileList) {
            for (let i = 0; i < files.length; i++) {
                this.accumulatedFiles.push(files[i]);
            }
            this.syncRealInput();
        },

        /**
         * Quitar un documento ya agregado, ANTES de enviar la solicitud.
         *
         * Hasta ahora solo se podía sumar: equivocarse de archivo obligaba a
         * recargar y volver a llenar el formulario entero. Agregar y quitar
         * son las dos mitades de la misma acción — una lista donde solo se
         * puede agregar no es una lista, es un embudo.
         *
         * Se quita de accumulatedFiles y se reconstruye el input real, que es
         * el mismo camino que ya usa addFiles(): así el borrado no necesita
         * su propia lógica de sincronización que pueda desviarse de la otra.
         * Livewire vuelve a subir los archivos que quedan, porque su
         * propiedad se reemplaza entera. Es el costo de no llevar dos fuentes
         * de verdad (la lista del cliente y la del servidor) que puedan
         * contradecirse; entre volver a subir dos PDF y arriesgar que la
         * pantalla muestre una lista distinta de la que se va a enviar, se
         * prefiere lo primero.
         */
        removeFile(this: AlpineComponent<OpenFileDropzoneState>, index: number) {
            this.accumulatedFiles.splice(index, 1);
            this.syncRealInput();
        },

        syncRealInput(this: AlpineComponent<OpenFileDropzoneState>) {
            const input = this.$refs.realInput as HTMLInputElement | undefined;
            if (!input) {
                return;
            }

            // input.files es de solo lectura salvo que se le asigne un
            // FileList real; DataTransfer es la forma estándar de construir
            // uno a mano a partir de un arreglo de File. Se reconstruye
            // ENTERO desde accumulatedFiles en cada llamada — así el input
            // real siempre refleja todo lo acumulado, no solo lo último
            // soltado o elegido.
            const transfer = new DataTransfer();
            this.accumulatedFiles.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;

            if (this.accumulatedFiles.length === 0) {
                // Un "change" con cero archivos no le dice nada a Livewire: su
                // manejador sube lo que haya en el input, y si no hay nada, no
                // sube nada — dejando en el servidor los archivos de la vuelta
                // anterior. Vaciar la propiedad a mano es lo único que hace
                // que quitar el último documento signifique de verdad
                // quitarlo.
                this.$wire.set(this.wireModel, []);
            } else {
                input.dispatchEvent(new Event("change", { bubbles: true }));
            }

            this.fileNames = this.accumulatedFiles.map((file) => file.name);
        },
    }));
});
