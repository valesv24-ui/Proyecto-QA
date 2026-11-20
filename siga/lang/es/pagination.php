<?php

declare(strict_types=1);

/*
| Enlaces del paginador de Laravel.
|
| Las tablas del módulo usan su propio paginador en Blade (ver
| data-table.blade.php), así que estos textos solo aparecen si alguna vista
| llama a ->links() directamente. Se traducen igual: una clave faltante cae
| al inglés en silencio, y ese es justo el tipo de cadena que se cuela sin
| que nadie la note hasta que un usuario la ve.
*/

return [

    'previous' => '&laquo; Anterior',
    'next' => 'Siguiente &raquo;',

];
