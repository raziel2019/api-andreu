<fieldset data-row data-index="{{ $index }}">
    <legend>Element</legend>
    <input type="hidden" name="{{ $name }}[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}">
    <x-admin.error :name="$name.'.'.$index.'.id'" />
    <div class="row-controls">
        <button type="button" data-up aria-label="Mou l’element amunt">Amunt</button>
        <button type="button" data-down aria-label="Mou l’element avall">Avall</button>
        <button type="button" class="danger" data-remove>Elimina l’element</button>
    </div>
    @if($name === 'features')
        <x-admin.media-picker :name="$name.'['.$index.'][icon]'" label="Icona de la característica" :path="$item['icon_path'] ?? null" />
    @endif
    <x-admin.translated-field :name="$name.'['.$index.'][content]'" label="Contingut" :value="$item['content'] ?? []" :multiline="true" :rich="true" />
    <x-admin.field :name="$name.'['.$index.'][sort_order]'" label="Ordre" type="number" :value="$item['sort_order'] ?? 0" min="0" max="10000" data-order :required="true" />
</fieldset>
