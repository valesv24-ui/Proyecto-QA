<?php

declare(strict_types=1);

/*
| Mensajes del flujo de restablecimiento de contraseña.
|
| Los emite el password broker de Laravel, no una vista nuestra: por eso no
| aparecían en el chequeo de claves __() y salían en inglés en la pantalla de
| "olvidé mi contraseña" pese a que el resto de esa pantalla ya estaba
| traducida.
*/

return [

    'reset' => 'Su contraseña ha sido restablecida.',
    'sent' => 'Le enviamos por correo el enlace para restablecer su contraseña.',
    'throttled' => 'Espere un momento antes de volver a intentarlo.',
    'token' => 'El enlace para restablecer la contraseña no es válido o ya expiró.',
    'user' => 'No encontramos ninguna cuenta con ese correo electrónico.',

];
