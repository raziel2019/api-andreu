@props(['name' => 'active', 'value' => true, 'label' => 'Visible al web'])
<input type="hidden" name="{{ $name }}" value="0">
<label><input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $value))> {{ $label }}</label>
<x-admin.error :name="$name" />
