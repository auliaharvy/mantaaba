<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('site.forum.show', $thread->slug) }}" class="btn btn-sm btn-icon btn-light-warning" title="Lihat" target="_blank"><i class="fa fa-search"></i></a>
    @can('moderate', App\Models\Thread::class)
        @foreach ([[$thread->status === 'hidden' ? 'show' : 'hide', $thread->status === 'hidden' ? 'fa-eye' : 'fa-eye-slash', $thread->status === 'hidden' ? 'Tampilkan' : 'Sembunyikan'],
                   [$thread->is_locked ? 'unlock' : 'lock', $thread->is_locked ? 'fa-lock-open' : 'fa-lock', $thread->is_locked ? 'Buka kunci' : 'Kunci'],
                   [$thread->is_pinned ? 'unpin' : 'pin', 'fa-thumbtack', $thread->is_pinned ? 'Lepas sematan' : 'Sematkan']] as [$action, $icon, $label])
            <form method="POST" action="{{ route('thread.moderate', $thread) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-icon btn-light-info" name="action" value="{{ $action }}" title="{{ $label }}" aria-label="{{ $label }}"><i class="fa {{ $icon }}"></i></button>
            </form>
        @endforeach
    @endcan
    @can('update', $thread)<a href="{{ route('thread.edit', $thread) }}" class="btn btn-sm btn-icon btn-light-success" title="Ubah"><i class="fa fa-pen"></i></a>@endcan
    @can('delete', $thread)<button type="button" class="btn btn-sm btn-icon btn-light-danger" title="Hapus" data-delete-url="{{ route('thread.destroy', $thread) }}"><i class="fa fa-trash"></i></button>@endcan
</div>
