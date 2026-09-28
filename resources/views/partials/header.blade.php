@php
    $path = trim(request()->path(), '/');
    $nav = [
        ['label' => 'Каталог', 'href' => url('/catalog.html'), 'match' => 'catalog'],
        ['label' => 'Объекты', 'href' => url('/projects.html'), 'match' => 'projects'],
        ['label' => 'Покрытия', 'href' => url('/pokrytiya'), 'match' => 'pokrytiya'],
        ['label' => 'Новости', 'href' => url('/news.html'), 'match' => 'news'],
        ['label' => 'Калькулятор', 'href' => url('/service/kalkulyator-rascheta-krovli.html'), 'match' => 'service/kalkulyator'],
        ['label' => 'О компании', 'href' => url('/about.html'), 'match' => 'about'],
        ['label' => 'Контакты', 'href' => url('/contacts.html'), 'match' => 'contacts'],
    ];
@endphp

<header class="pf-header" data-pf-header>
    <div class="pf-header-inner">
        <a class="pf-logo" href="{{ url('/') }}" aria-label="Портал — на главную">
            @if(file_exists(public_path('portal.svg')))
                <img src="{{ asset('portal.svg') }}" alt="Портал">
            @else
                Портал<span>firma</span>
            @endif
        </a>

        <nav class="pf-nav" aria-label="Основное меню">
            @foreach($nav as $item)
                @php
                    $active = $item['match'] !== '' && (
                        $path === $item['match']
                        || str_starts_with($path, $item['match'].'/')
                        || str_starts_with($path, $item['match'].'.')
                    );
                @endphp
                <a href="{{ $item['href'] }}" class="{{ $active ? 'is-active' : '' }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="pf-header-actions">
            <a class="pf-header-phone" href="tel:+78617278000">
                <span class="pf-header-phone-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6.5 3.8h2.4l1.2 3.1-1.5 1.5a12.5 12.5 0 0 0 5.5 5.5l1.5-1.5 3.1 1.2v2.4A2.2 2.2 0 0 1 16.5 20 14.7 14.7 0 0 1 4 7.5 2.2 2.2 0 0 1 6.5 3.8z"/>
                    </svg>
                </span>
                <span class="pf-header-phone-text">
                    <small>Звонок бесплатный</small>
                    <strong>8 (8617) 27-80-00</strong>
                </span>
            </a>
            <a class="pf-header-cta" href="{{ url('/contacts.html') }}#form">Заказать звонок</a>
            <button type="button" class="pf-burger" data-pf-burger aria-label="Открыть меню" aria-expanded="false">
                <span></span>
            </button>
        </div>
    </div>

    <nav class="pf-mobile-nav" data-pf-mobile-nav aria-label="Мобильное меню">
        @foreach($nav as $item)
            <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
        @endforeach
        <a class="pf-mobile-nav-phone" href="tel:+78617278000">8 (8617) 27-80-00</a>
        <a class="pf-mobile-nav-cta" href="{{ url('/contacts.html') }}#form">Заказать звонок</a>
    </nav>
</header>
