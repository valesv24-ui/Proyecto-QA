/**
 * Tipos ambiente mínimos para Alpine.js.
 *
 * El proyecto no instala el paquete @types/alpinejs a propósito: Alpine no
 * expone una API tipada que valga la pena describir por completo (es un
 * proxy dinámico), así que en vez de una dependencia externa se declaran
 * aquí solo las dos piezas que los componentes de este proyecto realmente
 * usan:
 *
 *   - El global `Alpine` con la única función que se llama desde este
 *     código: `Alpine.data(nombre, fábrica)`.
 *   - `AlpineComponent<T>`, que describe el `this` real dentro de
 *     `init()`/getters/métodos: el propio estado del componente (T) más
 *     las "magic properties" de Alpine que el código usa ($watch, $refs).
 *
 * Al ser un archivo .d.ts sin `import`/`export`, TypeScript lo trata como
 * declaración global — cualquier otro .ts del proyecto puede usar
 * `AlpineComponent<...>` sin importarlo, igual que `Alpine` ya es global en
 * tiempo de ejecución (lo registra el propio paquete Livewire/Alpine).
 */
declare const Alpine: {
    data(name: string, factory: (...args: any[]) => object): void;
};

type AlpineComponent<T> = T & {
    /** Registra un callback que corre cada vez que la propiedad cambia. */
    $watch(property: string, callback: (value: unknown, oldValue: unknown) => void): void;
    /** Elementos marcados con x-ref dentro de este componente. */
    $refs: Record<string, HTMLElement | undefined>;
    /**
     * El componente de Livewire que envuelve a este componente de Alpine.
     *
     * `set` escribe una propiedad del servidor desde el cliente cuando
     * ningún evento del DOM lo haría por sí solo — el caso de quitar el
     * último archivo de una zona de subida, donde un input vacío no
     * dispara ninguna subida.
     *
     * `entangle` devuelve un valor reactivo ligado en ambas direcciones a
     * una propiedad de Livewire (a diferencia de `set`, que solo escribe
     * una vez); se tipa como `any` porque el objeto que retorna no es un
     * valor plano sino un proxy con soporte además para encadenar `.live`
     * (`entangle('x').live`), algo que no vale la pena describir por
     * completo — el mismo criterio ya usado arriba para `Alpine.data`.
     */
    $wire: {
        set(property: string, value: unknown): Promise<unknown>;
        entangle(property: string): any;
    };
};
