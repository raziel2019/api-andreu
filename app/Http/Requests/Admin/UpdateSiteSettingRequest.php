<?php

namespace App\Http\Requests\Admin;

use App\Rules\CmsDestination;

class UpdateSiteSettingRequest extends CmsFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    public function rules(): array
    {
        $shared = $this->translations(['callout_title', 'callout_content', 'callout_label', 'footer_description', 'footer_cta_label', 'footer_copyright'], false);
        foreach (['callout_title', 'callout_content', 'callout_label', 'footer_description', 'footer_cta_label', 'footer_copyright'] as $field) {
            foreach (['ca', 'es'] as $locale) {
                $shared[$field.'.'.$locale] = ['required_with:'.$field, 'string', 'max:20000'];
            }
        }

        return [
            ...$shared,
            'legal_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tax_identifier' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'privacy_contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'professional_registration' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'data_controller_name' => ['sometimes', 'nullable', 'string', 'max:255'],

            ...$this->imageRules('logo'),
            'callout_destination' => ['sometimes', 'nullable', 'string', 'max:2048', new CmsDestination],
            'footer_cta_destination' => ['sometimes', 'nullable', 'string', 'max:2048', new CmsDestination],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'array:ca,es'],
            'location.ca' => ['nullable', 'string', 'max:255'],
            'location.es' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:2048'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
