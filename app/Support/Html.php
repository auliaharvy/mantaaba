<?php

namespace App\Support;

/**
 * Pembersih HTML dari TinyMCE sebelum disimpan & ditampilkan mentah di situs publik.
 * shortcut: berbasis regex (tanpa package baru, PRD 5) — cukup untuk editor internal tepercaya;
 * ganti dengan HTML Purifier bila konten kelak berasal dari pengguna umum.
 */
class Html
{
    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }
        $html = preg_replace('#<(script|object|embed|applet|form|meta|link|base)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|object|embed|applet|form|meta|link|base)\b[^>]*/?>#i', '', $html);
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\']?)\s*(javascript|vbscript|data):#i', '$1=$2#blocked:', $html);

        return $html;
    }
}
