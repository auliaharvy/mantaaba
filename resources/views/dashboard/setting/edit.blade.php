{{-- Setting situs (PRD 9.1.1) dalam tab; satu form. --}}
@php
    $tabs = ['identitas' => 'Identitas', 'pemeliharaan' => 'Mode Dibangun', 'tampilan' => 'Tampilan', 'kontak' => 'Kontak & Peta', 'sosmed' => 'Media Sosial', 'donasi' => 'Donasi',
        'pendaftaran' => 'Pendaftaran', 'akademik' => 'Akademik', 'operasional' => 'Operasional & Akuntansi', 'lainnya' => 'Lainnya'];
    $active = array_key_exists(request('tab'), $tabs) ? request('tab') : 'identitas';
    $weights = old('assessment_weights', $setting->assessment_weights ?? App\Models\Setting::DEFAULT_WEIGHTS);
    $banks = old('bank_accounts', $setting->bank_accounts ?? []);
    $image = fn ($field) => $setting->{$field} ? Storage::disk('public')->url($setting->{$field}) : null;
@endphp
<x-dashboard.page title="Setting Situs" :crumbs="['Website', 'Setting Situs']">
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('setting.update') }}" enctype="multipart/form-data" class="card">
        @csrf @method('PATCH')
        <input type="hidden" name="_tab" id="setting-tab" value="{{ $active }}">
        <div class="card-header card-header-stretch overflow-auto">
            <ul class="nav nav-stretch nav-line-tabs border-transparent flex-nowrap fs-6 fw-bold" role="tablist">
                @foreach ($tabs as $key => $label)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link text-nowrap {{ $key === $active ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-{{ $key }}" data-tab="{{ $key }}" role="tab">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body tab-content">
            <div class="tab-pane fade {{ $active === 'identitas' ? 'show active' : '' }}" id="tab-identitas" role="tabpanel">
                <div class="row">
                    <div class="col-md-6"><x-dashboard.field name="site_name" label="Nama lembaga" :value="$setting->site_name" required /></div>
                    <div class="col-md-6"><x-dashboard.field name="foundation_name" label="Nama yayasan" :value="$setting->foundation_name" required /></div>
                    <div class="col-12"><x-dashboard.field name="tagline" label="Tagline (footer & judul beranda)" :value="$setting->tagline" /></div>
                    <div class="col-12"><x-dashboard.field name="meta_description" label="Deskripsi SEO bawaan" type="textarea" :value="$setting->meta_description" help="Dipakai halaman yang tidak punya deskripsi sendiri (maks. 300 karakter)." /></div>
                    @foreach (['logo' => 'Logo header', 'logo_footer' => 'Logo footer', 'favicon' => 'Favicon (png 256px)', 'og_image' => 'Gambar pratinjau tautan (1200×630)'] as $field => $label)
                        <div class="col-md-3">
                            <x-dashboard.field :name="$field" :label="$label" type="file" accept="image/*" />
                            <img src="{{ $setting->imageUrl($field) }}" alt="" class="h-50px mb-6 rounded">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tab-pane fade {{ $active === 'tampilan' ? 'show active' : '' }}" id="tab-tampilan" role="tabpanel">
                <p class="text-muted fs-7">Warna situs publik langsung berubah setelah disimpan. Emas hanya untuk hiasan; teks memakai cokelat/marun agar terbaca.</p>
                <div class="row">
                    @foreach (['color_primary' => 'Primer (header, tombol, judul)', 'color_secondary' => 'Sekunder (emas, ornamen)', 'color_accent' => 'Aksen (tautan, CTA)', 'color_surface' => 'Latar section (krem)'] as $field => $label)
                        <div class="col-md-3 mb-6">
                            <label class="form-label" for="field-{{ $field }}">{{ $label }}</label>
                            <div class="d-flex gap-2">
                                <input type="color" class="form-control form-control-color" value="{{ old($field, $setting->{$field}) }}" oninput="this.nextElementSibling.value = this.value.toUpperCase()" aria-label="Pilih warna {{ $label }}">
                                <input type="text" class="form-control @error($field) is-invalid @enderror" id="field-{{ $field }}" name="{{ $field }}" value="{{ old($field, $setting->{$field}) }}" maxlength="7" oninput="if (/^#[0-9a-f]{6}$/i.test(this.value)) this.previousElementSibling.value = this.value">
                            </div>
                            <div class="form-text">Bawaan {{ App\Models\Setting::COLORS[$field] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tab-pane fade {{ $active === 'kontak' ? 'show active' : '' }}" id="tab-kontak" role="tabpanel">
                <div class="row">
                    <div class="col-12"><x-dashboard.field name="address" label="Alamat (jalan, RT/RW)" :value="$setting->address" /></div>
                    <div class="col-md-4"><x-dashboard.field name="village" label="Desa/Kelurahan" :value="$setting->village" /></div>
                    <div class="col-md-4"><x-dashboard.field name="district" label="Kecamatan" :value="$setting->district" /></div>
                    <div class="col-md-4"><x-dashboard.field name="regency" label="Kabupaten/Kota" :value="$setting->regency" /></div>
                    <div class="col-md-4"><x-dashboard.field name="province" label="Provinsi" :value="$setting->province" /></div>
                    <div class="col-md-2"><x-dashboard.field name="postal_code" label="Kode pos" :value="$setting->postal_code" /></div>
                    <div class="col-md-3"><x-dashboard.field name="latitude" label="Latitude" :value="$setting->latitude" placeholder="-6.9" /></div>
                    <div class="col-md-3"><x-dashboard.field name="longitude" label="Longitude" :value="$setting->longitude" placeholder="107.6" /></div>
                    <div class="col-12"><x-dashboard.field name="maps_embed" label="URL embed Google Maps" type="textarea" :value="$setting->maps_embed" help="Google Maps → Bagikan → Sematkan peta → salin isi atribut src." /></div>
                    <div class="col-md-3"><x-dashboard.field name="phone" label="Telepon" :value="$setting->phone" /></div>
                    <div class="col-md-3"><x-dashboard.field name="whatsapp" label="WhatsApp" :value="$setting->whatsapp" placeholder="0812..." /></div>
                    <div class="col-md-3"><x-dashboard.field name="email" label="Email (penerima pesan kontak)" type="email" :value="$setting->email" /></div>
                    <div class="col-md-3"><x-dashboard.field name="office_hours" label="Jam layanan" :value="$setting->office_hours" placeholder="Senin–Sabtu, 08.00–16.00" /></div>
                </div>
            </div>

            <div class="tab-pane fade {{ $active === 'sosmed' ? 'show active' : '' }}" id="tab-sosmed" role="tabpanel">
                <div class="row">
                    @foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn'] as $field => $label)
                        <div class="col-md-6"><x-dashboard.field :name="$field" :label="$label" type="url" :value="$setting->{$field}" placeholder="https://" /></div>
                    @endforeach
                </div>
            </div>

            <div class="tab-pane fade {{ $active === 'donasi' ? 'show active' : '' }}" id="tab-donasi" role="tabpanel">
                <label class="form-label">Rekening donasi (maks. 3)</label>
                @for ($i = 0; $i < 3; $i++)
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><input class="form-control @error("bank_accounts.{$i}.bank") is-invalid @enderror" name="bank_accounts[{{ $i }}][bank]" value="{{ $banks[$i]['bank'] ?? '' }}" placeholder="Bank" aria-label="Bank {{ $i + 1 }}"></div>
                        <div class="col-md-4"><input class="form-control @error("bank_accounts.{$i}.number") is-invalid @enderror" name="bank_accounts[{{ $i }}][number]" value="{{ $banks[$i]['number'] ?? '' }}" placeholder="Nomor rekening" aria-label="Nomor rekening {{ $i + 1 }}"></div>
                        <div class="col-md-5"><input class="form-control @error("bank_accounts.{$i}.holder") is-invalid @enderror" name="bank_accounts[{{ $i }}][holder]" value="{{ $banks[$i]['holder'] ?? '' }}" placeholder="Atas nama" aria-label="Atas nama {{ $i + 1 }}"></div>
                    </div>
                @endfor
                <div class="row mt-6">
                    <div class="col-md-4">
                        <x-dashboard.field name="qris_image" label="Gambar QRIS" type="file" accept="image/*" />
                        @if ($image('qris_image'))<img src="{{ $image('qris_image') }}" alt="" class="h-100px mb-6 rounded">@endif
                    </div>
                    <div class="col-md-8"><x-dashboard.field name="donation_note" label="Catatan donasi" type="textarea" :value="$setting->donation_note" /></div>
                </div>
            </div>

            <div class="tab-pane fade {{ $active === 'pendaftaran' ? 'show active' : '' }}" id="tab-pendaftaran" role="tabpanel">
                <div class="mb-6">
                    <label class="form-label" for="field-admission_requirements">Persyaratan pendaftaran (tampil di /pendaftaran)</label>
                    <textarea id="field-admission_requirements" name="admission_requirements">{{ old('admission_requirements', $setting->admission_requirements) }}</textarea>
                </div>
                <div class="row">
                    @foreach (['parent_consent_template' => 'Template surat persetujuan orang tua', 'integrity_pact_template' => 'Template pakta integritas'] as $field => $label)
                        <div class="col-md-6">
                            <x-dashboard.field :name="$field" :label="$label . ' (pdf/docx)'" type="file" accept=".pdf,.doc,.docx" />
                            @if ($image($field))<a href="{{ $image($field) }}" target="_blank" class="fs-7 d-block mb-6">Lihat file saat ini</a>@endif
                        </div>
                    @endforeach
                    <div class="col-12"><x-dashboard.field name="integrity_pact_text" label="Teks pakta integritas (ditampilkan saat siswa baru login pertama)" type="textarea" rows="8" :value="$setting->integrity_pact_text" /></div>
                    <div class="col-12"><x-dashboard.field name="priority_districts" label="Kecamatan prioritas (satu per baris)" type="textarea" :value="implode(PHP_EOL, $setting->priority_districts ?? [])" help="Dipakai menandai pendaftar prioritas lokal (M2)." /></div>
                </div>
            </div>

            <div class="tab-pane fade {{ $active === 'akademik' ? 'show active' : '' }}" id="tab-akademik" role="tabpanel">
                <label class="form-label">Bobot penilaian 4 dimensi (total 100%)</label>
                <div class="row">
                    @foreach (['knowledge' => 'Knowledge', 'skill' => 'Skill', 'character' => 'Character', 'social' => 'Social Cohesion'] as $key => $label)
                        <div class="col-md-3 mb-6">
                            <div class="input-group">
                                <span class="input-group-text">{{ $label }}</span>
                                <input type="number" min="0" max="100" class="form-control @error('assessment_weights') is-invalid @enderror" name="assessment_weights[{{ $key }}]" value="{{ $weights[$key] ?? 0 }}" aria-label="Bobot {{ $label }}">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="row">
                    <div class="col-md-3"><x-dashboard.field name="default_pass_score" label="Nilai lulus bawaan" type="number" min="0" max="100" :value="$setting->default_pass_score" required /></div>
                    <div class="col-md-3"><x-dashboard.field name="min_character_score" label="Nilai minimum Character" type="number" min="0" max="100" :value="$setting->min_character_score" required /></div>
                </div>
            </div>

            <div class="tab-pane fade {{ $active === 'operasional' ? 'show active' : '' }}" id="tab-operasional" role="tabpanel">
                <div class="row">
                    <div class="col-md-4"><x-dashboard.field name="meal_budget_per_person_day" label="Anggaran makan per orang per hari (Rp)" type="number" min="0" step="1" :value="$setting->meal_budget_per_person_day" /></div>
                    <div class="col-md-4"><x-dashboard.field name="default_residents" label="Jumlah penghuni bawaan (perencanaan menu)" type="number" min="0" :value="$setting->default_residents" /></div>
                    <div class="col-md-4"><x-dashboard.field name="accounting_start_date" label="Tanggal mulai pembukuan" type="date" :value="$setting->accounting_start_date" /></div>
                    <div class="col-md-6"><x-dashboard.field name="report_signatories[ketua]" label="Penandatangan laporan: Ketua" :value="$setting->report_signatories['ketua'] ?? null" /></div>
                    <div class="col-md-6"><x-dashboard.field name="report_signatories[bendahara]" label="Penandatangan laporan: Bendahara" :value="$setting->report_signatories['bendahara'] ?? null" /></div>
                </div>
                <p class="text-muted fs-7">Voucher selalu disetujui oleh orang yang berbeda dari pembuatnya (aturan sistem, tidak bisa dimatikan).</p>
            </div>

            <div class="tab-pane fade {{ $active === 'pemeliharaan' ? 'show active' : '' }}" id="tab-pemeliharaan" role="tabpanel">
                <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                    <input type="hidden" name="maintenance_mode" value="0">
                    <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" id="field-maintenance_mode" @checked(old('maintenance_mode', $setting->maintenance_mode))>
                    <label class="form-check-label fw-semibold" for="field-maintenance_mode">Tampilkan halaman "Sedang Dibangun" kepada pengunjung</label>
                </div>
                <p class="text-muted fs-7 mb-8">Semua halaman situs publik (termasuk pendaftaran & donasi) diganti halaman under construction. Login dan dashboard tetap berjalan; pengguna yang sudah login tetap melihat situs untuk meninjau isinya.
                    <a href="{{ route('site.home') }}" target="_blank" rel="noopener">Lihat situs</a></p>
                <div class="row">
                    <div class="col-md-8"><x-dashboard.field name="maintenance_title" label="Judul" :value="$setting->maintenance_title" placeholder="Situs sedang dalam pembangunan" /></div>
                    <div class="col-md-4"><x-dashboard.field name="maintenance_until" label="Perkiraan selesai (opsional)" type="date" :value="$setting->maintenance_until" /></div>
                    <div class="col-12"><x-dashboard.field name="maintenance_message" label="Pesan untuk pengunjung" type="textarea" :value="$setting->maintenance_message" help="Kosong = pesan bawaan. Kontak (WhatsApp, email) dan media sosial dari tab Kontak & Media Sosial ikut ditampilkan." /></div>
                </div>
            </div>

            <div class="tab-pane fade {{ $active === 'lainnya' ? 'show active' : '' }}" id="tab-lainnya" role="tabpanel">
                <x-dashboard.field name="footer_text" label="Teks footer" type="textarea" :value="$setting->footer_text" help="Kosong = memakai tagline." />
                <p class="text-muted fs-7">Kebijakan privasi diubah di menu <a href="{{ route('page.edit', 'kebijakan-privasi') }}">Halaman → Kebijakan Privasi</a>.</p>
            </div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
    </form>
    <x-slot name="javascript">
        @include('dashboard.partials.tinymce', ['selector' => '#field-admission_requirements', 'height' => 360])
        <script>
            document.querySelectorAll('[data-tab]').forEach((el) => el.addEventListener('shown.bs.tab', () => document.getElementById('setting-tab').value = el.dataset.tab));
        </script>
    </x-slot>
</x-dashboard.page>
