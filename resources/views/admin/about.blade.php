<x-admin.layout title="Sobre mi">
    <h1>Sobre mi</h1>
    <p>Completa el contingut original en català i la traducció al castellà.</p>
    <form method="POST" action="{{ route('admin.about.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <x-admin.translated-field name="title" :label="__('admin.fields.title')" :value="$page->title" />
        <x-admin.translated-field name="introduction" :label="__('admin.fields.introduction')" :value="$page->introduction" :multiline="true" />
        <x-admin.translated-field name="image_alt" :label="__('admin.fields.image_alt')" :value="$page->image_alt" />
        <x-admin.translated-field name="image_caption" :label="__('admin.fields.image_caption')" :value="$page->image_caption" />
        <x-admin.translated-field name="benefits_heading" :label="__('admin.fields.benefits_heading')" :value="$page->benefits_heading" />
        <x-admin.translated-field name="working_method_heading" :label="__('admin.fields.working_method_heading')" :value="$page->working_method_heading" />
        <x-admin.translated-field name="working_method_introduction" :label="__('admin.fields.working_method_introduction')" :value="$page->working_method_introduction" :multiline="true" />
        <x-admin.image-field name="image" label="Imatge principal" :path="$page->image_path" />
        <x-admin.repeatable name="benefits" label="Beneficis" :items="$page->benefits->toArray()" />
        <x-admin.repeatable name="working_method_steps" label="Passos del mètode de treball" :items="$page->workingMethodSteps->toArray()" />
        <p><button type="submit">Desa Sobre mi</button></p>
    </form>
</x-admin.layout>
