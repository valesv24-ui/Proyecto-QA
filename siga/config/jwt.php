<?php

declare(strict_types=1);

// Tarea 2.4: configuración de la capa de API por token.
return [
    /*
     * Clave de firma de los tokens. Si no se define JWT_SECRET en .env, cae.
     * a APP_KEY para que la API funcione sin configuración adicional.
     * inmediatamente después de clonar el proyecto — pero en un entorno.
     * real conviene usar una clave propia, dedicada solo a esto.
     */
    'secret' => env('JWT_SECRET', env('APP_KEY')),

    /*
     * Minutos de validez de un token emitido por POST /api/auth/login.
     */
    'ttl_minutes' => (int) env('JWT_TTL_MINUTES', 60),
];
