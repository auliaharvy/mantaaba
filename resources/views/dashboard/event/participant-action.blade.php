<div class="d-flex justify-content-end flex-wrap gap-1">
    @if ($wa = App\Models\Setting::waLink($participant->phone()))
        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm btn-icon btn-light-success" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
    @endif
    @can('update', $participant)
        @foreach (['hadir' => ['fa-check', 'success', 'Hadir'], 'terdaftar' => ['fa-undo', 'light', 'Terdaftar'], 'batal' => ['fa-times', 'danger', 'Batal']] as $status => [$icon, $color, $label])
            @continue($participant->status === $status)
            <form method="POST" action="{{ route('participant.status', $participant) }}">@csrf @method('PATCH')
                <button class="btn btn-sm btn-icon btn-light-{{ $color }}" name="status" value="{{ $status }}" title="Tandai {{ $label }}"><i class="fa {{ $icon }}"></i></button></form>
        @endforeach
    @endcan
</div>
