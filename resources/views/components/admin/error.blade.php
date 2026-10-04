@props(['name'])
@error($name)<p class="field-error" id="error-{{ str_replace('.', '-', $name) }}" role="alert">{{ $message }}</p>@enderror
