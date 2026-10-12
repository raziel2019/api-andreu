<?php

namespace Database\Seeders;

use App\Services\MediaCatalog;
use Illuminate\Database\Seeder;

class MediaAssetSeeder extends Seeder
{
    public function run(MediaCatalog $catalog): void
    {
        $catalog->import();
    }
}
