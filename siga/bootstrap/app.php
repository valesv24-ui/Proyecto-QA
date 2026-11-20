<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Src\Shared\Attachment\Presentation\Support\UploadLimit;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Cuando el cuerpo de la petición supera post_max_size.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $message = __('The file exceeds what this server accepts (:size). Ask your administrator to raise post_max_size.', [
                'size' => UploadLimit::label(),
            ]);

            // '*/upload-file' y no 'livewire/upload-file'.
            if ($request->is('*/upload-file')) {
                return response()->json(['errors' => ['files.0' => [$message]]], 422);
            }

            return response()->json(['message' => $message], 413);
        });
    })->create();
