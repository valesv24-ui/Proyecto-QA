/**
 * Desplegable propio para elegir curso, en reemplazo del <select> nativo.
 *
 * Por qué existe: el <select> nativo del navegador/SO no se puede acotar ni
 * posicionar con CSS del autor — con muchos cursos (AC-100 a ISW-1311, por
 * ejemplo) su popup nativo podía salirse de la pantalla, y eso no tiene
 * arreglo posible desde aquí. La única solución real es dejar de usar el
 * popup nativo y dibujar la lista nosotros mismos.
 *
 * Posicionamiento: igual que resources/js/row-actions-menu.ts y por el mismo
 * motivo — este control vive dentro de .modal-body (overflow-y: auto), así
 * que un menú con `position: absolute` quedaría recortado por ese ancestro.
 * `position: fixed` con coordenadas calculadas a mano contra
 * getBoundingClientRect() ignora ese recorte.
 *
 * Valor: se usa $wire.entangle() en vez de un <select> oculto con eventos
 * "change" manuales. Un reseteo del lado del servidor (p. ej. limpiar el
 * formulario tras enviarlo) hace que Livewire actualice el DOM directamente
 * vía morphdom, sin disparar "change" — un <select> oculto se quedaría
 * mostrando el valor viejo. entangle() sincroniza en ambas direcciones sin
 * ese problema.
 *
 * OJO con dónde se llama entangle(): devuelve un INTERCEPTOR, no un valor, y
 * Alpine solo los resuelve al construir el objeto de x-data. Si se asigna
 * dentro de init() ya es tarde: la propiedad queda con el objeto crudo y el
 * primer choose() lo pisa, con lo que el control sigue andando en pantalla
 * pero deja de escribirle al servidor. Por eso entra como argumento desde la
 * vista y se coloca directo en `value`.
 *
 * Lista de opciones: entra por config.options en la inicialización, NO se
 * vuelve a leer sola en cada renderizado de Livewire — si las opciones
 * dependen de otro campo (p. ej. los requisitos pendientes dependen del
 * curso elegido), la vista debe forzar la reinicialización con un
 * `wire:key` que cambie junto con esa dependencia. Es el mismo patrón que ya
 * usa x-ui.data-table (`wire:key="crud-table-{{ $tableVersion ?? 0 }}"`)
 * para su propio x-data con `rows` de config.
 */

interface CourseSelectOption {
    value: string;
    label: string;
}

interface CourseSelectConfig {
    options?: CourseSelectOption[];
    placeholder?: string;
}

/** El servidor devuelve int; las opciones del DOM son string. */
type CourseSelectValue = string | number | null;

interface CourseSelectState {
    open: boolean;
    value: CourseSelectValue;
    options: CourseSelectOption[];
    placeholder: string;

    /** Margen mínimo contra el borde del viewport, en píxeles. */
    margin: number;

    /** Estilo inline calculado al abrir (left/top/width en coordenadas de viewport). */
    style: string;

    currentValue(this: AlpineComponent<CourseSelectState>): string;
    selectedLabel(this: AlpineComponent<CourseSelectState>): string;
    toggle(this: AlpineComponent<CourseSelectState>): void;
    show(this: AlpineComponent<CourseSelectState>): void;
    close(this: AlpineComponent<CourseSelectState>): void;
    choose(this: AlpineComponent<CourseSelectState>, option: CourseSelectOption): void;
}

document.addEventListener("alpine:init", () => {
    Alpine.data("courseSelect", (
        config: CourseSelectConfig = {},
        entangled?: unknown,
    ): CourseSelectState => ({
        open: false,
        // Interceptor de entangle(): Alpine lo resuelve al construir x-data.
        value: (entangled ?? null) as CourseSelectValue,
        options: config.options ?? [],
        placeholder: config.placeholder ?? "",
        margin: 8,
        style: "",

        currentValue(this: AlpineComponent<CourseSelectState>) {
            return this.value === null || this.value === undefined ? "" : String(this.value);
        },

        selectedLabel(this: AlpineComponent<CourseSelectState>) {
            const current = this.currentValue();
            const selected = this.options.find((option) => option.value === current);
            return selected ? selected.label : this.placeholder;
        },

        toggle(this: AlpineComponent<CourseSelectState>) {
            this.open ? this.close() : this.show();
        },

        show(this: AlpineComponent<CourseSelectState>) {
            const trigger = this.$refs.trigger;
            const menu = this.$refs.menu;
            if (!trigger || !menu) {
                return;
            }

            const rect = trigger.getBoundingClientRect();

            // El menú está oculto con display:none mientras no tiene .open, y un
            // elemento sin renderizar mide 0 — se hace medible primero
            // (visibility:hidden mantiene invisible pero con caja real) y recién
            // después se calcula si cabe hacia abajo.
            menu.style.visibility = "hidden";
            menu.style.display = "block";
            const menuHeight = menu.offsetHeight;
            menu.style.display = "";
            menu.style.visibility = "";

            let top = rect.bottom + 6;
            if (top + menuHeight > window.innerHeight - this.margin) {
                // No cabe hacia abajo: se abre hacia arriba, como
                // row-actions-menu, para no salirse de la pantalla en filas
                // cercanas al borde inferior.
                top = rect.top - menuHeight - 6;
            }
            if (top < this.margin) {
                top = this.margin;
            }

            let left = rect.left;
            if (left + rect.width > window.innerWidth - this.margin) {
                left = window.innerWidth - this.margin - rect.width;
            }
            if (left < this.margin) {
                left = this.margin;
            }

            this.style = `left:${Math.round(left)}px; top:${Math.round(top)}px; width:${Math.round(rect.width)}px;`;
            this.open = true;
        },

        close(this: AlpineComponent<CourseSelectState>) {
            this.open = false;
        },

        choose(this: AlpineComponent<CourseSelectState>, option: CourseSelectOption) {
            this.value = option.value;
            this.close();
        },
    }));
});
