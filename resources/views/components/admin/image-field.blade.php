@props(['name', 'label', 'path' => null])
<fieldset>
    <legend>{{ $label }}</legend>
    @if($path)<p><img class="image-preview" src="{{ Storage::disk('public')->url($path) }}" alt="{{ $label }}"></p>@endif
    <p><small>JPG, PNG o WebP. Màxim 5 MB i 6000 × 6000 píxels. Sense una imatge nova es conserva l’actual.</small></p>
    <label for="{{ $name }}">Tria una imatge nova</label>
    <input type="file" id="{{ $name }}" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}">
    <x-admin.error :name="$name" />
    <input type="hidden" name="remove_{{ $name }}" value="0">
    <label><input type="checkbox" name="remove_{{ $name }}" value="1" @checked(old('remove_'.$name))> Elimina la imatge actual</label>
    <x-admin.error :name="'remove_'.$name" />
</fieldset>
