<x-admin.layout title="Previsualització del projecte" :content-lang="$locale">
    <p role="status">Previsualització privada del contingut desat · {{ $project->status === 'published' ? 'Publicat' : 'Esborrany' }}</p>
    <p><a href="{{ route('admin.portfolio.projects.edit', $project) }}">Torna a editar</a> · <a href="{{ route('admin.portfolio.projects.preview', ['project' => $project, 'locale' => $locale === 'ca' ? 'es' : 'ca']) }}">{{ $locale === 'ca' ? 'Castellano' : 'Català' }}</a></p>
    <article>
        <h1>{{ $content['title'] }}</h1>
        @if($project->category)<p>{{ $project->category->translated('title', $locale) }}</p>@endif
        @if($content['featured_image_url'])<img src="{{ $content['featured_image_url'] }}" alt="{{ $content['featured_image_alt'] ?? '' }}" style="max-width:100%;height:auto">@endif
        <div>{!! $content['description_html'] !!}</div>
        <div>{!! $content['body'] !!}</div>
        @foreach($content['gallery'] as $image)
            <figure>
                @if($image['url'])<img src="{{ $image['url'] }}" alt="{{ $image['alt'] ?? '' }}" style="max-width:100%;height:auto" loading="lazy">@endif
                @if($image['caption'] !== null && $image['caption'] !== '')<figcaption>{{ $image['caption'] }}</figcaption>@endif
            </figure>
        @endforeach
    </article>
</x-admin.layout>
