<?php

use App\Http\Controllers\Site\AdmissionController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\TrainingController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
| Situs publik (PRD 8). Header & footer dari Menu Manager; halaman bebas `/{slug}` didaftarkan paling akhir di web.php.
*/
Route::get('/', [PageController::class, 'home'])->name('site.home');

Route::controller(AdmissionController::class)->name('site.admission')->prefix('pendaftaran')->group(function () {
    Route::get('/', 'show')->name('');
    Route::post('/', 'store')->middleware('throttle:admission')->name('.store');
    Route::get('selesai/{number}', 'done')->middleware('signed')->name('.done');
    Route::get('bukti/{number}', 'receipt')->middleware('signed')->name('.receipt');
    Route::match(['get', 'post'], 'status', 'status')->middleware('throttle:10,1')->name('.status');
});

Route::controller(SiteController::class)->name('site.')->group(function () {
    Route::get('kurikulum', 'curriculum')->name('curriculum');
    Route::get('ekosistem', 'units')->name('units');
    Route::get('ekosistem/{slug}', 'unit')->name('units.show');
    Route::get('galeri', 'galleries')->name('gallery');
    Route::get('galeri/{slug}', 'gallery')->name('gallery.show');
    Route::get('dokumen', 'documents')->name('documents');
    Route::get('dokumen/{document:slug}/unduh', 'downloadDocument')->name('documents.download');
    Route::get('donasi', 'donation')->name('donation');
    Route::get('kontak', 'contact')->name('contact');
    Route::post('kontak', 'sendContact')->middleware('throttle:contact')->name('contact.send');
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
