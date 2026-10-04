<?php

namespace App\Http\Requests\Admin;

class ConfirmDeleteRequest extends CmsFormRequest
{
    public function rules(): array
    {
        return ['confirm_delete' => ['required', 'accepted']];
    }

    public function messages(): array
    {
        return ['confirm_delete.required' => 'Confirma que vols eliminar aquest element.', 'confirm_delete.accepted' => 'Confirma que vols eliminar aquest element.'];
    }
}
