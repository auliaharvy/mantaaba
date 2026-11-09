<div id="komentar-{{ $comment->id }}" class="scroll-mt-28 rounded-lg border border-brand-border bg-white p-4">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm">
        <span><strong class="text-brand-heading">{{ $comment->user->name }}</strong> <span class="text-gray-500">· {{ $comment->created_at->diffForHumans() }}</span></span>
        <span class="flex gap-3">
            @can('update', $comment)<a href="{{ route('comment.edit', $comment) }}" class="text-brand-secondary">Ubah</a>@endcan
            @can('delete', $comment)
                <form method="POST" action="{{ route('comment.destroy', $comment) }}" onsubmit="return confirm('Hapus komentar ini?')">@csrf @method('DELETE')<button class="text-red-700">Hapus</button></form>
            @endcan
        </span>
    </div>
    <p class="whitespace-pre-line text-gray-800">{{ $comment->body }}</p>
    <div class="mt-2">@include('site.forum._report', ['type' => 'comment', 'id' => $comment->id])</div>
</div>
