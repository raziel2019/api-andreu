<?php

namespace App\Http\Requests\Admin;

use App\Models\LegalPage;
use App\Services\PageVisibility;

class UpdatePageVisibilityRequest extends CmsFormRequest
{
    protected function prepareForValidation(): void
    {
        $page = (new PageVisibility)->page($this->route('page'));
        if ($page instanceof LegalPage && $this->boolean('active')) {
            $this->merge(['title' => $page->title, 'body' => $page->body]);
        }
    }

    public function rules(): array
    {
        $page = (new PageVisibility)->page($this->route('page'));
        if ($page instanceof LegalPage && $this->boolean('active')) {
            $request = new UpdateLegalPageRequest;
            $request->merge(['active' => true]);

            return $request->rules();
        }

        return ['active' => ['required', 'boolean']];
    }
}
