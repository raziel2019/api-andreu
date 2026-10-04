<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['legal_notice' => ['ca' => 'Avís legal', 'es' => 'Aviso legal'], 'privacy_policy' => ['ca' => 'Política de privacitat', 'es' => 'Política de privacidad'], 'cookie_policy' => ['ca' => 'Política de cookies', 'es' => 'Política de cookies']] as $type => $title) {
            LegalPage::firstOrCreate(['type' => $type], ['title' => $title, 'body' => ['ca' => null, 'es' => null], 'active' => false]);
        }
    }
}
