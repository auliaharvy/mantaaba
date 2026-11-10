<x-site.layout title="Donasi" description="Dukung pendidikan gratis Rumah Mantaaba Sigaling: asrama, konsumsi, dan pembelajaran siswa dibiayai ekosistem dan donatur.">
    <x-site.breadcrumb-header title="Donasi" :crumbs="['Donasi']" subtitle="Seluruh pendidikan, asrama, kesehatan, dan konsumsi siswa gratis. Setiap donasi dicatat dan dilaporkan secara terbuka." />
    <div class="container-site grid gap-10 py-12 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-site.section-heading title="Rekening donasi" />
            @forelse ($site->bank_accounts ?? [] as $account)
                <div class="rounded-site border border-brand-border bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-accent">{{ $account['bank'] }}</p>
                    <p class="mt-1 font-display text-2xl tracking-wider text-brand-primary">{{ $account['number'] }}</p>
                    <p class="text-sm text-gray-700">a.n. {{ $account['holder'] }}</p>
                </div>
            @empty
                <x-site.empty-state title="Informasi rekening segera tersedia" icon="heart">Hubungi kami untuk berdonasi.</x-site.empty-state>
            @endforelse
            @if ($site->donation_note)<p class="whitespace-pre-line text-gray-700">{{ $site->donation_note }}</p>@endif
            <p class="text-sm text-gray-600">Laporan penggunaan dana dipublikasikan di halaman <a href="{{ route('site.documents') }}" class="font-semibold text-brand-accent underline">Dokumen</a>.</p>
        </div>
        <aside class="space-y-5 self-start rounded-site border border-brand-border bg-brand-section-bg p-6 text-center">
            @if ($site->qris_image)
                <img src="{{ $site->imageUrl('qris_image') }}" alt="QRIS donasi {{ $site->site_name }}" class="mx-auto w-full max-w-xs rounded-site bg-white p-2">
            @endif
            <p class="text-gray-700">Sudah transfer? Isi formulir konfirmasi agar donasi tercatat dan bukti terima dikirim.</p>
            @if ($wa = $site->whatsappUrl('Assalamualaikum, saya ingin konfirmasi donasi untuk Rumah Mantaaba Sigaling.'))
                <x-site.button :href="$wa" variant="accent" target="_blank" rel="noopener">Tanya via WhatsApp</x-site.button>
            @endif
        </aside>
    </div>
    <div class="container-site grid gap-10 pb-12 lg:grid-cols-3">
        <section class="lg:col-span-2" id="konfirmasi">
            <x-site.section-heading title="Konfirmasi donasi" />
            @if (session('success'))<x-site.alert class="mb-6">{{ session('success') }}</x-site.alert>@endif
            @if ($errors->any())<x-site.alert type="error" class="mb-6">Periksa kembali isian yang ditandai.</x-site.alert>@endif
            <form method="POST" action="{{ route('site.donation.send') }}" enctype="multipart/form-data" class="grid gap-5 rounded-site border border-brand-border bg-white p-6 shadow-sm sm:grid-cols-2" novalidate>
                @csrf
                <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <div class="sm:col-span-2">
                    <x-site.input name="donor_name" label="Nama donatur" />
                    <label class="-mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous')) class="rounded border-brand-border text-brand-primary focus:ring-brand-primary"> Sembunyikan nama saya (tercatat sebagai "Hamba Allah")</label>
                </div>
                <x-site.input name="phone" label="WhatsApp" type="tel" required />
                <x-site.input name="email" label="Email (untuk bukti terima)" type="email" />
                <x-site.input name="amount" label="Jumlah (Rp)" type="number" required min="1000" step="1" inputmode="numeric" />
                <x-site.input name="transferred_at" label="Tanggal transfer" type="date" required :value="today()->toDateString()" />
                @foreach (['coa_bank_account_id' => ['Rekening tujuan', $banks->pluck('name', 'id')->all()], 'purpose' => ['Peruntukan', collect(App\Models\Donation::PURPOSES)->map(fn ($p) => $p[0])->all()]] as $field => [$label, $options])
                    <div class="mb-4">
                        <label for="f-{{ $field }}" class="mb-1 block text-sm font-semibold">{{ $label }}<span class="text-red-700"> *</span></label>
                        <select id="f-{{ $field }}" name="{{ $field }}" required class="w-full rounded-md border-brand-border focus:border-brand-primary focus:ring-brand-primary @error($field) border-red-600 @enderror" @error($field) aria-invalid="true" aria-describedby="e-{{ $field }}" @enderror>
                            <option value="">— Pilih —</option>
                            @foreach ($options as $value => $text)<option value="{{ $value }}" @selected((string) old($field) === (string) $value)>{{ $text }}</option>@endforeach
                        </select>
                        @error($field)<p id="e-{{ $field }}" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <div class="sm:col-span-2">
                    <x-site.input name="proof" label="Bukti transfer (foto/PDF, maks. 5 MB)" type="file" required accept="image/*,application/pdf" />
                </div>
                <div class="sm:col-span-2">
                    <label for="f-message" class="mb-1 block text-sm font-semibold">Pesan / doa</label>
                    <textarea id="f-message" name="message" rows="3" class="w-full rounded-md border-brand-border focus:border-brand-primary focus:ring-brand-primary">{{ old('message') }}</textarea>
                </div>
                <div class="sm:col-span-2"><x-site.button>Kirim Konfirmasi</x-site.button></div>
            </form>
        </section>
        <section class="self-start rounded-site border border-brand-border bg-white p-6 shadow-sm">
            <h2 class="font-display text-xl text-brand-primary">Transparansi {{ $year }}</h2>
            <p class="mb-4 text-sm text-gray-600">Donasi tercatat sampai hari ini, tanpa nama donatur.</p>
            <dl class="space-y-2 text-sm">
                @foreach ($summary['donations'] as $purpose => $v)
                    <div class="flex justify-between gap-3"><dt>{{ App\Models\Donation::PURPOSES[$purpose][0] ?? $purpose }}</dt><dd class="font-semibold">{{ App\Support\Money::format(App\Support\Money::decimal($v['amount'])) }}</dd></div>
                @endforeach
                <div class="flex justify-between gap-3 border-t border-brand-border pt-2"><dt class="font-semibold">Jumlah donasi</dt><dd class="font-bold text-brand-primary">{{ App\Support\Money::format(App\Support\Money::decimal($summary['donation_total'])) }}</dd></div>
                <div class="flex justify-between gap-3"><dt>Dana digunakan</dt><dd class="font-semibold">{{ App\Support\Money::format(App\Support\Money::decimal($summary['expense_total'])) }}</dd></div>
                <div class="flex justify-between gap-3"><dt>Penerima manfaat</dt><dd class="font-semibold">{{ $summary['beneficiaries'] }} siswa</dd></div>
            </dl>
        </section>
    </div>
</x-site.layout>
