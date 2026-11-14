<?php

use App\Http\Controllers\InvestorsController;
use App\Http\Controllers\LegacyRedirectController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');

foreach (['products','company','plants','clients','sustainability','certificates','grievance','contact'] as $page) {
    Route::view("/{$page}", "pages.{$page}")->name($page);
}

Route::controller(InvestorsController::class)->group(function () {
    Route::get('/investors', 'index')->name('investors');
    Route::get('/investors/documents/{document}/{slug?}', 'document')->name('investors.document');
    Route::get('/investors/{category}/{child?}', 'category')->name('investors.category');
    Route::get('/sitemap.xml', 'sitemap');
});

// Old addresses (pgfoils.co.in / pgfoils.co) -> new ones, permanent redirects. See config/legacy.php.
Route::get('/reports/{id}/{rest?}', [LegacyRedirectController::class, 'report'])->whereNumber('id')->where('rest', '.*');
Route::get('/storage/{path}', [LegacyRedirectController::class, 'file'])->where('path', '.+');
Route::get('/{page}.html', [LegacyRedirectController::class, 'page'])->where('page', '[A-Za-z0-9_-]+');

Route::view('/quote', 'pages.quote')->name('quote');

// Dev-only reference page for the design tokens.
if (! app()->environment('production')) {
    Route::view('/_styleguide', 'pages.styleguide')->name('styleguide');
}
