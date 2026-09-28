@extends('layouts.app')

@section('content')
    <section class="pf-page-hero pf-reveal">
        <div class="pf-crumbs"><a href="{{ url('/') }}">Главная</a><span>/</span><span>{{ $title }}</span></div>
        <h1>{{ $title }}</h1>
        @if(!empty($seoDescription))
            <p>{{ $seoDescription }}</p>
        @endif
    </section>

    <section class="pf-section pf-reveal">
        <div class="pf-content">{!! $bodyHtml !!}</div>
        <div style="margin-top:1.5rem; display:flex; flex-wrap:wrap; gap:0.65rem;">
            <a class="pf-btn pf-btn-primary" href="{{ url('/contacts.html') }}#form">Заявка</a>
            <a class="pf-btn pf-btn-ghost" href="{{ url('/catalog.html') }}">В каталог</a>
        </div>
    </section>
@endsection
