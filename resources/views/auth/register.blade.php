<x-site.auth-card title="Buat Akun">
    <p class="mb-6 text-sm text-gray-600">Akun umum untuk mendaftar pelatihan/seminar dan mengunduh sertifikat. Pendaftaran <strong>anggota AKP2I</strong> diproses Pengurus Pusat:
        <a href="{{ App\Models\Setting::current()->url_registration }}" target="_blank" rel="noopener" class="text-brand-secondary underline">daftar anggota di sini</a>.</p>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <x-site.input name="name" label="Nama lengkap" required autocomplete="name" autofocus />
        <x-site.input name="email" label="Email" type="email" required autocomplete="email" />
        <x-site.input name="phone_number" label="Telepon / WhatsApp" type="tel" autocomplete="tel" />
        <x-site.input name="password" label="Password (min. 8 karakter)" type="password" required autocomplete="new-password" />
        <x-site.input name="password_confirmation" label="Ulangi password" type="password" required autocomplete="new-password" />
        <x-site.button class="mt-2 w-full">Daftar</x-site.button>
    </form>
    <p class="mt-6 text-center text-sm">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-brand-secondary underline">Masuk</a></p>
</x-site.auth-card>
