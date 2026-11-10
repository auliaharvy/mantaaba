<x-site.auth-card title="Masuk" subtitle="Untuk pengurus dan anggota Rumah Mantaaba Sigaling.">
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <x-site.input name="email" label="Email" type="email" required autocomplete="username" autofocus />
        <x-site.input name="password" label="Password" type="password" required autocomplete="current-password" />
        <div class="mb-6 flex items-center justify-between gap-4 text-sm">
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="remember" class="rounded border-brand-border text-brand-primary focus:ring-brand-primary"> Ingat saya</label>
            <a href="{{ route('password.request') }}" class="font-semibold text-brand-accent underline">Lupa password?</a>
        </div>
        <x-site.button class="w-full">Masuk</x-site.button>
    </form>
    <p class="mt-6 text-center text-sm text-gray-600">Akun dibuat oleh pengurus. Calon siswa silakan ke halaman <a href="{{ route('site.admission') }}" class="font-semibold text-brand-accent underline">Pendaftaran</a>.</p>
</x-site.auth-card>
