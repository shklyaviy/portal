<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle ?? 'Portalfirma' }} | Кровельный центр «Портал»</title>
    @if(!empty($seoDescription))
        <meta name="description" content="{{ $seoDescription }}">
    @endif
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Unbounded:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    @stack('head')
</head>
<body>
    @include('partials.header')

    <main class="pf-main">
        @if(session('success'))
            <div class="pf-alert pf-reveal">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    @include('partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
    <script src="{{ asset('js/portal.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
