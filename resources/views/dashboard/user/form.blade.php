@php($editing = $target->exists)
<x-dashboard.page :title="$editing ? 'Ubah Pengguna' : 'Tambah Pengguna'" :crumbs="['Sistem', 'Pengguna', $editing ? 'Ubah' : 'Tambah']">
    @if (session('wa_link'))
        <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-3">
            <span>Tautan juga bisa dikirim lewat WhatsApp.</span>
            <a class="btn btn-sm btn-success" href="{{ session('wa_link') }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> Kirim via WhatsApp</a>
        </div>
    @endif
    <form method="POST" action="{{ $editing ? route('user.update', $target) : route('user.store') }}" class="card">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">{{ $editing ? $target->name : 'Pengguna baru' }}</div>
            <div class="card-toolbar gap-2">
                @if ($editing && auth()->user()->can('managePermissions', $target))
                    <a class="btn btn-sm btn-light-primary" href="{{ route('user.permissions', $target) }}"><i class="fa fa-key"></i> Hak akses</a>
                @endif
                <a class="btn btn-sm btn-light" href="{{ route('user.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
        <div class="card-body row">
            <div class="col-md-6"><x-dashboard.field name="name" label="Nama lengkap" :value="$target->name" required /></div>
            <div class="col-md-6"><x-dashboard.field name="email" label="Email (untuk masuk)" type="email" :value="$target->email" required /></div>
            <div class="col-md-6"><x-dashboard.field name="phone_number" label="WhatsApp" :value="$target->phone_number" placeholder="0812..." /></div>
            <div class="col-md-6">
                <x-dashboard.field name="role" label="Role" type="select" :options="$roles" :value="$target->getRoleNames()->first()" required
                    :help="$editing ? null : 'Pengurus baru otomatis mendapat paket Pengurus Inti; bisa diubah di layar Hak Akses.'" />
            </div>
            <div class="col-md-6"><x-dashboard.field name="password" label="Password {{ $editing ? 'baru (kosongkan bila tidak diganti)' : '(opsional)' }}" type="password" autocomplete="new-password" /></div>
            <div class="col-md-6"><x-dashboard.field name="password_confirmation" label="Ulangi password" type="password" autocomplete="new-password" /></div>
            <div class="col-12 form-check form-switch ps-14 mb-4">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $target->is_active))>
                <label class="form-check-label" for="is_active">Aktif (bisa masuk)</label>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
            <div>
                @if ($editing)
                    <button class="btn btn-sm btn-light-info" type="submit" form="password-link"><i class="fa fa-paper-plane"></i> Kirim tautan atur password</button>
                @endif
            </div>
            <button class="btn btn-sm btn-primary" type="submit">Simpan</button>
        </div>
    </form>
    @if ($editing)
        <form id="password-link" method="POST" action="{{ route('user.password-link', $target) }}">@csrf</form>
    @endif
</x-dashboard.page>
