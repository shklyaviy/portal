@extends('layouts.app')

@section('content')
    @php
        $parsed = \App\Support\HtmlContent::splitMedia((string) $product->description_html);
        $images = $parsed['images'];
        if ($product->image_path) {
            $main = str_starts_with($product->image_path, 'http')
                ? $product->image_path
                : asset('storage/'.$product->image_path);
            array_unshift($images, $main);
            $images = array_values(array_unique($images));
        }
        $cover = $images[0] ?? null;
        $bodyHtml = $parsed['body'] !== '' ? $parsed['body'] : null;
    @endphp

    <div class="pf-cat-shell pf-reveal">
        @include('partials.catalog-nav', ['navForest' => $navForest ?? collect(), 'activeIds' => $activeIds ?? []])

        <div class="pf-cat-main">
            <article class="pf-detail-page pf-detail-in-shell">
                <div class="pf-crumbs pf-crumbs-dark">
                    <a href="{{ url('/') }}">Главная</a><span>/</span>
                    <a href="{{ url('/catalog.html') }}">Каталог</a>
                    @foreach(($breadcrumbs ?? collect()) as $crumb)
                        <span>/</span>
                        <a href="{{ url('/catalog/'.$crumb->slug.'.html') }}">{{ $crumb->name }}</a>
                    @endforeach
                    <span>/</span><span>{{ $product->name }}</span>
                </div>

                <div class="pf-detail-media">
                    <div class="pf-detail-cover {{ $cover ? '' : 'is-empty' }}">
                        @if($cover)
                            <img src="{{ $cover }}" alt="{{ $product->name }}" loading="eager" data-pf-cover>
                        @else
                            <div class="pf-detail-cover-fallback" aria-hidden="true">
                                {!! \App\Support\CatalogIcons::svg($product->category?->slug ?? 'box') !!}
                                <span>Фото появится после синхронизации</span>
                            </div>
                        @endif
                    </div>
                    @if(count($images) > 1)
                        <div class="pf-detail-thumbs" data-pf-thumbs>
                            @foreach($images as $i => $src)
                                <button
                                    type="button"
                                    class="pf-detail-thumb {{ $i === 0 ? 'is-active' : '' }}"
                                    data-pf-thumb="{{ $src }}"
                                    aria-label="Фото {{ $i + 1 }}"
                                >
                                    <img src="{{ $src }}" alt="{{ $product->name }} — фото {{ $i + 1 }}" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <header class="pf-detail-head">
                    <h1>{{ $h1 }}</h1>
                    @if($product->sku)
                        <p class="pf-detail-lead">Артикул: {{ $product->sku }}</p>
                    @endif
                </header>

                <div class="pf-detail-buy">
                    <div class="pf-detail-buy-meta">
                        @if($product->show_price && $product->price)
                            <p class="pf-price">
                                {{ number_format((float) $product->price, 0, '.', ' ') }} ₽
                                @if($product->unit)
                                    <span class="pf-muted" style="font-size:0.95rem; font-weight:600;"> / {{ $product->unit }}</span>
                                @endif
                            </p>
                        @else
                            <p class="pf-muted" style="margin:0;">Цена по запросу</p>
                        @endif

                        @if($product->show_availability)
                            <span class="pf-badge {{ $product->is_in_stock ? 'is-ok' : '' }}">
                                {{ $product->is_in_stock ? 'В наличии' : 'Под заказ' }}
                            </span>
                        @endif
                    </div>

                    <div class="pf-detail-buy-form">
                        <h2>Заявка на расчёт</h2>
                        <form class="pf-form" method="post" action="{{ route('leads.store') }}">
                            @csrf
                            <input type="hidden" name="source" value="product:{{ $product->slug }}">
                            <input type="text" name="name" placeholder="Имя" required value="{{ old('name') }}">
                            <input type="text" name="phone" placeholder="Телефон" required value="{{ old('phone') }}">
                            <textarea name="comment" rows="3" placeholder="Комментарий">{{ old('comment') }}</textarea>
                            <button class="pf-btn pf-btn-primary" type="submit" style="width:100%;">Отправить</button>
                        </form>
                    </div>
                </div>

                @if($bodyHtml)
                    <div class="pf-detail-block">
                        <h2>Описание</h2>
                        <div class="pf-content pf-detail-prose">{!! $bodyHtml !!}</div>
                    </div>
                @endif

                @if($product->attributeValues->isNotEmpty())
                    <div class="pf-detail-block">
                        <h2>Характеристики</h2>
                        <table class="pf-table">
                            @foreach($product->attributeValues as $value)
                                <tr>
                                    <th>{{ $value->attribute?->name }}</th>
                                    <td>{{ $value->value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif

                @if($product->prices->isNotEmpty())
                    <div class="pf-detail-block">
                        <h2>Цены</h2>
                        <table class="pf-table">
                            @foreach($product->prices as $price)
                                <tr>
                                    <th>{{ $price->priceType?->name ?? 'Цена' }}</th>
                                    <td>{{ number_format((float) $price->amount, 0, '.', ' ') }} ₽</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </article>
        </div>
    </div>

    @php
        $productUrl = url('/catalog/'.$product->slug.'.html');
        $imageUrl = $cover;
        $productLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->seo_description ?: strip_tags((string) $product->description_html),
            'url' => $productUrl,
        ];
        if ($imageUrl) { $productLd['image'] = $imageUrl; }
        if ($product->show_price && $product->price) {
            $productLd['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => $product->currency ?: 'RUB',
                'price' => (float) $product->price,
                'availability' => $product->is_in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $productUrl,
            ];
        }
        $crumbs = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Каталог', 'item' => url('/catalog.html')],
        ];
        $pos = 3;
        foreach (($breadcrumbs ?? collect()) as $crumb) {
            $crumbs[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $crumb->name, 'item' => url('/catalog/'.$crumb->slug.'.html')];
        }
        $crumbs[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => $product->name, 'item' => $productUrl];
        $breadcrumbLd = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs];
    @endphp
    <script type="application/ld+json">{!! json_encode($productLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endsection
