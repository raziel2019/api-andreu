<?php

namespace App\Console\Commands;

use App\Services\MediaCatalog;
use Illuminate\Console\Command;

class ImportMediaAssets extends Command
{
    protected $signature = 'cms:import-media';

    protected $description = 'Registra mitjans existents sense copiar, substituir ni eliminar fitxers';

    public function handle(MediaCatalog $catalog): int
    {
        $this->info('Fitxers disponibles: '.$catalog->import());

        return self::SUCCESS;
    }
}
