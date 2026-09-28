@extends('layouts.app')

@section('content')
    <section class="pf-page-hero pf-page-hero-compact pf-reveal">
        <div class="pf-crumbs">
            <a href="{{ url('/') }}">Главная</a><span>/</span>
            <a href="{{ url('/projects.html') }}">Объекты</a><span>/</span>
            <span>{{ $item->title }}</span>
        </div>
        <h1>{{ $item->title }}</h1>
        @if($item->seo_description)
            <p>{{ $item->seo_description }}</p>
        @endif
    </section>

    <section class="pf-section pf-reveal">
        <article class="pf-detail">
            @if($item->image_path)
                <div class="pf-detail-cover">
                    <img src="{{ $item->image_path }}" alt="{{ $item->title }}" loading="eager">
                </div>
            @endif

            <div class="pf-detail-block" style="border-top:0; padding-top:0;">
                <div class="pf-content pf-detail-prose">{!! $item->description_html !!}</div>
            </div>

            <div class="pf-detail-actions">
                <a class="pf-btn pf-btn-primary" href="{{ url('/contacts.html') }}#form">Заявка на замер</a>
                <a class="pf-btn pf-btn-ghost" href="{{ url('/projects.html') }}">Все объекты</a>
            </div>
        </article>
    </section>
@endsection
