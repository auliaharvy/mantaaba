<x-site.auth-card title="Verifikasi Email">
    <p class="mb-4 text-gray-700">Kami telah mengirim tautan verifikasi ke <strong>{{ auth()->user()->email }}</strong>. Buka email tersebut lalu klik tautannya untuk melanjutkan.</p>
    @if (session('status') === 'verification-link-sent')
        <x-site.alert class="mb-4">Tautan verifikasi baru telah dikirim.</x-site.alert>
    @endif
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">@csrf<x-site.button size="sm">Kirim ulang email</x-site.button></form>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-gray-600 underline">Keluar</button></form>
    </div>
</x-site.auth-card>
