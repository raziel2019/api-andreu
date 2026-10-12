<fieldset data-summary data-summary-index="{{ $index }}">
    <legend>Resum de servei</legend>
    <div class="summary-header">
        <div><label for="summary-{{ $index }}-slug">Identificador</label><input id="summary-{{ $index }}-slug" name="service_summaries[{{ $index }}][slug]" value="{{ $summary['slug'] ?? '' }}" pattern="[a-z0-9-]+" maxlength="100" required></div>
        <div><label for="summary-{{ $index }}-order">Ordre</label><input id="summary-{{ $index }}-order" type="number" name="service_summaries[{{ $index }}][sort_order]" min="0" max="10000" value="{{ $summary['sort_order'] ?? 0 }}" required></div>
        <button type="button" class="danger" data-remove-summary>Elimina el resum</button>
    </div>
    @php($selectedIcon = $summary['icon'] ?? (isset($summary['slug']) ? '' : 'archive'))
    <div>
        <label for="summary-{{ $index }}-icon">Icona</label>
        <div aria-label="Previsualització de la icona">
            @foreach($summaryIcons as $icon => $option)
                <img data-summary-icon-preview="{{ $icon }}" src="{{ $option['preview_url'] }}" alt="{{ $option['label'] }}" width="56" height="56" @if($selectedIcon !== $icon) hidden @endif>
            @endforeach
            @if(!empty($summary['icon_path']))
                <img data-summary-icon-preview="" src="{{ Storage::disk('public')->url($summary['icon_path']) }}" alt="Icona anterior" width="56" height="56" @if($selectedIcon !== '') hidden @endif>
            @endif
        </div>
        <select id="summary-{{ $index }}-icon" name="service_summaries[{{ $index }}][icon]" data-summary-icon>
            <option value="" @selected($selectedIcon === '')>Conserva la icona anterior (o sense icona)</option>
            @foreach($summaryIcons as $icon => $option)
                <option value="{{ $icon }}" @selected($selectedIcon === $icon)>{{ $option['label'] }} ({{ $icon }})</option>
            @endforeach
        </select>
        <x-admin.error :name="'service_summaries.'.$index.'.icon'" />
    </div>
    <x-admin.media-picker :name="'service_summaries['.$index.'][custom_icon]'" label="Imatge o icona personalitzada" :path="$summary['icon_path'] ?? null" />
    <x-admin.translated-field :name="'service_summaries['.$index.'][title]'" label="Títol" :value="$summary['title'] ?? []" />
    <x-admin.translated-field :name="'service_summaries['.$index.'][description]'" label="Descripció" :value="$summary['description'] ?? []" :multiline="true" :rich="true" />
</fieldset>
