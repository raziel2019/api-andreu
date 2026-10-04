<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['legal_name', 'tax_identifier', 'postal_address', 'privacy_contact_email', 'professional_registration', 'data_controller_name', 'location', 'callout_title', 'callout_content', 'callout_label', 'footer_description', 'footer_cta_label', 'footer_copyright', 'contact_email', 'phone', 'linkedin_url', 'instagram_url', 'logo_path', 'callout_destination', 'footer_cta_destination'])]
class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'location' => 'array',
            'callout_title' => 'array',
            'callout_content' => 'array',
            'callout_label' => 'array',
            'footer_description' => 'array',
            'footer_cta_label' => 'array',
            'footer_copyright' => 'array',
        ];
    }
}
