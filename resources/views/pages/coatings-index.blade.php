@extends('layouts.app')

@section('content')
    <section class="pf-page-hero pf-reveal">
        <div class="pf-crumbs"><a href="{{ url('/') }}">Главная</a><span>/</span><span>Покрытия</span></div>
        <h1>Покрытия</h1>
        <p>Полимерные покрытия для металлочерепицы и профнастила — от полиэстера до премиум-серий.</p>
    </section>

    <section class="pf-section pf-reveal">
        @if($items->isEmpty())
            <p class="pf-muted">Раздел покрытий скоро появится.</p>
        @else
            <div class="pf-media-grid">
                @foreach($items as $item)
                    @php
                        $cover = null;
                        if (is_string($item->description_html) && preg_match('/src=["\']([^"\']+)["\']/i', $item->description_html, $m)) {
                            $cover = $m[1];
                        }
                        $excerpt = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $item->description_html))));
                        $excerpt = $excerpt !== '' ? \Illuminate\Support\Str::limit($excerpt, 120) : 'Подробнее о покрытии';
                    @endphp
                    <a class="pf-media-card" href="{{ url('/pokrytiya/'.$item->slug.'.html') }}">
                        <span class="pf-media-thumb" @if($cover) style="background-image:url('{{ $cover }}')" @endif>
                            @unless($cover)
                                <span class="pf-media-fallback" aria-hidden="true">{{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}</span>
                            @endunless
                        </span>
                        <span class="pf-media-body">
                            <strong>{{ $item->name }}</strong>
                            <span class="pf-muted">{{ $excerpt }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        <div style="margin-top:1.75rem; display:flex; flex-wrap:wrap; gap:0.65rem;">
            <a class="pf-btn pf-btn-primary" href="{{ url('/catalog.html') }}">Каталог материалов</a>
            <a class="pf-btn pf-btn-ghost" href="{{ url('/contacts.html') }}#form">Консультация</a>
        </div>
    </section>
@endsection
