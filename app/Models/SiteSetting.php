<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['interface_media->action_arrow', 'interface_media->service_arrow', 'interface_media->footer_arrow', 'interface_media->contact_callout', 'interface_media->contact_message', 'interface_media->contact_location', 'interface_media->menu_open', 'interface_media->menu_close', 'interface_media->email', 'interface_media->linkedin', 'interface_media->instagram', 'primary_color', 'background_color', 'legal_name', 'tax_identifier', 'postal_address', 'privacy_contact_email', 'professional_registration', 'data_controller_name', 'location', 'callout_title', 'callout_content', 'callout_label', 'footer_description', 'footer_cta_label', 'footer_copyright', 'contact_email', 'phone', 'whatsapp_number', 'linkedin_url', 'instagram_url', 'logo_path', 'callout_destination', 'footer_cta_destination'])]
class SiteSetting extends Model
{
    public const DEFAULT_PRIMARY_COLOR = '#8F6C35';

    public const DEFAULT_BACKGROUND_COLOR = '#FFFFFF';

    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory, HasTranslations;

    public function whatsappUrl(): ?string
    {
        return is_string($this->whatsapp_number) && preg_match('/^\+[1-9][0-9]{7,14}$/D', $this->whatsapp_number)
            ? 'https://wa.me/'.substr($this->whatsapp_number, 1)
            : null;
    }

    protected function casts(): array
    {
        return [
            'interface_media' => 'array',
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
