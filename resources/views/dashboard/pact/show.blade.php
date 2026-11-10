<x-dashboard.page title="Pakta Integritas" :crumbs="['Pakta Integritas']">
    <form method="POST" action="{{ route('pact.sign') }}" class="card mw-800px">
        @csrf
        <div class="card-header"><div class="card-title fs-3 fw-bolder">Pakta Integritas Siswa</div></div>
        <div class="card-body">
            <p class="text-muted">Assalamu'alaikum {{ $member->name }}. Sebelum memakai sistem, baca dan setujui pakta integritas berikut.</p>
            <div class="border rounded p-6 bg-light mb-6" style="white-space: pre-line">{{ $text ?: "Saya yang bertanda tangan di bawah ini menyatakan bersedia:\n1. Menaati seluruh tata tertib Rumah Mantaaba Sigaling.\n2. Bersikap jujur dalam setiap amanah, termasuk pencatatan keuangan dan operasional.\n3. Menjaga adab kepada guru, pengurus, dan sesama siswa.\n4. Menjalankan tugas piket dan tanggung jawab bersama tanpa meninggalkan saudara ('Asabiyah).\n5. Menerima sanksi sesuai tata tertib bila melanggar." }}</div>
            <label class="form-check"><input class="form-check-input" type="checkbox" name="agree" value="1" required> <span class="form-check-label">Saya telah membaca dan menyetujui pakta integritas ini.</span></label>
            @error('agree')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
        </div>
        <div class="card-footer text-end"><button class="btn btn-primary">Setuju & lanjutkan</button></div>
    </form>
</x-dashboard.page>
