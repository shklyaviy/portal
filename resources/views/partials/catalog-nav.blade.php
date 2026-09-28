@php
    $activeIds = $activeIds ?? [];
    $navForest = $navForest ?? collect();
@endphp

<aside class="pf-cat-sidebar" aria-label="Навигация по каталогу" data-pf-catalog-side>
    <div class="pf-cat-search">
        <label class="pf-cat-search-label" for="pf-catalog-search">Поиск по каталогу</label>
        <div class="pf-cat-search-box">
            <span class="pf-cat-search-glyph" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </span>
            <input
                id="pf-catalog-search"
                type="search"
                autocomplete="off"
                placeholder="Раздел, товар, артикул…"
                data-pf-catalog-search
                data-search-url="{{ url('/catalog/search.json') }}"
            >
        </div>
        <div class="pf-cat-search-results" data-pf-catalog-results hidden></div>
    </div>

    <div class="pf-cat-sidebar-head">
        <a href="{{ url('/catalog.html') }}" class="pf-cat-sidebar-all">Все разделы</a>
        <strong class="pf-cat-sidebar-title">Каталог</strong>
    </div>

    <nav class="pf-cat-tree" data-pf-catalog-tree>
        @foreach($navForest as $root)
            @php
                $isActive = in_array($root->id, $activeIds, true);
                $isOpen = \App\Support\CatalogNav::nodeShouldOpen($root, $activeIds);
            @endphp
            <div
                class="pf-cat-node level-0 {{ $isActive ? 'is-active' : '' }} {{ $isOpen ? 'is-open' : '' }}"
                data-cat-name="{{ mb_strtolower($root->name) }}"
            >
                <div class="pf-cat-node-row">
                    <a href="{{ url('/catalog/'.$root->slug.'.html') }}" class="pf-cat-node-link {{ $isActive && count($activeIds) === 1 ? 'is-current' : '' }}">
                        <span class="pf-cat-node-label">
                            <span class="pf-cat-node-icon">{!! \App\Support\CatalogIcons::svg($root->slug) !!}</span>
                            <span>{{ $root->name }}</span>
                        </span>
                    </a>
                    @if($root->children->isNotEmpty())
                        <button
                            type="button"
                            class="pf-cat-node-toggle"
                            data-pf-cat-toggle
                            aria-label="{{ $isOpen ? 'Свернуть' : 'Развернуть' }} {{ $root->name }}"
                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        >
                            <span class="pf-cat-node-chevron" aria-hidden="true"></span>
                        </button>
                    @endif
                </div>
                @if($root->children->isNotEmpty())
                    <div class="pf-cat-node-children">
                        @include('partials.catalog-nav-nodes', [
                            'nodes' => $root->children,
                            'activeIds' => $activeIds,
                            'level' => 1,
                        ])
                    </div>
                @endif
            </div>
        @endforeach
    </nav>
</aside>
