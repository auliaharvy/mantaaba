<x-site.auth-card title="Konfirmasi Password">
    <p class="mb-4 text-sm text-gray-600">Area ini dilindungi. Masukkan password Anda untuk melanjutkan.</p>
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <x-site.input name="password" label="Password" type="password" required autocomplete="current-password" />
        <x-site.button class="w-full">Konfirmasi</x-site.button>
    </form>
</x-site.auth-card>
