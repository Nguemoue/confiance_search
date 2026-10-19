<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/locale/{locale}', function (string $locale, Request $request) {
    if (in_array($locale, ['fr', 'en'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }

    $referer = $request->headers->get('referer');
    if ($referer) {
        $parsed = parse_url($referer);
        $queryParams = [];
        if (! empty($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }
        $queryParams['lang'] = $locale;
        $path = $parsed['path'] ?? '/';
        $redirectUrl = $path.'?'.http_build_query($queryParams);

        return redirect($redirectUrl);
    }

    return redirect()->route('home', ['lang' => $locale]);
})->name('locale.switch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
