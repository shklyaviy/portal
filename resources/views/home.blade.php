@extends('layouts.app')

@section('content')
    @php
        $heroBg = asset('templates/portalfirma_2.0/images/background/sliderA/clouds.jpg');
        $promos = [
            asset('images/sliderAkcii/2025-1.jpg'),
            asset('images/sliderAkcii/2025-2.jpg'),
            asset('images/sliderAkcii/2025-3.jpg'),
            asset('images/sliderAkcii/2025-4.jpg'),
        ];
        $statsBg = $heroBg;
    @endphp

    <section
        class="pf-hero"
        style="background-image: url('{{ $heroBg }}');"
    >
        <div class="pf-hero-grid"></div>
        <div class="pf-hero-orb pf-hero-orb-a"></div>
        <div class="pf-hero-orb pf-hero-orb-b"></div>

        <div class="pf-hero-inner">
            <div class="pf-hero-copy">
                <p class="pf-kicker">Кровельный центр с 1993 года</p>
                <h1>Кровельный центр<br>в Новороссийске</h1>
                <p class="pf-hero-lead">
                    Кровля, фасады, заборы и комплектация напрямую от производителя.
                    Подбор, замер, расчёт и доставка по Краснодарскому краю.
                </p>
                <p class="pf-hero-note">Ответ в течение рабочего дня · без лишней бюрократии</p>
                <div class="pf-hero-actions">
                    <a class="pf-btn pf-btn-primary" href="{{ url('/contacts.html') }}#form">Заявка на замер и расчёт</a>
                    <a class="pf-btn pf-btn-secondary" href="{{ url('/catalog.html') }}">Перейти в каталог</a>
                </div>
            </div>

            <div class="pf-metrics">
                <article class="pf-metric"><strong>30+</strong><span>лет на рынке</span></article>
                <article class="pf-metric"><strong>14 000+</strong><span>реализованных кровель</span></article>
                <article class="pf-metric"><strong>10 000+ м²</strong><span>ежемесячной отгрузки</span></article>
                <article class="pf-metric"><strong>3</strong><span>офиса в регионе</span></article>
            </div>
        </div>
    </section>

    <div class="pf-promo-rail pf-reveal">
        @foreach($promos as $i => $src)
            <a class="pf-promo" href="{{ url('/catalog.html') }}" aria-label="Промо {{ $i + 1 }}">
                <img src="{{ $src }}" alt="Акция {{ $i + 1 }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
            </a>
        @endforeach
    </div>

    <section class="pf-section pf-reveal">
        <div class="pf-home-features">
            <article class="pf-feature">
                <div class="pf-meta">Быстрый старт</div>
                <h3>Калькулятор расчёта</h3>
                <p>Ориентировочный комплект материалов и стоимость до выезда на объект.</p>
                <a class="pf-btn pf-btn-primary" href="{{ url('/service/kalkulyator-rascheta-krovli.html') }}">Рассчитать</a>
            </article>
            <article class="pf-feature">
                <div class="pf-meta">Сервис</div>
                <h3>Выезд на замер</h3>
                <p>Инженер приедет, снимет размеры и предложит рабочие варианты под бюджет.</p>
                <a class="pf-btn pf-btn-ghost" href="{{ url('/contacts.html') }}#form">Вызвать замерщика</a>
            </article>
            <article class="pf-feature">
                <div class="pf-meta">Ассортимент</div>
                <h3>От эконом до премиум</h3>
                <p>Металлочерепица, профнастил, фасады, водосток, утеплитель и доборные элементы.</p>
                <a class="pf-btn pf-btn-ghost" href="{{ url('/catalog.html') }}">Смотреть каталог</a>
            </article>
        </div>
    </section>

    @if($categories->isNotEmpty())
        <section class="pf-section pf-reveal">
            <h2 class="pf-section-title">Разделы каталога</h2>
            <p class="pf-lead">Все URL сохранены для SEO. Выберите категорию и перейдите к товарам.</p>
            <div class="pf-dept-grid">
                @foreach($categories as $category)
                    <a class="pf-dept-card" href="{{ url('/catalog/'.$category->slug.'.html') }}">
                        @include('partials.catalog-icon', ['slug' => $category->slug])
                        <span class="pf-dept-body">
                            <strong>{{ $category->name }}</strong>
                            <span class="pf-muted">Открыть раздел →</span>
                        </span>
                        <span class="pf-dept-arrow" aria-hidden="true">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section
        class="pf-stats-band pf-reveal"
        style="background-image: url('{{ $statsBg }}');"
    >
        <div class="pf-stats-band-inner">
            <article><strong>30+</strong><span>лет опыта в продаже и комплектации</span></article>
            <article><strong>14 000+</strong><span>кровель реализовано</span></article>
            <article><strong>10 000+ м²</strong><span>продукции отгружаем ежемесячно</span></article>
            <article><strong>3</strong><span>офиса в регионе</span></article>
            <article><strong>1 день</strong><span>ответа на заявку</span></article>
            <article><strong>SEO</strong><span>старые адреса сохранены</span></article>
        </div>
    </section>
@endsection
