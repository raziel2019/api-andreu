<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHomePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (array_key_exists('service_summaries', $this->all()) && $this->input('service_summaries') === null) {
            $this->merge(['service_summaries' => []]);
        }
    }

    public function rules(): array
    {
        return [
            'hero_title' => ['required', 'array:ca,es'],
            'hero_title.ca' => ['required', 'string', 'max:20000'],
            'hero_title.es' => ['required', 'string', 'max:20000'],
            'hero_content' => ['required', 'array:ca,es'],
            'hero_content.ca' => ['required', 'string', 'max:20000'],
            'hero_content.es' => ['required', 'string', 'max:20000'],
            'hero_eyebrow' => ['required', 'array:ca,es'],
            'hero_eyebrow.ca' => ['required', 'string', 'max:20000'],
            'hero_eyebrow.es' => ['required', 'string', 'max:20000'],
            'hero_image_alt' => ['required', 'array:ca,es'],
            'hero_image_alt.ca' => ['required', 'string', 'max:20000'],
            'hero_image_alt.es' => ['required', 'string', 'max:20000'],
            'hero_image_caption' => ['required', 'array:ca,es'],
            'hero_image_caption.ca' => ['required', 'string', 'max:20000'],
            'hero_image_caption.es' => ['required', 'string', 'max:20000'],
            'hero_cta_label' => ['required', 'array:ca,es'],
            'hero_cta_label.ca' => ['required', 'string', 'max:20000'],
            'hero_cta_label.es' => ['required', 'string', 'max:20000'],
            'services_heading' => ['required', 'array:ca,es'],
            'services_heading.ca' => ['required', 'string', 'max:20000'],
            'services_heading.es' => ['required', 'string', 'max:20000'],
            'services_introduction' => ['required', 'array:ca,es'],
            'services_introduction.ca' => ['required', 'string', 'max:20000'],
            'services_introduction.es' => ['required', 'string', 'max:20000'],
            'stories_heading' => ['required', 'array:ca,es'],
            'stories_heading.ca' => ['required', 'string', 'max:20000'],
            'stories_heading.es' => ['required', 'string', 'max:20000'],
            'stories_introduction' => ['required', 'array:ca,es'],
            'stories_introduction.ca' => ['required', 'string', 'max:20000'],
            'stories_introduction.es' => ['required', 'string', 'max:20000'],
            'portfolio_title' => ['required', 'array:ca,es'],
            'portfolio_title.ca' => ['required', 'string', 'max:20000'],
            'portfolio_title.es' => ['required', 'string', 'max:20000'],
            'portfolio_image_alt' => ['required', 'array:ca,es'],
            'portfolio_image_alt.ca' => ['required', 'string', 'max:20000'],
            'portfolio_image_alt.es' => ['required', 'string', 'max:20000'],
            'portfolio_cta_label' => ['required', 'array:ca,es'],
            'portfolio_cta_label.ca' => ['required', 'string', 'max:20000'],
            'portfolio_cta_label.es' => ['required', 'string', 'max:20000'],
            'cta_title' => ['required', 'array:ca,es'],
            'cta_title.ca' => ['required', 'string', 'max:20000'],
            'cta_title.es' => ['required', 'string', 'max:20000'],
            'cta_content' => ['required', 'array:ca,es'],
            'cta_content.ca' => ['required', 'string', 'max:20000'],
            'cta_content.es' => ['required', 'string', 'max:20000'],
            'cta_label' => ['required', 'array:ca,es'],
            'cta_label.ca' => ['required', 'string', 'max:20000'],
            'cta_label.es' => ['required', 'string', 'max:20000'],
            'hero_cta_destination' => ['required', 'string', 'max:2048', $this->destinationRule()],
            'portfolio_cta_destination' => ['required', 'string', 'max:2048', $this->destinationRule()],
            'cta_destination' => ['required', 'string', 'max:2048', $this->destinationRule()],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'portfolio_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'remove_hero_image' => ['sometimes', 'boolean'],
            'remove_portfolio_image' => ['sometimes', 'boolean'],
            'service_summaries' => ['present', 'array', 'max:12'],
            'service_summaries.*' => ['array:slug,title,description,sort_order'],
            'service_summaries.*.slug' => ['required', 'string', 'regex:/^[a-z0-9-]+$/', 'max:100', 'distinct'],
            'service_summaries.*.title' => ['required', 'array:ca,es'],
            'service_summaries.*.title.ca' => ['required', 'string', 'max:500'],
            'service_summaries.*.title.es' => ['required', 'string', 'max:500'],
            'service_summaries.*.description' => ['required', 'array:ca,es'],
            'service_summaries.*.description.ca' => ['required', 'string', 'max:2000'],
            'service_summaries.*.description.es' => ['required', 'string', 'max:2000'],
            'service_summaries.*.sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }

    private function destinationRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                return;
            }
            $internal = preg_match('~^/(?!/)[a-zA-Z0-9/_?&=.%#-]*$~D', $value) === 1;
            $external = filter_var($value, FILTER_VALIDATE_URL) && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
            if (! $internal && ! $external) {
                $fail('Indica una ruta interna o un URL HTTP/HTTPS vàlid.');
            }
        };
    }
}
