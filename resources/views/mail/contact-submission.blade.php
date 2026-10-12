<!doctype html>
<html lang="{{ $submissionLocale }}">
<head><meta charset="utf-8"><title>{{ __('contact.subject') }}</title></head>
<body>
    <h1>{{ __('contact.subject') }}</h1>
    <p><strong>{{ __('contact.name') }}:</strong> {{ $visitorName }}</p>
    <p><strong>{{ __('contact.email') }}:</strong> {{ $visitorEmail }}</p>
    <p><strong>{{ __('contact.services') }}:</strong></p>
    <ul>@foreach($selectedServices as $service)<li>{{ $service['label'] ?? $service['slug'] }} ({{ $service['slug'] }})</li>@endforeach</ul>
    <p><strong>{{ __('contact.time') }}:</strong> {{ $submittedAt->toIso8601String() }}</p>
    <p><strong>{{ __('contact.locale') }}:</strong> {{ $submissionLocale }}</p>
    <h2>{{ __('contact.message') }}</h2>
    <div style="white-space:pre-wrap">{{ $visitorMessage }}</div>
</body>
</html>
