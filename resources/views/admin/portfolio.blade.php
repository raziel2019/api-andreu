<x-admin.layout save-form="cms-editor" title="Portafoli">
    <h1>Portafoli</h1>
    <p>Completa el contingut original en català i la traducció al castellà.</p>
    <p><a class="button" href="{{ route('admin.portfolio.projects.index') }}">Gestiona projectes</a> <a href="{{ route('admin.portfolio.categories.index') }}">Gestiona categories</a></p>
    <p>Desactiva «En construcció» per mostrar la galeria de projectes publicats. La visibilitat global es gestiona a Pàgines.</p>
    <form id="cms-editor" method="POST" action="{{ route('admin.portfolio.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <x-admin.translated-field name="title" :label="__('admin.fields.title')" :value="$page->title" />
        @foreach(['listing_title' => 'Títol de la galeria', 'eyebrow' => 'Text editorial superior', 'introduction' => 'Introducció', 'callout_title' => 'Títol de contacte', 'callout_content' => 'Text de contacte', 'related_title' => 'Títol dels projectes relacionats', 'related_eyebrow' => 'Text superior dels projectes relacionats', 'whatsapp_label' => 'Etiqueta de WhatsApp'] as $field => $label)
        <x-admin.translated-field :name="$field" :label="$label" :value="$page->$field" :multiline="in_array($field, ['introduction', 'callout_content'])" :rich="in_array($field, ['introduction', 'callout_content'])" :required="false" />
        @endforeach
        <x-admin.field name="whatsapp_url" label="URL de WhatsApp" :value="$page->whatsapp_url" type="url" :required="true" />
        <x-admin.translated-field name="construction_label" :label="__('admin.fields.construction_label')" :value="$page->construction_label" />
        <x-admin.translated-field name="message" :label="__('admin.fields.message')" :value="$page->message" :multiline="true" :rich="true" />
        <x-admin.translated-field name="cta_label" :label="__('admin.fields.cta_label')" :value="$page->cta_label" :required="false" />
        <x-admin.active-field name="under_construction" label="En construcció" :value="$page->under_construction" />
        <x-admin.field name="cta_destination" label="Destinació del botó (opcional)" :value="$page->cta_destination" />
        <x-admin.field name="linkedin_destination" label="URL de LinkedIn (opcional)" type="url" :value="$page->linkedin_destination" />
        <p><button type="submit">Desa Portafoli</button></p>
    </form>
</x-admin.layout>
