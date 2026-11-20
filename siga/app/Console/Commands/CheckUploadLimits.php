<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Src\Shared\Attachment\Domain\Entities\Attachment;
use Src\Shared\Attachment\Presentation\Support\UploadLimit;

/** Muestra los CUATRO límites que puede atravesar un adjunto. */
final class CheckUploadLimits extends Command
{
    protected $signature = 'uploads:limits';

    protected $description = 'Muestra los límites de subida de PHP, Livewire y el módulo';

    public function handle(): int
    {
        $php = [
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'post_max_size' => (string) ini_get('post_max_size'),
            'file_uploads' => ini_get('file_uploads') ? 'On' : 'Off',
            'upload_tmp_dir' => ((string) ini_get('upload_tmp_dir')) ?: '(el del sistema)',
        ];

        $this->line('');
        $this->line('  <options=bold>PHP</> (php.ini en uso: '.(php_ini_loaded_file() ?: 'ninguno').')');

        foreach ($php as $directive => $value) {
            $this->line(sprintf('    %-22s %s', $directive, $value));
        }

        /** @var array<int, string> $livewireRules */
        $livewireRules = config('livewire.temporary_file_upload.rules', []);

        $this->line('');
        $this->line('  <options=bold>Livewire</> (valida cada archivo ANTES de que se envíe el formulario)');
        $this->line('    reglas                 '.implode(' | ', $livewireRules));

        $this->line('');
        $this->line('  <options=bold>Módulo</>');
        $this->line('    Attachment             '.(int) (Attachment::MAX_SIZE_BYTES / 1024 / 1024).' MB');
        $this->line('    límite efectivo        '.UploadLimit::label());

        $this->line('');

        // Los dos errores que de verdad se cometen al editar.
        foreach (['upload_max_filesize', 'post_max_size'] as $directive) {
            $raw = $php[$directive];

            if (preg_match('/^\d+[BbKkMmGg]?$/', $raw) !== 1) {
                $this->warn("  ¡Ojo! {$directive} = \"{$raw}\" no tiene un formato que PHP entienda.");
            }

            // "12MB" es el error clásico: PHP lee el número.
            if (str_ends_with(strtolower($raw), 'b') && ! str_ends_with(strtolower($raw), 'kb')) {
                $this->warn("  ¡Ojo! {$directive} = \"{$raw}\": el sufijo es M o K, sin la B. \"12MB\" son 12 bytes para PHP.");
            }
        }

        if (UploadLimit::isConstrainedByPhp()) {
            $this->warn('  PHP acepta menos que el módulo: manda '.UploadLimit::label().', no '.(int) (Attachment::MAX_SIZE_BYTES / 1024 / 1024).' MB.');
        }

        $this->line('  Si acabás de editar php.ini, reiniciá `php artisan serve`: el servidor lee la configuración al arrancar.');
        $this->line('');

        return self::SUCCESS;
    }
}
