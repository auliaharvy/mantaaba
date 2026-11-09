<x-site.layout title="Cek Anggota" description="Periksa status keanggotaan AKP2I Jawa Barat berdasarkan nomor anggota.">
    <x-site.breadcrumb-header title="Cek Anggota" :crumbs="['Keanggotaan' => route('site.membership'), 'Cek Anggota']" />
    <div class="container-site py-12">
        <div class="mx-auto max-w-xl">
            <form method="GET" action="{{ route('site.membership.check') }}" class="flex flex-col gap-3 sm:flex-row">
                <label for="nomor" class="sr-only">Nomor anggota</label>
                <input id="nomor" name="nomor" value="{{ $number }}" required maxlength="50" placeholder="Masukkan nomor anggota"
                    class="flex-1 rounded-md border-brand-border focus:border-brand-primary focus:ring-brand-primary">
                <x-site.button><x-site.icon name="search" class="h-4 w-4" /> Cek</x-site.button>
            </form>
            @error('nomor')<x-site.alert type="error" class="mt-4">{{ $message }}</x-site.alert>@enderror

            @if ($number !== '')
                <div class="mt-8" aria-live="polite">
                    @if ($member)
                        @php($active = $member->status !== 'berhenti')
                        <div class="rounded-lg border border-brand-border bg-white p-6 shadow-sm">
                            <p class="text-sm text-gray-600">Nomor anggota {{ $member->member_number }}</p>
                            <p class="mt-1 text-xl font-bold text-brand-heading">{{ $member->user->name }}</p>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><dt class="text-gray-600">Jenis</dt><dd class="font-semibold">{{ App\Models\Member::TYPES[$member->membership_type] }}</dd></div>
                                <div><dt class="text-gray-600">Cabang</dt><dd class="font-semibold">{{ $member->organization->name }}</dd></div>
                                <div class="col-span-2"><dt class="text-gray-600">Status</dt>
                                    <dd><span class="mt-1 inline-block rounded px-2 py-0.5 text-sm font-bold {{ $member->status === 'aktif' ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' }}">
                                        {{ ['aktif' => 'Aktif', 'skors' => 'Skors', 'berhenti' => 'Tidak aktif'][$member->status] }}</span></dd></div>
                            </dl>
                        </div>
                    @else
                        <x-site.empty-state title="Nomor anggota tidak ditemukan" icon="search">Periksa kembali penulisan nomor, atau hubungi sekretariat.</x-site.empty-state>
                    @endif
                </div>
            @endif
            <p class="mt-8 text-sm text-gray-600">Data yang ditampilkan hanya nama, jenis, cabang, dan status keanggotaan sesuai <a href="{{ route('site.privacy') }}" class="text-brand-secondary underline">Kebijakan Privasi</a>.</p>
        </div>
    </div>
</x-site.layout>
