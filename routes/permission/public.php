<?php

use App\Http\Controllers\SiteController;
use App\Http\Controllers\Site\ForumController;
use App\Http\Controllers\Site\JournalController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\TrainingController;
use Illuminate\Support\Facades\Route;

/*
| Situs publik (PRD 7). Menu tetap di resources/views/components/site/header.blade.php.
*/
Route::controller(SiteController::class)->name('site.')->group(function () {
    Route::get('/', 'home')->name('home');

    # Profil
    Route::get('profil', 'profile')->name('profile');
    Route::get('pengurus', 'board')->name('board');
    Route::get('pengurus-cabang', 'branches')->name('branches');
    Route::get('pengurus-cabang/{slug}', 'branch')->name('branch');
    Route::get('galeri', 'galleries')->name('gallery');
    Route::get('galeri/{slug}', 'gallery')->name('gallery.show');
    Route::get('peraturan-organisasi', 'documents')->name('documents');

    # Keanggotaan
    Route::get('keanggotaan', 'membership')->name('membership');
    Route::get('keanggotaan/layanan', 'memberServices')->name('membership.services');
    Route::get('keanggotaan/cek', 'memberCheck')->middleware('throttle:member-check')->name('membership.check');

    # Kontak & legal
    Route::get('kontak', 'contact')->name('contact');
    Route::post('kontak', 'sendContact')->middleware('throttle:contact')->name('contact.send');
    Route::get('kebijakan-privasi', 'privacy')->name('privacy');
    Route::get('syarat-ketentuan', 'terms')->name('terms');
    Route::get('halaman/{slug}', 'page')->name('page');
    # SEO
    Route::get('sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('robots.txt', 'robots')->name('robots');
});

/*
| Berita, Forum, Jurnal (M3)
*/
Route::name('site.')->group(function () {
    Route::controller(NewsController::class)->group(function () {
        Route::get('berita', 'index')->name('news');
        Route::get('berita/kategori/{category:slug}', 'category')->name('news.category');
        Route::get('berita/tag/{tag:slug}', 'tag')->name('news.tag');
        Route::get('berita/{slug}', 'show')->name('news.show');
    });
    Route::controller(ForumController::class)->group(function () {
        Route::get('forum', 'index')->name('forum');
        Route::post('forum/laporan', 'report')->middleware('auth')->name('forum.report');
        Route::get('forum/{slug}', 'show')->name('forum.show');
        Route::post('forum/{slug}/komentar', 'comment')->middleware(['auth', 'throttle:comments'])->name('forum.comment');
    });
    Route::controller(TrainingController::class)->group(function () {
        Route::get('pelatihan', 'index')->name('training');
        Route::get('pelatihan/{slug}', 'show')->name('training.show');
        Route::post('pelatihan/{slug}/daftar', 'register')->middleware(['auth', 'verified', 'throttle:10,1'])->name('training.register');
        Route::get('sertifikat/{code}', 'certificate')->middleware('throttle:30,1')->name('certificate');
    });
    Route::controller(JournalController::class)->group(function () {
        Route::get('jurnal', 'index')->name('journal');
        Route::get('jurnal/{slug}', 'show')->name('journal.show');
        Route::get('jurnal/{slug}/unduh', 'download')->name('journal.download');
    });
});
