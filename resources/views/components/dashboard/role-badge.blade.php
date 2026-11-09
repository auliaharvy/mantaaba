@props(['user'])
@php
    $colors = ['super_administrator' => 'primary', 'admin_pd' => 'success', 'editor' => 'info', 'admin_pc' => 'info', 'anggota' => 'warning', 'umum' => 'danger'];
@endphp
@foreach ($user->getRoleNames() as $role)
<span {{ $attributes->merge(['class' => 'badge badge-light-' . ($colors[$role] ?? 'secondary')]) }}>{{ config('akp2i.roles.' . $role, $role) }}</span>
@endforeach
