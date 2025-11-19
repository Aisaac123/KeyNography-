<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class PintRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pint:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta Laravel Pint para formatear el código';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ejecuta Pint usando shell
        $this->info('Ejecutando Pint...');

        // Se asume que Pint está instalado con Composer
        $exitCode = null;
        $output = null;

        exec('vendor\\bin\\pint.bat', $output, $exitCode);

        // Mostrar salida en consola
        foreach ($output as $line) {
            $this->line($line);
        }

        return $exitCode === 0 ? CommandAlias::SUCCESS : CommandAlias::FAILURE;
    }
}
