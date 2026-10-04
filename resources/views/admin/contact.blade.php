<x-admin.layout title="Contacte">
    <h1>Contacte</h1>
    <p>Completa el contingut original en català i la traducció al castellà.</p>
    <form method="POST" action="{{ route('admin.contact.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <x-admin.translated-field name="title" :label="__('admin.fields.title')" :value="$page->title" />
        <x-admin.translated-field name="introduction" :label="__('admin.fields.introduction')" :value="$page->introduction" :multiline="true" />
        <x-admin.translated-field name="content" :label="__('admin.fields.content')" :value="$page->content" :multiline="true" />
        <x-admin.translated-field name="conversation" :label="__('admin.fields.conversation')" :value="$page->conversation" :multiline="true" />
        <x-admin.translated-field name="service_legend" :label="__('admin.fields.service_legend')" :value="$page->service_legend" />
        <p><button type="submit">Desa Contacte</button></p>
    </form>
    @include('admin.contact-options', ['options' => $page->serviceOptions])
</x-admin.layout>
