<x-dashboard.page title="Setting Situs" :crumbs="['Website', 'Setting Situs']">
    <form method="POST" action="{{ route('setting.update') }}" enctype="multipart/form-data">
        @csrf @method('PATCH')
        <div class="row g-5">
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Identitas & Kontak</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="site_name" label="Nama situs" :value="$setting->site_name" required />
                        <x-dashboard.field name="tagline" label="Tagline / deskripsi singkat (footer & SEO)" :value="$setting->tagline" />
                        <div class="row">
                            <div class="col-md-6"><x-dashboard.field name="email" label="Email lembaga (penerima pesan kontak)" type="email" :value="$setting->email" /></div>
                            <div class="col-md-6"><x-dashboard.field name="phone" label="Telepon" :value="$setting->phone" /></div>
                            <div class="col-md-6"><x-dashboard.field name="whatsapp" label="WhatsApp" :value="$setting->whatsapp" placeholder="0812..." /></div>
                        </div>
                        <x-dashboard.field name="address" label="Alamat" :value="$setting->address" />
                        <x-dashboard.field name="maps_embed" label="URL embed Google Maps" type="textarea" :value="$setting->maps_embed" help="Google Maps → Bagikan → Sematkan peta → salin isi atribut src." />
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Logo & Media Sosial</div></div>
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
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Rekening</div></div>
                    <div class="card-body row">
                        <div class="col-12"><x-dashboard.field name="bank_account_info" label="Info rekening (donasi & agenda berbayar)" type="textarea" :value="$setting->bank_account_info" /></div>
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
                </div>
            </div>
        </div>
    </form>
</x-dashboard.page>
