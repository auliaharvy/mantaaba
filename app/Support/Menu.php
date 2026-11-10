<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Navigasi dashboard dari config('mantaaba.menu'): hanya item yang diizinkan, modul hanya bila punya item. */
class Menu
{
    public static function visible(array $item, User $user): bool
    {
        return (! isset($item['can']) || $user->can($item['can']))
            && (! isset($item['role']) || $user->hasRole($item['role']))
            && (empty($item['member']) || $user->member);
    }

    public static function top(User $user): Collection
    {
        return collect(config('mantaaba.menu.top'))->filter(fn ($i) => self::visible($i, $user))->values();
    }

    /** [key => modul + items yang tampil]. */
    public static function modules(User $user): Collection
    {
        return collect(config('mantaaba.menu.modules'))
            ->map(fn ($m, $key) => array_merge($m, ['key' => $key, 'items' => collect($m['items'])->filter(fn ($i) => self::visible($i, $user))->values()]))
            ->filter(fn ($m) => $m['items']->isNotEmpty());
    }

    public static function isActive(array $item): bool
    {
        return request()->routeIs(...explode('|', $item['active'] ?? Str::beforeLast($item['route'], '.') . '.*'));
    }

    /** Modul terbuka bila halaman ringkasannya atau salah satu itemnya aktif. */
    public static function isOpen(array $module): bool
    {
        return (request()->routeIs('overview.show') && request()->route('key') === $module['key'])
            || $module['items']->contains(fn ($i) => self::isActive($i));
    }
}
