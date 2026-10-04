@props(['name', 'label', 'items' => []])
@php
    $rows = old($name, $items);
    $rows = is_array($rows) ? $rows : [];
@endphp
<section class="repeatable" data-repeatable aria-labelledby="{{ $name }}-heading">
    <h2 id="{{ $name }}-heading">{{ $label }}</h2>
    <p>Afegeix elements i mou-los amunt o avall. Elimina els que ja no necessitis.</p>
    <x-admin.error :name="$name" />
    <input type="hidden" name="{{ $name }}" value="">
    <div data-rows>
        @foreach($rows as $index => $item)
            @include('admin.content-item-fields', ['name' => $name, 'index' => $index, 'item' => is_array($item) ? $item : []])
        @endforeach
    </div>
    <button type="button" data-add>Afegeix un element</button>
    <template data-template>@include('admin.content-item-fields', ['name' => $name, 'index' => '__INDEX__', 'item' => []])</template>
</section>
@pushOnce('scripts')<script src="{{ asset('admin-repeatables.js') }}" defer></script>@endPushOnce
