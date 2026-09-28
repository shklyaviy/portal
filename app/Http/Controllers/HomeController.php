<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get();

        return view('home', [
            'categories' => $categories,
            'seoTitle' => 'Кровельные материалы в Новороссийске — Кровельный центр «Портал»',
            'seoDescription' => 'Кровельный центр «Портал» в Новороссийске: кровля, фасады, заборы. Металлочерепица, профнастил, сайдинг. Замер, доставка, монтаж. Работаем с 1993 года.',
            'canonical' => url('/'),
        ]);
    }
}
