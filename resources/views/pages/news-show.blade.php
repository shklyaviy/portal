@extends('layouts.app')

@section('content')
    <section class="pf-section">
        <p class="pf-muted" style="margin-top:0;"><a href="{{ url('/news.html') }}">Новости</a></p>
        <h1>{{ $title }}</h1>
        @if($item->published_at)
            <p class="pf-muted">{{ $item->published_at->format('d.m.Y') }}</p>
        @endif
        <div>{!! $bodyHtml !!}</div>
    </section>

    @php
        $articleLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $item->title,
            'description' => $item->seo_description ?: strip_tags((string) $item->body_html),
            'datePublished' => optional($item->published_at)->toIso8601String(),
            'dateModified' => optional($item->updated_at)->toIso8601String(),
            'mainEntityOfPage' => url()->current(),
            'author' => [
                '@type' => 'Organization',
                'name' => 'Кровельный центр «Портал»',
            ],
        ];
        if ($item->image_path) {
            $articleLd['image'] = str_starts_with($item->image_path, 'http')
                ? $item->image_path
                : asset('storage/'.$item->image_path);
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($articleLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endsection
