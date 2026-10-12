@props(['value', 'label', 'icon'])
<section class="stat-card"><div><p class="stat-value">{{ str_pad((string) $value, 2, '0', STR_PAD_LEFT) }}</p><p class="muted">{{ $label }}</p></div><x-admin.icon :name="$icon" /></section>
