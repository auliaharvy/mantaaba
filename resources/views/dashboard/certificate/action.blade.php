<div class="d-flex justify-content-end gap-1">
    @if ($certificate->file)<a href="{{ route('certificate.download', $certificate) }}" class="btn btn-sm btn-icon btn-light-primary" title="Unduh"><i class="fa fa-file-pdf"></i></a>@endif
    @if (! $certificate->revoked_at)
        @can('certificate-revoke')
            <form method="POST" action="{{ route('certificate.revoke', $certificate) }}" onsubmit="const r = prompt('Alasan pencabutan {{ $certificate->number }}:'); if (!r) return false; this.reason.value = r;">
                @csrf @method('PATCH') <input type="hidden" name="reason">
                <button class="btn btn-sm btn-icon btn-light-warning" title="Cabut"><i class="fa fa-ban"></i></button></form>
        @endcan
    @else
        @can('certificate-issue')
            <form method="POST" action="{{ route('certificate.reissue', $certificate) }}">@csrf<button class="btn btn-sm btn-icon btn-light-info" title="Terbitkan ulang (nomor baru)"><i class="fa fa-redo"></i></button></form>
        @endcan
    @endif
</div>
