@php
    $slug = $slug ?? '';
@endphp
<span class="pf-dept-icon" aria-hidden="true">{!! \App\Support\CatalogIcons::svg($slug) !!}</span>
