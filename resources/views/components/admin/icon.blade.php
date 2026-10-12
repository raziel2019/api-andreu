@props(['name'])
<span {{ $attributes->class(['admin-icon']) }} aria-hidden="true"><img src="{{ asset('admin-icons/'.$name.'.svg') }}" alt=""></span>
