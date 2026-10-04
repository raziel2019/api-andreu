@props(['name', 'label', 'value' => [], 'multiline' => false, 'required' => true])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $value = old($key, $value);
    $value = is_array($value) ? $value : [];
@endphp
<fieldset>
    <legend>{{ $label }}{{ $required ? '' : ' (opcional)' }}</legend>
    <x-admin.error :name="$key" />
    <div class="languages">
        @foreach(['ca' => 'Català', 'es' => 'Castellano'] as $locale => $language)
            @php($id = str_replace(['[', ']'], ['-', ''], $name).'-'.$locale)
            <div lang="{{ $locale }}">
                <label for="{{ $id }}">{{ $language }}</label>
                @if($multiline)
                    <textarea id="{{ $id }}" name="{{ $name }}[{{ $locale }}]" @required($required) aria-invalid="{{ $errors->has($key.'.'.$locale) ? 'true' : 'false' }}" @if($errors->has($key.'.'.$locale)) aria-describedby="error-{{ str_replace('.', '-', $key.'.'.$locale) }}" @endif>{{ is_string($value[$locale] ?? null) ? $value[$locale] : '' }}</textarea>
                @else
                    <input id="{{ $id }}" name="{{ $name }}[{{ $locale }}]" value="{{ is_string($value[$locale] ?? null) ? $value[$locale] : '' }}" @required($required) aria-invalid="{{ $errors->has($key.'.'.$locale) ? 'true' : 'false' }}" @if($errors->has($key.'.'.$locale)) aria-describedby="error-{{ str_replace('.', '-', $key.'.'.$locale) }}" @endif>
                @endif
                <x-admin.error :name="$key.'.'.$locale" />
            </div>
        @endforeach
    </div>
</fieldset>
