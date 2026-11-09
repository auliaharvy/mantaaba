@if ($report->resolved_at)
    <span class="text-muted fs-7">Ditangani {{ $report->resolved_at->translatedFormat('d M Y') }}</span>
@else
    <form method="POST" action="{{ route('report.resolve', $report) }}" class="d-flex justify-content-end gap-2">
        @csrf @method('PATCH')
        <button class="btn btn-sm btn-light" type="submit">Abaikan</button>
        @if ($report->reportable && ! $report->reportable->trashed())
            <button class="btn btn-sm btn-light-danger" type="submit" name="take_down" value="1" onclick="return confirm('Turunkan konten ini?')">
                {{ $report->reportable instanceof App\Models\Thread ? 'Sembunyikan diskusi' : 'Hapus komentar' }}</button>
        @endif
    </form>
@endif
