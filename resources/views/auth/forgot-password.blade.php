<x-site.auth-card title="Lupa Password" subtitle="Masukkan email akun Anda. Kami kirimkan tautan untuk mengatur password baru.">
    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <x-site.input name="email" label="Email" type="email" required autocomplete="email" autofocus />
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <x-site.button :href="route('login')" variant="outline">Kembali</x-site.button>
            <x-site.button>Kirim Tautan</x-site.button>
        </div>
    </form>
</x-site.auth-card>
