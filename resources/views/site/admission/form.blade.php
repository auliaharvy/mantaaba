{{-- Pendaftaran siswa (PRD 9.3): wizard 6 langkah (Alpine), satu POST di akhir. --}}
@php
    $steps = array_keys(App\Http\Controllers\Site\AdmissionController::STEPS);
    $errorStep = 0;
    foreach (array_values(App\Http\Controllers\Site\AdmissionController::STEPS) as $i => $fields) {
        if (collect($fields)->contains(fn ($f) => $errors->has($f) || $errors->has($f . '.*'))) { $errorStep = $i; break; }
    }
    $input = 'w-full rounded-md border-brand-border focus:border-brand-primary focus:ring-brand-primary';
@endphp
<x-site.layout title="Pendaftaran" description="Pendaftaran siswa Rumah Mantaaba Sigaling: inkubasi karakter & vokasi bisnis 6 bulan, berasrama, gratis.">
    <x-site.breadcrumb-header title="Pendaftaran Siswa" :crumbs="['Pendaftaran']" subtitle="Inkubasi karakter & vokasi bisnis 6 bulan, berasrama, seluruh biaya ditanggung." />
    <div class="container-site grid gap-10 py-12 lg:grid-cols-3">
        <aside class="space-y-6 lg:order-2">
            <div class="rounded-site border border-brand-border bg-brand-section-bg p-6">
                <h2 class="font-display text-xl text-brand-primary">Persyaratan</h2>
                @if ($site->admission_requirements)
                    <div class="prose-cms mt-3 text-sm">{!! $site->admission_requirements !!}</div>
                @else
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach (['Lulusan SMA/sederajat atau seusia, usia produktif.', 'Bersedia menetap di asrama dengan persetujuan orang tua/wali.', 'Bersedia menandatangani pakta integritas dan tata tertib.', 'Prioritas: pemuda sekitar, keluarga kurang mampu, dan anak yatim.'] as $rule)
                            <li class="flex gap-2"><x-site.icon name="check" class="h-5 w-5 flex-none text-brand-accent" />{{ $rule }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($site->parent_consent_template || $site->integrity_pact_template)
                    <div class="mt-5 flex flex-col gap-2">
                        @if ($site->parent_consent_template)<x-site.button :href="Storage::disk('public')->url($site->parent_consent_template)" variant="outline" size="sm"><x-site.icon name="download" class="h-4 w-4" /> Template surat persetujuan</x-site.button>@endif
                        @if ($site->integrity_pact_template)<x-site.button :href="Storage::disk('public')->url($site->integrity_pact_template)" variant="outline" size="sm"><x-site.icon name="download" class="h-4 w-4" /> Pakta integritas</x-site.button>@endif
                    </div>
                @endif
            </div>
            <div class="rounded-site border border-brand-border p-6 text-sm">
                <p class="font-semibold text-brand-heading">Sudah mendaftar?</p>
                <a href="{{ route('site.admission.status') }}" class="mt-1 inline-block font-semibold text-brand-accent underline">Cek status pendaftaran</a>
            </div>
        </aside>

        <div class="lg:col-span-2">
            @unless ($batch)
                <x-site.empty-state title="Pendaftaran belum dibuka" icon="calendar">
                    @if ($nextBatch?->admission_opens_at)
                        Pendaftaran {{ $nextBatch->name }} dibuka {{ $nextBatch->admission_opens_at->translatedFormat('j F Y') }}.
                    @else
                        Penerimaan dibuka per angkatan (setiap ±6 bulan). Ikuti kabar terbaru atau tanyakan kepada pengurus.
                    @endif
                    @if ($wa = $site->whatsappUrl('Assalamualaikum, saya ingin bertanya tentang pendaftaran Rumah Mantaaba Sigaling.'))
                        <div class="mt-4"><x-site.button :href="$wa" size="sm" target="_blank" rel="noopener">Tanya via WhatsApp</x-site.button></div>
                    @endif
                </x-site.empty-state>
            @else
                <div class="mb-6 rounded-site border-l-4 border-brand-gold bg-brand-section-bg p-4">
                    <p class="font-semibold text-brand-heading">{{ $batch->name }}</p>
                    <p class="text-sm text-gray-700">Pendaftaran dibuka{{ $batch->admission_closes_at ? ' sampai ' . $batch->admission_closes_at->translatedFormat('j F Y') : '' }} · kuota {{ $batch->quota }} siswa{{ $batch->starts_at ? ' · mulai ' . $batch->starts_at->translatedFormat('F Y') : '' }}</p>
                    @if ($batch->requirements_note)<p class="mt-2 text-sm text-gray-700">{{ $batch->requirements_note }}</p>@endif
                </div>
                @if ($errors->any())<x-site.alert type="error" class="mb-6">Periksa kembali isian yang ditandai merah. Berkas perlu dipilih ulang.</x-site.alert>@endif

                <form method="POST" action="{{ route('site.admission.store') }}" enctype="multipart/form-data" novalidate
                    x-data="{ step: {{ $errorStep }}, total: {{ count($steps) }}, birth: @js(old('birth_date')), len: {{ mb_strlen(old('motivation', '')) }},
                        get age() { if (!this.birth) return null; const b = new Date(this.birth), n = new Date(); let a = n.getFullYear() - b.getFullYear(); if (n < new Date(n.getFullYear(), b.getMonth(), b.getDate())) a--; return a; },
                        next() { const fields = [...this.$refs['s' + this.step].querySelectorAll('input, select, textarea')]; const bad = fields.find(f => !f.checkValidity()); if (bad) { bad.reportValidity(); return; } this.step++; window.scrollTo({ top: this.$el.offsetTop - 100, behavior: 'smooth' }); } }"
                    class="rounded-site border border-brand-border bg-white p-5 shadow-sm sm:p-8">
                    @csrf
                    <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    <ol class="mb-8 grid grid-cols-6 gap-1" aria-label="Langkah pendaftaran">
                        @foreach ($steps as $i => $label)
                            <li class="text-center">
                                <span class="block h-1.5 rounded-full" :class="step >= {{ $i }} ? 'bg-brand-primary' : 'bg-brand-border'"></span>
                                <span class="mt-2 hidden text-xs sm:block" :class="step === {{ $i }} ? 'font-bold text-brand-primary' : 'text-gray-500'">{{ $label }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mb-5 text-sm text-gray-600 sm:hidden">Langkah <span x-text="step + 1"></span> dari {{ count($steps) }}: <strong x-text="@js($steps)[step]"></strong></p>

                    {{-- 1. Data diri --}}
                    <fieldset x-ref="s0" x-show="step === 0" class="grid gap-5 sm:grid-cols-2">
                        <legend class="mb-4 font-display text-xl text-brand-primary">Data diri</legend>
                        <div class="sm:col-span-2"><x-site.input name="name" label="Nama lengkap" required /></div>
                        <x-site.input name="nik" label="NIK (16 digit)" required inputmode="numeric" pattern="[0-9]{16}" maxlength="16" />
                        <div class="mb-4">
                            <label for="f-gender" class="mb-1 block text-sm font-semibold">Jenis kelamin<span class="text-red-700"> *</span></label>
                            <select id="f-gender" name="gender" required class="{{ $input }}"><option value="">— Pilih —</option>@foreach (App\Models\Applicant::GENDERS as $v => $l)<option value="{{ $v }}" @selected(old('gender') === $v)>{{ $l }}</option>@endforeach</select>
                            @error('gender')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <x-site.input name="birth_place" label="Tempat lahir" required />
                        <div>
                            <x-site.input name="birth_date" label="Tanggal lahir" type="date" required x-model="birth" class="mb-0" />
                            <p x-show="age !== null && (age < 16 || age > 25)" x-cloak class="-mt-2 mb-4 text-sm text-amber-700">Usia <span x-text="age"></span> tahun di luar rentang umum (16–25). Anda tetap boleh mendaftar.</p>
                        </div>
                        <x-site.input name="phone" label="No. WhatsApp" type="tel" required />
                        <x-site.input name="email" label="Email (bila ada)" type="email" />
                        <div class="sm:col-span-2"><x-site.input name="address" label="Alamat (jalan, RT/RW)" required /></div>
                        <x-site.input name="village" label="Desa/Kelurahan" required />
                        <x-site.input name="district" label="Kecamatan" required />
                        <x-site.input name="regency" label="Kabupaten/Kota" required />
                    </fieldset>

                    {{-- 2. Pendidikan --}}
                    <fieldset x-ref="s1" x-show="step === 1" x-cloak class="grid gap-5 sm:grid-cols-2">
                        <legend class="mb-4 font-display text-xl text-brand-primary">Pendidikan</legend>
                        <div class="mb-4">
                            <label for="f-education" class="mb-1 block text-sm font-semibold">Pendidikan terakhir<span class="text-red-700"> *</span></label>
                            <select id="f-education" name="education" required class="{{ $input }}"><option value="">— Pilih —</option>@foreach (App\Models\Applicant::EDUCATIONS as $v)<option @selected(old('education') === $v)>{{ $v }}</option>@endforeach</select>
                            @error('education')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <x-site.input name="graduation_year" label="Tahun lulus" type="number" min="1990" max="{{ now()->year + 1 }}" />
                        <div class="sm:col-span-2"><x-site.input name="school_name" label="Nama sekolah" /></div>
                    </fieldset>

                    {{-- 3. Keluarga --}}
                    <fieldset x-ref="s2" x-show="step === 2" x-cloak class="grid gap-5 sm:grid-cols-2">
                        <legend class="mb-4 font-display text-xl text-brand-primary">Keluarga</legend>
                        <x-site.input name="father_name" label="Nama ayah" />
                        <x-site.input name="mother_name" label="Nama ibu" />
                        <x-site.input name="guardian_name" label="Nama wali (bila ada)" />
                        <x-site.input name="parent_job" label="Pekerjaan orang tua/wali" />
                        <x-site.input name="guardian_phone" label="No. WhatsApp orang tua/wali" type="tel" required />
                        <x-site.input name="siblings" label="Jumlah saudara" type="number" min="0" max="30" />
                        @foreach (['family_status' => ['Status keluarga', App\Models\Applicant::FAMILY_STATUSES], 'economic_status' => ['Kondisi ekonomi keluarga', App\Models\Applicant::ECONOMIC_STATUSES]] as $field => [$label, $options])
                            <div class="mb-4">
                                <label for="f-{{ $field }}" class="mb-1 block text-sm font-semibold">{{ $label }}<span class="text-red-700"> *</span></label>
                                <select id="f-{{ $field }}" name="{{ $field }}" required class="{{ $input }}"><option value="">— Pilih —</option>@foreach ($options as $v => $l)<option value="{{ $v }}" @selected(old($field) === $v)>{{ $l }}</option>@endforeach</select>
                                @error($field)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </fieldset>

                    {{-- 4. Motivasi --}}
                    <fieldset x-ref="s3" x-show="step === 3" x-cloak>
                        <legend class="mb-4 font-display text-xl text-brand-primary">Motivasi</legend>
                        <div class="mb-4">
                            <label for="f-motivation" class="mb-1 block text-sm font-semibold">Mengapa Anda ingin bergabung di Rumah Mantaaba Sigaling?<span class="text-red-700"> *</span></label>
                            <textarea id="f-motivation" name="motivation" rows="6" required minlength="100" maxlength="3000" @input="len = $event.target.value.length" class="{{ $input }} @error('motivation') border-red-600 @enderror">{{ old('motivation') }}</textarea>
                            <p class="mt-1 text-xs" :class="len < 100 ? 'text-gray-500' : 'text-green-700'"><span x-text="len"></span> / minimal 100 karakter</p>
                            @error('motivation')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="mb-4">
                            <label for="f-info_source" class="mb-1 block text-sm font-semibold">Tahu program ini dari<span class="text-red-700"> *</span></label>
                            <select id="f-info_source" name="info_source" required class="{{ $input }}"><option value="">— Pilih —</option>@foreach (App\Models\Applicant::SOURCES as $v)<option @selected(old('info_source') === $v)>{{ $v }}</option>@endforeach</select>
                            @error('info_source')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="mb-4">
                            <label for="f-health_history" class="mb-1 block text-sm font-semibold">Riwayat kesehatan / penyakit khusus (bila ada)</label>
                            <textarea id="f-health_history" name="health_history" rows="3" maxlength="1000" class="{{ $input }}">{{ old('health_history') }}</textarea>
                        </div>
                    </fieldset>

                    {{-- 5. Berkas --}}
                    <fieldset x-ref="s4" x-show="step === 4" x-cloak>
                        <legend class="mb-2 font-display text-xl text-brand-primary">Berkas</legend>
                        <p class="mb-5 text-sm text-gray-600">Format jpg, png, atau pdf, maksimal 2 MB per berkas. Berkas hanya dilihat panitia.</p>
                        @foreach (App\Models\Applicant::DOCUMENTS as $key => [$label, $required])
                            <div class="mb-4">
                                <label for="f-doc-{{ $key }}" class="mb-1 block text-sm font-semibold">{{ $label }}@if ($required)<span class="text-red-700"> *</span>@endif</label>
                                <input id="f-doc-{{ $key }}" type="file" name="documents[{{ $key }}]" accept=".jpg,.jpeg,.png,.pdf" @required($required)
                                    class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-brand-section-bg file:px-4 file:py-2 file:font-semibold file:text-brand-primary">
                                @error("documents.{$key}")<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </fieldset>

                    {{-- 6. Pernyataan --}}
                    <fieldset x-ref="s5" x-show="step === 5" x-cloak class="space-y-4">
                        <legend class="mb-4 font-display text-xl text-brand-primary">Pernyataan</legend>
                        @foreach (['agree_dorm' => 'Saya bersedia tinggal di asrama selama program.', 'agree_pact' => 'Saya bersedia menandatangani pakta integritas dan menaati tata tertib Rumah Mantaaba.', 'agree_privacy' => 'Saya menyetujui pengolahan data sesuai Kebijakan Privasi.'] as $field => $label)
                            <div>
                                <label class="flex items-start gap-3"><input type="checkbox" name="{{ $field }}" value="1" required @checked(old($field)) class="mt-1 rounded border-brand-border text-brand-primary focus:ring-brand-primary">
                                    <span>{{ $label }}@if ($field === 'agree_privacy') <a href="{{ route('site.page', 'kebijakan-privasi') }}" target="_blank" class="text-brand-accent underline">Baca</a>@endif</span></label>
                                @error($field)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </fieldset>

                    <div class="mt-8 flex justify-between gap-3">
                        <x-site.button type="button" variant="outline" x-show="step > 0" x-cloak @click="step--">Kembali</x-site.button>
                        <span x-show="step === 0"></span>
                        <x-site.button type="button" x-show="step < total - 1" @click="next()">Lanjut</x-site.button>
                        <x-site.button x-show="step === total - 1" x-cloak>Kirim Pendaftaran</x-site.button>
                    </div>
                </form>
            @endunless
        </div>
    </div>
</x-site.layout>
