@extends('layouts.app')

@section('content')
    <section class="pf-page-hero pf-reveal">
        <div class="pf-crumbs"><a href="{{ url('/') }}">Главная</a><span>/</span><span>Объекты</span></div>
        <h1>Наши объекты</h1>
        <p>Реализованные кровли и фасады в Новороссийске, Геленджике, Абинске и по краю.</p>
    </section>

    <section class="pf-section pf-reveal">
        @if($items->isEmpty())
            <p class="pf-muted">Объекты скоро появятся.</p>
        @else
            <div class="pf-media-grid">
                @foreach($items as $item)
                    <a class="pf-media-card" href="{{ url('/projects/'.$item->slug.'.html') }}">
                        <span class="pf-media-thumb" @if($item->image_path) style="background-image:url('{{ $item->image_path }}')" @endif>
                            @unless($item->image_path)
                                <span class="pf-media-fallback" aria-hidden="true">{{ mb_strtoupper(mb_substr($item->title, 0, 1)) }}</span>
                            @endunless
                        </span>
                        <span class="pf-media-body">
                            <strong>{{ $item->title }}</strong>
                            <span class="pf-muted">Смотреть фото →</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        <div style="margin-top:1.75rem; display:flex; flex-wrap:wrap; gap:0.65rem;">
            <a class="pf-btn pf-btn-primary" href="{{ url('/contacts.html') }}#form">Заявка на объект</a>
            <a class="pf-btn pf-btn-ghost" href="{{ url('/catalog.html') }}">В каталог</a>
        </div>
    </section>
@endsection
