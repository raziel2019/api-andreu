<x-admin.layout title="Presentació de Serveis">
    <h1>Presentació de Serveis</h1>
    <p>Completa el contingut original en català i la traducció al castellà.</p>
    <p><a href="{{ route('admin.services.index') }}">Torna als serveis</a></p>
    <form method="POST" action="{{ route('admin.services.page.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <x-admin.translated-field name="title" :label="__('admin.fields.title')" :value="$page->title" />
        <x-admin.translated-field name="subtitle" :label="__('admin.fields.subtitle')" :value="$page->subtitle" />
        <x-admin.translated-field name="lead" :label="__('admin.fields.lead')" :value="$page->lead" />
        <x-admin.translated-field name="description" :label="__('admin.fields.description')" :value="$page->description" :multiline="true" />
        <x-admin.translated-field name="heading" :label="__('admin.fields.heading')" :value="$page->heading" />
        <x-admin.translated-field name="image_alt" :label="__('admin.fields.image_alt')" :value="$page->image_alt" />
        <x-admin.translated-field name="cta_label" :label="__('admin.fields.cta_label')" :value="$page->cta_label" />
        <x-admin.image-field name="image" label="Imatge principal" :path="$page->image_path" />
        <x-admin.field name="cta_destination" label="Destinació del botó" :value="$page->cta_destination" :required="true" />
        <p><button type="submit">Desa Presentació de Serveis</button></p>
    </form>
</x-admin.layout>
