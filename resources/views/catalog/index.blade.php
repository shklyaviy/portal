@extends('layouts.app')

@section('content')
    <section class="pf-page-hero pf-reveal">
        <div class="pf-crumbs"><a href="{{ url('/') }}">Главная</a><span>/</span><span>Каталог</span></div>
        <h1>{{ $h1 ?? 'Каталог продукции' }}</h1>
        <p>Слева — поиск и дерево разделов. Справа — крупные категории с иконками.</p>
    </section>

    <div class="pf-cat-shell pf-reveal">
        @include('partials.catalog-nav', ['navForest' => $navForest, 'activeIds' => $activeIds ?? []])

        <div class="pf-cat-main">
            <div class="pf-cat-panel">
                <div class="pf-cat-panel-head">
                    <h2>Разделы</h2>
                    <span class="pf-muted">{{ $sections->count() }}</span>
                </div>

                <div class="pf-dept-grid">
                    @forelse($sections as $section)
                        <a class="pf-dept-card" href="{{ url('/catalog/'.$section->slug.'.html') }}">
                            @include('partials.catalog-icon', ['slug' => $section->slug])
                            <span class="pf-dept-body">
                                <strong>{{ $section->name }}</strong>
                            </span>
                            <span class="pf-dept-arrow" aria-hidden="true">→</span>
                        </a>
                    @empty
                        <p class="pf-muted">Каталог пока пуст.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
