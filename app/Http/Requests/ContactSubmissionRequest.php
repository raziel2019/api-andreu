<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContactSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\x00-\x1F\x7F<>]/u'],
            'email' => ['required', 'string', 'max:254', 'email:rfc', 'not_regex:/[\x00-\x20\x7F]/u'],
            'services' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'services.*' => ['required', 'string', 'max:100', 'distinct:strict', Rule::exists('contact_service_options', 'slug')->where('contact_page_id', 1)->where('active', true)],
            'message' => ['required', 'string', 'max:10000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
            'locale' => ['sometimes', 'string', Rule::in(['ca', 'es'])],
            'token' => ['required', 'string', 'max:2048', 'not_regex:/[\x00-\x20\x7F]/u'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['name', 'email', 'services', 'message', 'locale', 'token']) !== []) {
                $validator->errors()->add('payload', __('contact.unexpected_fields'));
            }
        }];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'code' => 'validation_failed',
            'message' => __('contact.validation_failed'),
            'errors' => $validator->errors(),
        ], 422)->header('Content-Language', app()->getLocale()));
    }
}
