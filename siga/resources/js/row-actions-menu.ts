/**
 * Menú desplegable de acciones por fila, anclado al botón que lo abre pero
 * renderizado FUERA de cualquier contenedor que recorte.
 *
 * Por qué existe: un menú con `position: absolute` dentro de una fila queda
 * recortado por dos ancestros de la tabla, y ninguno de los dos se puede
 * quitar sin romper otra cosa:
 *
 *   - .card  { overflow: hidden }   -> necesario para que el contenido
 *     respete las esquinas redondeadas de la tarjeta.
 *   - .table-scroll { overflow-x: auto } -> necesario para el scroll
 *     horizontal en pantallas angostas. Ojo: por especificación, poner
 *     overflow-x en `auto` fuerza el overflow-y a `auto` también, así que
 *     este recorta en vertical aunque solo se pidiera scroll horizontal.
 *     Ese es el motivo real de que el menú se corte hacia abajo.
 *
 * La solución es `position: fixed`, que se posiciona respecto al viewport y
 * por lo tanto ignora ambos recortes. A cambio hay que calcular las
 * coordenadas a mano al abrir (un elemento fixed no sigue a su botón) y
 * cerrar el menú si la página se desplaza o cambia de tamaño, en vez de
 * dejarlo flotando en un punto que ya no corresponde.
 *
 * Uso (Blade):
 *   <div x-data="rowActionsMenu()" x-on:click.outside="close()">
 *     <button x-ref="trigger" @click="toggle()">Acciones</button>
 *     <div x-ref="menu" class="row-actions-menu" :class="{ 'open': open }" :style="style">
 *       <button @click="close(); $wire.algo(1)">...</button>
 *     </div>
 *   </div>
 */

interface RowActionsMenuConfig {
    width?: number;
}

interface RowActionsMenuState {
    open: boolean;

    /** Ancho fijo del menú; debe coincidir con .row-actions-menu en app.css. */
    width: number;

    /** Margen mínimo contra el borde del viewport, en píxeles. */
    margin: number;

    /** Estilo inline calculado al abrir (left/top en coordenadas de viewport). */
    style: string;

    toggle(this: AlpineComponent<RowActionsMenuState>): void;
    show(this: AlpineComponent<RowActionsMenuState>): void;
    close(this: AlpineComponent<RowActionsMenuState>): void;
}

document.addEventListener("alpine:init", () => {
    Alpine.data("rowActionsMenu", (config: RowActionsMenuConfig = {}): RowActionsMenuState => ({
        open: false,
        width: config.width ?? 230,
        margin: 8,
        style: "",

        toggle(this: AlpineComponent<RowActionsMenuState>) {
            this.open ? this.close() : this.show();
        },

        show(this: AlpineComponent<RowActionsMenuState>) {
            const trigger = this.$refs.trigger;
            const menu = this.$refs.menu;
            if (!trigger || !menu) {
                return;
            }

            const rect = trigger.getBoundingClientRect();

            // El menú está oculto con display:none mientras no tiene .open, y un
            // elemento sin renderizar mide 0 — así que se hace medible primero
            // (visibility:hidden lo mantiene invisible pero con caja real) y solo
            // después se calcula si cabe hacia abajo.
            menu.style.visibility = "hidden";
            menu.style.display = "block";
            const menuHeight = menu.offsetHeight;
            menu.style.display = "";
            menu.style.visibility = "";

            // Alineado a la derecha del botón, como estaba con position:absolute,
            // pero sin dejar que se salga por el borde izquierdo en pantallas
            // angostas.
            let left = rect.right - this.width;
            if (left < this.margin) {
                left = this.margin;
            }

            // Si no cabe hacia abajo, se abre hacia arriba. Sin esto, las últimas
            // filas de la tabla abrirían el menú fuera de la pantalla.
            let top = rect.bottom + 6;
            if (top + menuHeight > window.innerHeight - this.margin) {
                top = rect.top - menuHeight - 6;
            }
            if (top < this.margin) {
                top = this.margin;
            }

            this.style = `left:${Math.round(left)}px; top:${Math.round(top)}px; width:${this.width}px;`;
            this.open = true;
        },

        close(this: AlpineComponent<RowActionsMenuState>) {
            this.open = false;
        },
    }));
});
