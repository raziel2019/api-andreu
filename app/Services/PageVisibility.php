<?php

namespace App\Services;

use App\Models\AboutPage;
use App\Models\ContactPage;
use App\Models\HomePage;
use App\Models\LegalPage;
use App\Models\PortfolioPage;
use App\Models\ServicePage;
use Illuminate\Database\Eloquent\Model;

class PageVisibility
{
    public const LABELS = ['home' => 'Home', 'about' => 'Sobre mi', 'services' => 'Serveis', 'portfolio' => 'Portafoli', 'contact' => 'Contacte', 'legal_notice' => 'Avís legal', 'privacy' => 'Privacitat', 'cookies' => 'Cookies'];

    private const MODELS = ['home' => HomePage::class, 'about' => AboutPage::class, 'services' => ServicePage::class, 'portfolio' => PortfolioPage::class, 'contact' => ContactPage::class];

    private const LEGAL_TYPES = ['legal_notice' => 'legal_notice', 'privacy' => 'privacy_policy', 'cookies' => 'cookie_policy'];

    public function page(string $key): Model
    {
        if (isset(self::MODELS[$key])) {
            return self::MODELS[$key]::findOrFail(1);
        }

        abort_unless(isset(self::LEGAL_TYPES[$key]), 404);

        return LegalPage::where('type', self::LEGAL_TYPES[$key])->firstOrFail();
    }

    /** @return array<string, bool> */
    public function publicMap(): array
    {
        $pages = [];
        foreach (self::MODELS as $key => $model) {
            $pages[$key] = $model::whereKey(1)->where('active', true)->exists();
        }
        $legal = LegalPage::whereIn('type', array_values(self::LEGAL_TYPES))->pluck('active', 'type');
        foreach (self::LEGAL_TYPES as $key => $type) {
            $pages[$key] = (bool) ($legal[$type] ?? false);
        }

        return $pages;
    }
}
