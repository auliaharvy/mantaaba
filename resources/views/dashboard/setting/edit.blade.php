<x-dashboard.page title="Pengaturan Aplikasi" :crumbs="['Pengaturan', 'Aplikasi']">
    <form method="POST" action="{{ route('setting.update', $setting) }}" enctype="multipart/form-data">
        @csrf @method('PATCH')
        <div class="row g-5">
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Identitas & Kontak</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="site_name" label="Nama situs" :value="$setting->site_name" required />
                        <x-dashboard.field name="tagline" label="Tagline / deskripsi singkat (footer & SEO)" :value="$setting->tagline" />
                        <div class="row">
                            <div class="col-md-6"><x-dashboard.field name="email" label="Email sekretariat (penerima pesan kontak)" type="email" :value="$setting->email" required /></div>
                            <div class="col-md-6"><x-dashboard.field name="phone" label="Telepon" :value="$setting->phone" /></div>
                            <div class="col-md-6"><x-dashboard.field name="whatsapp" label="WhatsApp" :value="$setting->whatsapp" placeholder="0812..." /></div>
                        </div>
                        <x-dashboard.field name="address" label="Alamat sekretariat" :value="$setting->address" />
                        <x-dashboard.field name="maps_embed" label="URL embed Google Maps" type="textarea" :value="$setting->maps_embed" help="Google Maps → Bagikan → Sematkan peta → salin isi atribut src." />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Logo, Media Sosial & Tautan Pusat</div></div>
                    <div class="card-body">
                        <div class="row">
                            @foreach (['logo' => 'Logo header', 'logo_footer' => 'Logo footer (latar gelap)', 'favicon' => 'Favicon (png)'] as $field => $label)
                                <div class="col-md-4">
                                    <x-dashboard.field :name="$field" :label="$label" type="file" accept="image/*" />
                                    <img src="{{ $setting->imageUrl($field) }}" alt="" class="h-40px mb-6 {{ $field === 'logo_footer' ? 'bg-dark p-1 rounded' : '' }}">
                                </div>
                            @endforeach
                        </div>
                        <div class="row">
                            @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn'] as $field => $label)
                                <div class="col-md-6"><x-dashboard.field :name="$field" :label="$label" type="url" :value="$setting->{$field}" placeholder="https://" /></div>
                            @endforeach
                        </div>
                        <x-dashboard.field name="url_registration" label="URL Pendaftaran Anggota (pusat)" type="url" :value="$setting->url_registration" required />
                        <x-dashboard.field name="url_member_service" label="URL Pelayanan Anggota (pusat)" type="url" :value="$setting->url_member_service" required />
                        <x-dashboard.field name="url_ejournal" label="URL eJurnal pusat" type="url" :value="$setting->url_ejournal" />
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Iuran & Pembayaran</div></div>
                    <div class="card-body row">
                        @foreach (App\Models\Member::TYPES as $type => $label)
                            <div class="col-sm-6 col-lg-3">
                                <x-dashboard.field name="dues_amounts[{{ $type }}]" :label="'Iuran tahunan ' . $label . ' (Rp)'" type="number" min="0" :value="$setting->dues_amounts[$type] ?? null" />
                            </div>
                        @endforeach
                        <div class="col-12"><x-dashboard.field name="bank_account_info" label="Info rekening (untuk event berbayar)" type="textarea" :value="$setting->bank_account_info" /></div>
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan pengaturan</button></div>
                </div>
            </div>
        </div>
    </form>
</x-dashboard.page>
