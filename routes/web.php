<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/catalog.html', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/search.json', [CatalogController::class, 'search'])->name('catalog.search');
Route::get('/catalog/{path}.html', [CatalogController::class, 'show'])
    ->where('path', '.+')
    ->name('catalog.show');

Route::get('/about.html', [PageController::class, 'about'])->name('about');
Route::get('/contacts.html', [PageController::class, 'contacts'])->name('contacts');

Route::get('/news.html', [PageController::class, 'newsIndex'])->name('news.index');
Route::get('/news/{slug}.html', [PageController::class, 'newsShow'])->name('news.show');

Route::get('/projects.html', [PageController::class, 'projectsIndex'])->name('projects.index');
Route::get('/projects/{slug}.html', [PageController::class, 'projectShow'])->name('projects.show');

Route::get('/pokrytiya/', [PageController::class, 'coatingsIndex'])->name('coatings.index');
Route::get('/pokrytiya/{slug}.html', [PageController::class, 'coatingShow'])->name('coatings.show');

Route::get('/service/{slug}.html', [PageController::class, 'service'])->name('service.show');

Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
