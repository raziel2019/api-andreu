<fieldset data-row data-index="{{ $index }}">
    <legend>Imatge</legend>
    <div class="row-controls"><button type="button" data-up>Amunt</button><button type="button" data-down>Avall</button><button type="button" data-remove>Treu</button></div>
    <x-admin.media-picker :name="'gallery['.$index.'][image]'" label="Imatge de la galeria" :path="$item['path'] ?? null" :asset-id="$item['image_asset_id'] ?? $galleryAssets[$item['path'] ?? ''] ?? null" />
    <x-admin.translated-field :name="'gallery['.$index.'][alt]'" label="Text alternatiu" :value="$item['alt'] ?? []" :multiline="true" :required="false" />
    <x-admin.translated-field :name="'gallery['.$index.'][caption]'" label="Peu de foto" :value="$item['caption'] ?? []" :multiline="true" :required="false" />
    <x-admin.field :name="'gallery['.$index.'][sort_order]'" label="Ordre" type="number" :value="$item['sort_order'] ?? 0" min="0" max="10000" data-order :required="true" />
</fieldset>
