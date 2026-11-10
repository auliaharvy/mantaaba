<x-site.auth-card title="Atur Password" subtitle="Buat password baru minimal 8 karakter.">
    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-site.input name="email" label="Email" type="email" :value="old('email', $request->email)" required readonly autocomplete="username" />
        <x-site.input name="password" label="Password baru" type="password" required autocomplete="new-password" autofocus />
        <x-site.input name="password_confirmation" label="Ulangi password" type="password" required autocomplete="new-password" />
        <x-site.button class="w-full">Simpan Password</x-site.button>
    </form>
</x-site.auth-card>
