<x-dashboard.page title="Ubah Komentar" :crumbs="['Postingan', 'Forum', 'Ubah komentar']">
    <form method="POST" action="{{ route('comment.update', $comment) }}" class="card">
        @csrf @method('PATCH')
        <div class="card-header">
            <div class="card-title fs-4 fw-bolder">Komentar di "{{ $comment->thread->title }}"</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('site.forum.show', $comment->thread->slug) }}"><i class="fa fa-arrow-left"></i> Kembali</a></div>
        </div>
        <div class="card-body"><x-dashboard.field name="body" label="Komentar" type="textarea" :value="$comment->body" required rows="6" /></div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
    </form>
</x-dashboard.page>
