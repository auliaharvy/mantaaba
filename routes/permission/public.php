<?php

use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\TrainingController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
| Situs publik (PRD 8). Header & footer dikelola Menu Manager mulai M1.
*/
Route::controller(SiteController::class)->name('site.')->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('profil', 'profile')->name('profile');
    Route::get('galeri', 'galleries')->name('gallery');
    Route::get('galeri/{slug}', 'gallery')->name('gallery.show');
    Route::get('dokumen', 'documents')->name('documents');
    Route::get('kontak', 'contact')->name('contact');
    Route::post('kontak', 'sendContact')->middleware('throttle:contact')->name('contact.send');
    Route::get('kebijakan-privasi', 'privacy')->name('privacy');
    Route::get('halaman/{slug}', 'page')->name('page');
    Route::get('sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('robots.txt', 'robots')->name('robots');
});

Route::name('site.')->group(function () {
    Route::controller(NewsController::class)->group(function () {
        Route::get('berita', 'index')->name('news');
        Route::get('berita/kategori/{category:slug}', 'category')->name('news.category');
        Route::get('berita/tag/{tag:slug}', 'tag')->name('news.tag');
        Route::get('berita/{slug}', 'show')->name('news.show');
    });
    Route::controller(TrainingController::class)->group(function () {
        Route::get('agenda', 'index')->name('training');
        Route::get('agenda/{slug}', 'show')->name('training.show');
        Route::post('agenda/{slug}/daftar', 'register')->middleware(['auth', 'throttle:10,1'])->name('training.register');
        Route::get('sertifikat/{code}', 'certificate')->middleware('throttle:30,1')->name('certificate');
    });
});
