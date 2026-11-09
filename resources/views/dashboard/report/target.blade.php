@php($target = $report->reportable)
@if (! $target)
    <span class="text-muted">Konten sudah dihapus permanen</span>
@else
    @php($thread = $target instanceof App\Models\Thread ? $target : $target->thread)
    <div class="fs-7 text-muted">{{ $target instanceof App\Models\Thread ? 'Diskusi' : 'Komentar' }} oleh {{ $target->user?->name }}{{ $target->trashed() ? ' (dihapus)' : '' }}</div>
    <a href="{{ route('site.forum.show', $thread->slug) }}{{ $target instanceof App\Models\Comment ? '#komentar-' . $target->id : '' }}" target="_blank">
        {{ Str::limit($target instanceof App\Models\Thread ? $target->title : strip_tags($target->body), 80) }}</a>
@endif
