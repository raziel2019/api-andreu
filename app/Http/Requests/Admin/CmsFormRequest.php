<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class CmsFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    /** @param list<string> $fields */
    protected function translations(array $fields, bool $required = true): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $rules[$field] = [$required ? 'required' : 'sometimes', 'nullable', 'array:ca,es'];
            foreach (['ca', 'es'] as $locale) {
                $rules[$field.'.'.$locale] = [$required ? 'required' : 'nullable', 'string', 'max:20000'];
            }
        }

        return $rules;
    }

    protected function imageRules(string $field): array
    {
        return [
            $field => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'remove_'.$field => ['sometimes', 'boolean'],
            $field.'_asset_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')->where('disk', 'public')],
        ];
    }

    protected function repeated(string $field, string $table, string $foreignKey, int $parentId): array
    {
        return [
            $field => ['present', 'array', 'max:50'],
            $field.'.*' => ['array:id,content,sort_order'],
            $field.'.*.id' => ['nullable', 'integer', 'distinct', Rule::exists($table, 'id')->where($foreignKey, $parentId)],
            $field.'.*.content' => ['required', 'array:ca,es'],
            $field.'.*.content.ca' => ['required', 'string', 'max:20000'],
            $field.'.*.content.es' => ['required', 'string', 'max:20000'],
            $field.'.*.sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }

    /** @param list<string> $fields */
    protected function emptyCollections(array $fields): void
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $this->all()) && $this->input($field) === null) {
                $this->merge([$field => []]);
            }
        }
    }

    public function attributes(): array
    {
        $labels = __('admin.fields');
        $attributes = [];
        foreach (is_array($labels) ? $labels : [] as $field => $label) {
            $attributes[$field] = $label;
            $attributes[$field.'.ca'] = $label.' (Català)';
            $attributes[$field.'.es'] = $label.' (Castellano)';
        }
        foreach (['benefits', 'working_method_steps', 'features'] as $collection) {
            $attributes[$collection.'.*.content.ca'] = 'Contingut (Català)';
            $attributes[$collection.'.*.content.es'] = 'Contingut (Castellano)';
            $attributes[$collection.'.*.sort_order'] = 'Ordre';
            $attributes[$collection.'.*.id'] = 'Element';
        }

        return $attributes;
    }
}
