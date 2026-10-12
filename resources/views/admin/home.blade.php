<x-admin.layout save-form="cms-editor" title="Inicio">
    <h1>Inicio</h1>
    <p>El català és el contingut original. Completa també la traducció al castellà.</p>
    <form id="cms-editor" method="POST" action="{{ route('admin.home.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <x-admin.translated-field name="hero_title" label="Títol principal" :value="old('hero_title', $home->hero_title)" />
        <x-admin.translated-field name="hero_content" label="Presentació (separa els paràgrafs amb una línia en blanc)" :value="old('hero_content', $home->hero_content)" :multiline="true" />
        <x-admin.translated-field name="hero_eyebrow" label="Text sobre el títol" :value="old('hero_eyebrow', $home->hero_eyebrow)" />
        <x-admin.translated-field name="hero_image_alt" label="Descripció de la imatge principal" :value="old('hero_image_alt', $home->hero_image_alt)" />
        <x-admin.translated-field name="hero_image_caption" label="Peu de la imatge principal" :value="old('hero_image_caption', $home->hero_image_caption)" />
        <x-admin.translated-field name="hero_cta_label" label="Enllaç de presentació" :value="old('hero_cta_label', $home->hero_cta_label)" />
        <x-admin.translated-field name="services_heading" label="Títol dels resums de serveis" :value="old('services_heading', $home->services_heading)" />
        <x-admin.translated-field name="services_introduction" label="Introducció als serveis" :value="old('services_introduction', $home->services_introduction)" />
        <x-admin.translated-field name="stories_heading" label="Títol de la secció de portafoli" :value="old('stories_heading', $home->stories_heading)" />
        <x-admin.translated-field name="stories_introduction" label="Introducció al portafoli" :value="old('stories_introduction', $home->stories_introduction)" />
        <x-admin.translated-field name="portfolio_title" label="Títol de la targeta de portafoli" :value="old('portfolio_title', $home->portfolio_title)" />
        <x-admin.translated-field name="portfolio_image_alt" label="Descripció de la imatge de portafoli" :value="old('portfolio_image_alt', $home->portfolio_image_alt)" />
        <x-admin.translated-field name="portfolio_cta_label" label="Enllaç al portafoli" :value="old('portfolio_cta_label', $home->portfolio_cta_label)" />
        <x-admin.translated-field name="cta_title" label="Títol de la crida a l’acció" :value="old('cta_title', $home->cta_title)" />
        <x-admin.translated-field name="cta_content" label="Contingut de la crida a l’acció" :value="old('cta_content', $home->cta_content)" :multiline="true" />
        <x-admin.translated-field name="cta_label" label="Text del botó de contacte" :value="old('cta_label', $home->cta_label)" />
        <fieldset><legend>Destinacions dels enllaços</legend>
            @foreach(['hero_cta_destination' => 'Presentació', 'portfolio_cta_destination' => 'Portafoli', 'cta_destination' => 'Contacte'] as $field => $label)
                <label for="{{ $field }}">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $home->$field) }}" required>
                <x-admin.error :name="$field" />
            @endforeach
            <p><small>Una ruta com /contacto o un URL HTTP/HTTPS.</small></p>
        </fieldset>
        <fieldset><legend>Imatges</legend>
            <p><small>JPG, PNG o WebP. Màxim 5 MB per imatge i 6000 × 6000 píxels.</small></p>
            @foreach(['hero' => 'Imatge principal', 'portfolio' => 'Imatge de portafoli'] as $image => $label)
                @php($path = $home->getAttribute($image.'_image_path'))
                <label for="{{ $image }}_image">{{ $label }}</label>
                @if($path)<p><img class="image-preview" src="{{ Storage::disk('public')->url($path) }}" alt="{{ $label }}"></p>@endif
                <input id="{{ $image }}_image" type="file" name="{{ $image }}_image" accept="image/jpeg,image/png,image/webp">
                <x-admin.error :name="$image.'_image'" />
                <label><input type="checkbox" name="remove_{{ $image }}_image" value="1" @checked(old('remove_'.$image.'_image'))> Elimina la imatge actual</label>
                <x-admin.error :name="'remove_'.$image.'_image'" />
            @endforeach
        </fieldset>
        <section aria-labelledby="summaries-title">
            <h2 id="summaries-title">Resums de serveis</h2>
            <p>Un número més petit apareix abans.</p>
            <input type="hidden" name="service_summaries" value="">
            <x-admin.error name="service_summaries" />
            <div id="summaries">
                @foreach(old('service_summaries', $home->serviceSummaries->toArray()) ?? [] as $index => $summary)
                    @include('admin.summary-fields', ['index' => $index, 'summary' => $summary])
                @endforeach
            </div>
            <button type="button" id="add-summary">Afegeix un resum</button>
        </section>
        <p><button type="submit">Desa l’inici</button></p>
    </form>
    <template id="summary-template">
        @include('admin.summary-fields', ['index' => '__INDEX__', 'summary' => []])
    </template>
    <script>
        const summaries = document.getElementById('summaries');
        let nextIndex = Math.max(-1, ...Array.from(summaries.children, item => Number(item.dataset.summaryIndex))) + 1;
        document.getElementById('add-summary').addEventListener('click', () => {
            if (summaries.children.length >= 12) return;
            const markup = document.getElementById('summary-template').innerHTML.replaceAll('__INDEX__', String(nextIndex++));
            summaries.insertAdjacentHTML('beforeend', markup);
        });
        summaries.addEventListener('click', (event) => {
            if (event.target.matches('[data-remove-summary]')) event.target.closest('[data-summary]').remove();
        });
        summaries.addEventListener('change', (event) => {
            if (!event.target.matches('[data-summary-icon]')) return;
            event.target.closest('[data-summary]').querySelectorAll('[data-summary-icon-preview]').forEach(preview => {
                preview.hidden = preview.dataset.summaryIconPreview !== event.target.value;
            });
        });
    </script>
</x-admin.layout>
