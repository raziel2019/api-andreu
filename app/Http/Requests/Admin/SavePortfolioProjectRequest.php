<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SavePortfolioProjectRequest extends CmsFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->emptyCollections(['gallery']);
    }

    public function rules(): array
    {
        return [
            ...$this->translations(['title']),
            ...$this->translations(['description', 'featured_image_alt'], false),
            'body' => ['sometimes', 'nullable', 'array:ca,es'],
            'body.ca' => ['nullable', 'string', 'max:100000'],
            'body.es' => ['nullable', 'string', 'max:100000'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('portfolio_projects')->ignore($this->route('project'))],
            'portfolio_category_id' => ['nullable', 'integer', 'exists:portfolio_categories,id'],
            ...$this->imageRules('featured_image'),
            'year' => ['nullable', 'integer', 'min:1000', 'max:9999'],
            'client' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'gallery' => ['sometimes', 'array', 'max:50'],
            'gallery.*' => ['array:image,image_asset_id,remove_image,alt,caption,sort_order'],
            'gallery.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'gallery.*.image_asset_id' => ['nullable', 'required_without:gallery.*.image', 'integer', Rule::exists('media_assets', 'id')->where('disk', 'public')],
            'gallery.*.remove_image' => ['sometimes', 'boolean'],
            'gallery.*.alt' => ['nullable', 'array:ca,es'],
            'gallery.*.alt.ca' => ['nullable', 'string', 'max:2000'],
            'gallery.*.alt.es' => ['nullable', 'string', 'max:2000'],
            'gallery.*.caption' => ['nullable', 'array:ca,es'],
            'gallery.*.caption.ca' => ['nullable', 'string', 'max:2000'],
            'gallery.*.caption.es' => ['nullable', 'string', 'max:2000'],
            'gallery.*.sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
