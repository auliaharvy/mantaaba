<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SiteCache;

/** Halaman CMS di situs publik (PRD 8): beranda, tentang, program, kebijakan privasi, halaman bebas. */
class PageController extends Controller
{
    public function home()
    {
        return $this->render(Page::published('home'));
    }

    public function show(string $slug)
    {
        if ($slug === 'home') {
            return redirect()->route('site.home', status: 301);
        }

        return $this->render(Page::published($slug));
    }

    /** Isi blok dirender sekali lalu di-cache 10 menit; pratinjau selalu segar. */
    public function render(Page $page, bool $preview = false)
    {
        $build = fn () => $page->template === 'rich'
            ? view('site.partials.rich', compact('page'))->render()
            : view('site.partials.blocks', ['blocks' => $page->visibleBlocks()])->render();
        $content = $preview ? $build() : SiteCache::remember("page.{$page->slug}", $build);

        return view('site.page', compact('page', 'content', 'preview'));
    }
}
