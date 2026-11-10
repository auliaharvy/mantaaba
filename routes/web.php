<?php

use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

# Auth routes
require __DIR__ . '/auth.php';

# Public routes
require __DIR__ . '/permission/public.php';

# Administrator routes
require __DIR__ . '/permission/administrator.php';

# Halaman CMS bebas (PRD 8): paling akhir agar tidak menutupi route lain
Route::get('{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('site.page');
