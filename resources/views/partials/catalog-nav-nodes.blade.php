@foreach($nodes as $node)
    @php
        $isActive = in_array($node->id, $activeIds, true);
        $isOpen = \App\Support\CatalogNav::nodeShouldOpen($node, $activeIds);
        $hasChildren = $node->children->isNotEmpty();
    @endphp
    <div
        class="pf-cat-node level-{{ $level }} {{ $isActive ? 'is-active' : '' }} {{ $isOpen ? 'is-open' : '' }}"
        data-cat-name="{{ mb_strtolower($node->name) }}"
    >
        <div class="pf-cat-node-row">
            <a href="{{ url('/catalog/'.$node->slug.'.html') }}" class="pf-cat-node-link {{ $isActive ? 'is-current' : '' }}">
                <span>{{ $node->name }}</span>
            </a>
            @if($hasChildren)
                <button
                    type="button"
                    class="pf-cat-node-toggle"
                    data-pf-cat-toggle
                    aria-label="{{ $isOpen ? 'Свернуть' : 'Развернуть' }} {{ $node->name }}"
                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                >
                    <span class="pf-cat-node-chevron" aria-hidden="true"></span>
                </button>
            @endif
        </div>
        @if($hasChildren)
            <div class="pf-cat-node-children">
                @include('partials.catalog-nav-nodes', [
                    'nodes' => $node->children,
                    'activeIds' => $activeIds,
                    'level' => $level + 1,
                ])
            </div>
        @endif
    </div>
@endforeach
