@props(['form' => null])
<button class="save-button" type="submit" @if($form) form="{{ $form }}" @else disabled title="Esta pantalla no tiene un formulario único para guardar" @endif><x-admin.icon name="save" />Guardar cambios</button>
