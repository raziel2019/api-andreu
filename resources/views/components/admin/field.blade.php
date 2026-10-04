@props(['name', 'label', 'value' => null, 'type' => 'text', 'required' => false])
@php($key = str_replace(['[', ']'], ['.', ''], $name))
@php($id = str_replace(['[', ']'], ['-', ''], $name))
<label for="{{ $id }}">{{ $label }}</label>
<input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ is_scalar(old($key, $value)) ? old($key, $value) : '' }}" @required($required) {{ $attributes }} aria-invalid="{{ $errors->has($key) ? 'true' : 'false' }}" @if($errors->has($key)) aria-describedby="error-{{ str_replace('.', '-', $key) }}" @endif>
<x-admin.error :name="$key" />
