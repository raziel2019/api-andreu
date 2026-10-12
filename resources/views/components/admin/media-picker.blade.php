@props(['name', 'label', 'path' => null, 'assetId' => null])
@php
    $field = str_replace(['][', '[', ']'], ['.', '.', ''], $name);
    $assetName = str_ends_with($name, ']') ? substr($name, 0, -1).'_asset_id]' : $name.'_asset_id';
    $removeName = str_contains($name, '[') ? preg_replace('/\[([^\[]+)\]$/', '[remove_$1]', $name) : 'remove_'.$name;
    $assetField = $field.'_asset_id';
    $removeField = str_contains($field, '.') ? substr($field, 0, strrpos($field, '.') + 1).'remove_'.substr($field, strrpos($field, '.') + 1) : 'remove_'.$field;
    $inputId = 'media-'.str_replace(['[', ']'], ['-', ''], $name).'-'.\Illuminate\Support\Str::random(8);
@endphp
<fieldset data-media-picker data-current-url="{{ \App\Services\CmsMedia::publicUrl($path) }}" {{ $attributes }}>
    <legend>{{ $label }}</legend>
    <p><img data-media-preview class="image-preview" @if($path) src="{{ \App\Services\CmsMedia::publicUrl($path) }}" @endif alt="{{ $label }}" @if(!$path) hidden @endif></p>
    <label for="{{ $inputId }}-asset">Tria o substitueix amb un fitxer de la biblioteca</label>
    <select id="{{ $inputId }}-asset" name="{{ $assetName }}" data-media-select>
        <option value="">Conserva la selecció actual</option>
        @foreach($mediaAssets as $asset)
            <option value="{{ $asset->id }}" data-url="{{ $asset->url() }}" @selected((string) old($assetField, $assetId) === (string) $asset->id)>{{ $asset->original_filename }} · {{ $asset->width ?? '—' }} × {{ $asset->height ?? '—' }} · #{{ $asset->id }}</option>
        @endforeach
    </select>
    <x-admin.error :name="$assetField" />
    <details>
        <summary>Puja una imatge o icona nova</summary>
        <p><small>PNG, JPEG o WebP. Màxim 5 MB, 6000 × 6000 píxels i 16 megapíxels. SVG i AVIF no admesos. La nova imatge té prioritat sobre la selecció.</small></p>
        <label for="{{ $inputId }}-upload">Fitxer nou</label>
        <input id="{{ $inputId }}-upload" type="file" name="{{ $name }}" accept="image/png,image/jpeg,image/webp" data-media-upload>
    </details>
    <x-admin.error :name="$field" />
    <input type="hidden" name="{{ $removeName }}" value="0">
    <label><input type="checkbox" name="{{ $removeName }}" value="1" data-media-remove @checked(old($removeField))> Treu la selecció (conserva el fitxer a la biblioteca)</label>
    <x-admin.error :name="$removeField" />
    <p><small>Desa el formulari per publicar l’assignació. Substituir aquesta selecció no modifica altres usos del mateix fitxer.</small></p>
</fieldset>
@pushOnce('scripts')<script src="{{ asset('admin-media.js') }}" defer></script>@endPushOnce
