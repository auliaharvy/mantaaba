{{-- Lencana 5 skema: ✓ lulus · ● berjalan · ✕ belum lulus · ○ terkunci --}}
@php($symbols = ['lulus' => ['✓', 'success'], 'berjalan' => ['●', 'primary'], 'belum_lulus' => ['✕', 'danger'], 'terkunci' => ['○', 'light']])
<div class="d-flex flex-wrap gap-1">
    @foreach ($enrollments as $e)
        @php([$symbol, $color] = $symbols[$e->status])
        <span class="badge badge-{{ $color }}" title="{{ $e->skema->name }}: {{ App\Models\SkemaEnrollment::STATUSES[$e->status] }}">{{ $e->skema->code }} {{ $symbol }}</span>
    @endforeach
</div>
