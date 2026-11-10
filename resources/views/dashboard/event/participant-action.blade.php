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
</div>
