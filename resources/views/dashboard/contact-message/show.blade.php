<x-dashboard.page title="Pesan Masuk" :crumbs="['Website', 'Pesan Masuk', 'Rincian']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">{{ $message->subject }}</div>
            <div class="card-toolbar gap-2">
                <a class="btn btn-sm btn-light-success" href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: ' . $message->subject) }}"><i class="fa fa-reply"></i> Balas via email</a>
                <a class="btn btn-sm btn-light-primary" href="{{ route('contact-message.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3 text-muted">Dari</dt><dd class="col-sm-9">{{ $message->name }} &lt;{{ $message->email }}&gt;</dd>
                <dt class="col-sm-3 text-muted">Telepon</dt><dd class="col-sm-9">{{ $message->phone ?? '-' }}</dd>
                <dt class="col-sm-3 text-muted">Kota</dt><dd class="col-sm-9">{{ $message->city ?? '-' }}</dd>
                <dt class="col-sm-3 text-muted">Dikirim</dt><dd class="col-sm-9">{{ $message->created_at->translatedFormat('l, j F Y H:i') }}</dd>
            </dl>
            <div class="border rounded p-5 bg-light" style="white-space: pre-line">{{ $message->message }}</div>
        </div>
    </div>
</x-dashboard.page>
