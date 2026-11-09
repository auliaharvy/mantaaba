<div class="d-flex justify-content-end flex-wrap gap-1">
    @if ($participant->payment_proof)
        <a href="{{ route('participant.proof', $participant) }}" target="_blank" class="btn btn-sm btn-light" title="Bukti bayar"><i class="fa fa-receipt"></i></a>
    @endif
    @if ($participant->status !== 'verified')
        <form method="POST" action="{{ route('participant.status', $participant) }}">@csrf @method('PATCH')
            <button class="btn btn-sm btn-light-success" name="status" value="verified" title="Verifikasi"><i class="fa fa-check"></i></button></form>
    @endif
    @if (in_array($participant->status, ['pending', 'verified']))
        <form method="POST" action="{{ route('participant.status', $participant) }}" onsubmit="const r = prompt('Alasan penolakan:'); if (!r) return false; this.notes.value = r;">
            @csrf @method('PATCH') <input type="hidden" name="notes">
            <button class="btn btn-sm btn-light-danger" name="status" value="rejected" title="Tolak"><i class="fa fa-times"></i></button></form>
    @endif
    @if ($c = $participant->certificateRecord)
        @if ($c->file)<a href="{{ route('certificate.download', $c) }}" class="btn btn-sm btn-light-primary" title="Unduh sertifikat"><i class="fa fa-file-pdf"></i></a>@endif
        @if ($c->isValid())
            <form method="POST" action="{{ route('certificate.revoke', $c) }}" onsubmit="return confirm('Cabut sertifikat {{ $c->number }}?')">@csrf @method('PATCH')
                <button class="btn btn-sm btn-light-warning" title="Cabut sertifikat"><i class="fa fa-ban"></i></button></form>
        @else
            <form method="POST" action="{{ route('certificate.reissue', $c) }}">@csrf
                <button class="btn btn-sm btn-light-info" title="Terbitkan ulang"><i class="fa fa-redo"></i></button></form>
        @endif
    @endif
</div>
