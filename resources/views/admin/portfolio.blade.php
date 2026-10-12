<x-admin.layout save-form="cms-editor" title="Portafoli">
    <h1>Portafoli</h1>
    <p>Completa el contingut original en català i la traducció al castellà.</p>
    <p class="notice">El portafoli és una pàgina de presentació en construcció. La gestió de projectes encara no forma part del CMS.</p>
    <form id="cms-editor" method="POST" action="{{ route('admin.portfolio.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <x-admin.translated-field name="title" :label="__('admin.fields.title')" :value="$page->title" />
        <x-admin.translated-field name="construction_label" :label="__('admin.fields.construction_label')" :value="$page->construction_label" />
        <x-admin.translated-field name="message" :label="__('admin.fields.message')" :value="$page->message" :multiline="true" />
        <x-admin.translated-field name="cta_label" :label="__('admin.fields.cta_label')" :value="$page->cta_label" :required="false" />
        <x-admin.active-field name="under_construction" label="En construcció" :value="$page->under_construction" />
        <x-admin.field name="cta_destination" label="Destinació del botó (opcional)" :value="$page->cta_destination" />
        <x-admin.field name="linkedin_destination" label="URL de LinkedIn (opcional)" type="url" :value="$page->linkedin_destination" />
        <p><button type="submit">Desa Portafoli</button></p>
    </form>
</x-admin.layout>
