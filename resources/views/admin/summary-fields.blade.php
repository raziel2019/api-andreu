<fieldset data-summary data-summary-index="{{ $index }}">
    <legend>Resum de servei</legend>
    <div class="summary-header">
        <div><label for="summary-{{ $index }}-slug">Identificador</label><input id="summary-{{ $index }}-slug" name="service_summaries[{{ $index }}][slug]" value="{{ $summary['slug'] ?? '' }}" pattern="[a-z0-9-]+" maxlength="100" required></div>
        <div><label for="summary-{{ $index }}-order">Ordre</label><input id="summary-{{ $index }}-order" type="number" name="service_summaries[{{ $index }}][sort_order]" min="0" max="10000" value="{{ $summary['sort_order'] ?? 0 }}" required></div>
        <button type="button" class="danger" data-remove-summary>Elimina el resum</button>
    </div>
    <x-admin.translated-field :name="'service_summaries['.$index.'][title]'" label="Títol" :value="$summary['title'] ?? []" />
    <x-admin.translated-field :name="'service_summaries['.$index.'][description]'" label="Descripció" :value="$summary['description'] ?? []" :multiline="true" />
</fieldset>
