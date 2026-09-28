<?php

namespace App\Support;

class CatalogIcons
{
    public static function keyForSlug(string $slug): string
    {
        $slug = mb_strtolower($slug);

        return match (true) {
            str_contains($slug, 'zabor') || str_contains($slug, 'ograzhden') => 'fence',
            str_contains($slug, 'fasad') || str_contains($slug, 'sayding') => 'facade',
            str_contains($slug, 'vodostoch') => 'drain',
            str_contains($slug, 'teploizol') => 'insulation',
            str_contains($slug, 'gidro') || str_contains($slug, 'paroizol') => 'membrane',
            str_contains($slug, 'polikarbonat') => 'polycarbonate',
            str_contains($slug, 'sendvich') => 'sandwich',
            str_contains($slug, 'mansard') || str_contains($slug, 'okna') => 'window',
            str_contains($slug, 'lestnits') => 'ladder',
            str_contains($slug, 'karniz') || str_contains($slug, 'sves') => 'soffit',
            str_contains($slug, 'bezopasnost') => 'safety',
            str_contains($slug, 'doborn') => 'trim',
            str_contains($slug, 'potolok') => 'ceiling',
            str_contains($slug, 'soputstv') => 'tools',
            str_contains($slug, 'krov') || str_contains($slug, 'cherepits') || str_contains($slug, 'profnast') => 'roof',
            default => 'box',
        };
    }

    public static function svg(string $slug): string
    {
        $key = self::keyForSlug($slug);

        $paths = match ($key) {
            'roof' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13V10.5"/><path d="M10 20v-5h4v5"/>',
            'facade' => '<path d="M4 20V6.5L12 3l8 3.5V20"/><path d="M9 20v-6h6v6"/><path d="M8 10h.01M12 10h.01M16 10h.01"/>',
            'fence' => '<path d="M4 20V8l2-3 2 3V20"/><path d="M10 20V8l2-3 2 3V20"/><path d="M16 20V8l2-3 2 3V20"/><path d="M3 12h18"/>',
            'drain' => '<path d="M4 7h16"/><path d="M6 7v4a4 4 0 0 0 4 4h0a4 4 0 0 0 4-4V7"/><path d="M14 15v3a3 3 0 0 0 3 3h1"/>',
            'insulation' => '<path d="M4 6h16v12H4z"/><path d="M4 10h16M4 14h16M8 6v12M12 6v12M16 6v12"/>',
            'membrane' => '<path d="M3 8c3-3 6-3 9 0s6 3 9 0"/><path d="M3 13c3-3 6-3 9 0s6 3 9 0"/><path d="M3 18c3-3 6-3 9 0s6 3 9 0"/>',
            'polycarbonate' => '<path d="M4 5h16v14H4z"/><path d="M4 10h16M4 15h16M9 5v14M15 5v14"/>',
            'sandwich' => '<path d="M4 7h16v3H4zM4 12h16v3H4zM4 17h16v2H4z"/>',
            'window' => '<path d="M5 5h14v14H5z"/><path d="M12 5v14M5 12h14"/>',
            'ladder' => '<path d="M8 3v18M16 3v18M8 7h8M8 11h8M8 15h8M8 19h8"/>',
            'soffit' => '<path d="M3 8h18"/><path d="M5 8v8h14V8"/><path d="M8 12h2M12 12h2M16 12h2"/>',
            'safety' => '<path d="M12 3 5 6v5c0 4.5 3 7.8 7 9 4-1.2 7-4.5 7-9V6l-7-3z"/>',
            'trim' => '<path d="M4 18 18 4"/><path d="M14 4h6v6"/><path d="M4 14v6h6"/>',
            'ceiling' => '<path d="M3 7h18"/><path d="M6 7v4l6 4 6-4V7"/><path d="M12 15v5"/>',
            'tools' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4L15 12z"/>',
            default => '<path d="M4 8h16v11H4z"/><path d="M4 11h16"/><path d="M9 8V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V8"/>',
        };

        return '<svg class="pf-cat-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
    }
}
