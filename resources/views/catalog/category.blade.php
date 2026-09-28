@extends('layouts.app')

@section('content')
    @php
        $parsed = \App\Support\HtmlContent::splitMedia((string) $category->description_html);
        $images = $parsed['images'];
        $swatches = $parsed['swatches'];
        $cover = $images[0] ?? null;
        $bodyHtml = $parsed['body'];
        $lead = $parsed['lead'] ?: ($seoDescription ?? null);
        $hasChildren = $category->children->isNotEmpty();
        $hasProducts = $category->products->isNotEmpty();
    @endphp

    <div class="pf-cat-shell pf-reveal">
        @include('partials.catalog-nav', ['navForest' => $navForest, 'activeIds' => $activeIds])

        <div class="pf-cat-main">
            <article class="pf-detail-page pf-detail-in-shell">
                <div class="pf-crumbs pf-crumbs-dark">
                    <a href="{{ url('/') }}">Главная</a><span>/</span>
                    <a href="{{ url('/catalog.html') }}">Каталог</a>
                    @foreach($breadcrumbs as $crumb)
                        <span>/</span>
                        <a href="{{ url('/catalog/'.$crumb->slug.'.html') }}">{{ $crumb->name }}</a>
                    @endforeach
                    <span>/</span><span>{{ $category->name }}</span>
                </div>

                @if($cover)
                    <div class="pf-detail-media">
                        <div class="pf-detail-cover">
                            <img src="{{ $cover }}" alt="{{ $category->name }}" loading="eager" data-pf-cover>
                        </div>
                        @if(count($images) > 1)
                            <div class="pf-detail-thumbs" data-pf-thumbs>
                                @foreach($images as $i => $src)
                                    @php $label = $swatches[$i]['name'] ?? ('Фото '.($i + 1)); @endphp
                                    <button
                                        type="button"
                                        class="pf-detail-thumb {{ $i === 0 ? 'is-active' : '' }}"
                                        data-pf-thumb="{{ $src }}"
                                        aria-label="{{ $label }}"
                                        title="{{ $label }}"
                                    >
                                        <img src="{{ $src }}" alt="{{ $label }}" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <header class="pf-detail-head">
                    <h1>{{ $h1 }}</h1>
                    @if($lead)
                        <p class="pf-detail-lead">{{ $lead }}</p>
                    @endif
                </header>

                {{-- Сначала подразделы/товары, длинный текст — ниже --}}
                @if($hasChildren)
                    <div class="pf-detail-block" style="border-top:0; padding-top:0;">
                        <h2>Подразделы</h2>
                        <div class="pf-dept-grid pf-dept-grid-compact">
                            @foreach($category->children as $child)
                                <a class="pf-dept-card" href="{{ url('/catalog/'.$child->slug.'.html') }}">
                                    @include('partials.catalog-icon', ['slug' => $child->slug])
                                    <span class="pf-dept-body">
                                        <strong>{{ $child->name }}</strong>
                                    </span>
                                    <span class="pf-dept-arrow" aria-hidden="true">→</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($hasProducts)
                    <div class="pf-detail-block" @unless($hasChildren) style="border-top:0; padding-top:0;" @endunless>
                        <h2>Товары</h2>
                        <div class="pf-product-list">
                            @foreach($category->products as $product)
                                <a class="pf-product-row" href="{{ url('/catalog/'.$product->slug.'.html') }}">
                                    <span class="pf-product-row-main">
                                        <strong>{{ $product->name }}</strong>
                                        @if($product->sku)
                                            <span class="pf-muted">Арт. {{ $product->sku }}</span>
                                        @endif
                                    </span>
                                    <span class="pf-product-row-meta">
                                        @if($category->show_availability && $product->show_availability)
                                            <span class="pf-badge {{ $product->is_in_stock ? 'is-ok' : '' }}">
                                                {{ $product->is_in_stock ? 'В наличии' : 'Под заказ' }}
                                            </span>
                                        @endif
                                        @if($category->show_prices && $product->show_price && $product->price)
                                            <span class="pf-price">{{ number_format((float) $product->price, 0, '.', ' ') }} ₽</span>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif(! $hasChildren && ! $bodyHtml && ! $cover)
                    <div class="pf-detail-block" style="border-top:0; padding-top:0;">
                        <p class="pf-muted" style="margin:0;">В этом разделе пока нет товаров.</p>
                    </div>
                @endif

                @if(!empty($swatches))
                    <div class="pf-detail-block">
                        <h2>Цвета</h2>
                        <div class="pf-swatch-grid">
                            @foreach($swatches as $swatch)
                                <button
                                    type="button"
                                    class="pf-swatch"
                                    data-pf-thumb="{{ $swatch['src'] }}"
                                    data-pf-swatch
                                >
                                    <img src="{{ $swatch['src'] }}" alt="{{ $swatch['name'] }}" loading="lazy">
                                    <span>{{ $swatch['name'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($bodyHtml)
                    <div class="pf-detail-block">
                        <h2>Описание</h2>
                        <div class="pf-content pf-detail-prose">{!! $bodyHtml !!}</div>
                    </div>
                @endif

                <div class="pf-detail-actions">
                    <a class="pf-btn pf-btn-primary" href="{{ url('/contacts.html') }}#form">Заявка на расчёт</a>
                    <a class="pf-btn pf-btn-ghost" href="{{ url('/catalog.html') }}">Весь каталог</a>
                </div>
            </article>
        </div>
    </div>

    @php
        $categoryUrl = url('/catalog/'.$category->slug.'.html');
        $crumbs = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Каталог', 'item' => url('/catalog.html')],
        ];
        $pos = 3;
        foreach ($breadcrumbs as $crumb) {
            $crumbs[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $crumb->name, 'item' => url('/catalog/'.$crumb->slug.'.html')];
        }
        $crumbs[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => $category->name, 'item' => $categoryUrl];
        $breadcrumbLd = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs];
        $collectionLd = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'description' => $category->seo_description ?: strip_tags((string) $category->description_html),
            'url' => $categoryUrl,
        ];
        if ($cover) {
            $collectionLd['image'] = url($cover);
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($collectionLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endsection
