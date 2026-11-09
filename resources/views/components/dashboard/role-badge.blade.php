@props(['user'])
@php($colors = ['super_admin' => 'primary', 'pengurus' => 'success', 'anggota' => 'warning'])
@foreach ($user->getRoleNames() as $role)
<span {{ $attributes->merge(['class' => 'badge badge-light-' . ($colors[$role] ?? 'secondary')]) }}>{{ config('mantaaba.roles.' . $role, $role) }}</span>
@endforeach
