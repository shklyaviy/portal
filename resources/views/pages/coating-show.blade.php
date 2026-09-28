@extends('layouts.app')

@section('content')
    @php
        $cover = $images[0] ?? null;
        $gallery = $images ?? [];
    @endphp

    <section class="pf-detail-page pf-reveal">
        <div class="pf-crumbs pf-crumbs-dark">
            <a href="{{ url('/') }}">Главная</a><span>/</span>
            <a href="{{ url('/pokrytiya/') }}">Покрытия</a><span>/</span>
            <span>{{ $item->name }}</span>
        </div>

        @if($cover)
            <div class="pf-detail-media">
                <div class="pf-detail-cover">
                    <img src="{{ $cover }}" alt="{{ $item->name }}" loading="eager">
                </div>
                @if(count($gallery) > 1)
                    <div class="pf-detail-thumbs" data-pf-thumbs>
                        @foreach($gallery as $i => $src)
                            <button
                                type="button"
                                class="pf-detail-thumb {{ $i === 0 ? 'is-active' : '' }}"
                                data-pf-thumb="{{ $src }}"
                                aria-label="Фото {{ $i + 1 }}"
                            >
                                <img src="{{ $src }}" alt="{{ $item->name }} — фото {{ $i + 1 }}" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <header class="pf-detail-head">
            <h1>{{ $item->name }}</h1>
            @if(!empty($lead))
                <p class="pf-detail-lead">{{ $lead }}</p>
            @elseif($item->seo_description)
                <p class="pf-detail-lead">{{ $item->seo_description }}</p>
            @endif
        </header>

        <div class="pf-detail-block">
            <h2>Описание покрытия</h2>
            <div class="pf-content pf-detail-prose">
                @if(!empty($bodyHtml))
                    {!! $bodyHtml !!}
                @else
                    <p>Описание скоро появится.</p>
                @endif
            </div>
        </div>

        <div class="pf-detail-actions">
            <a class="pf-btn pf-btn-primary" href="{{ url('/contacts.html') }}#form">Заявка на подбор</a>
            <a class="pf-btn pf-btn-ghost" href="{{ url('/pokrytiya/') }}">Все покрытия</a>
            <a class="pf-btn pf-btn-ghost" href="{{ url('/catalog.html') }}">В каталог</a>
        </div>
    </section>
@endsection
