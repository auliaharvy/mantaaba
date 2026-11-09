{{-- Profil -> Tulisan saya (PRD 8.11) + preferensi notifikasi forum (8.7) --}}
<div class="row g-5">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <div class="card-title fs-4 fw-bolder">Berita & Artikel Saya</div>
                @can('create', App\Models\Blog::class)<div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('blog.create') }}">Tulis</a></div>@endcan
            </div>
            <div class="card-body">
                @forelse ($writings['blogs'] as $blog)
                    <div class="d-flex justify-content-between border-bottom py-2 gap-3">
                        <a href="{{ route('blog.show', $blog) }}">{{ $blog->title }}</a>
                        <span class="badge badge-light flex-shrink-0">{{ App\Models\Blog::STATUSES[$blog->status] }}</span>
                    </div>
                @empty
                    <div class="text-muted">Belum ada tulisan.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <div class="card-title fs-4 fw-bolder">Diskusi Saya</div>
                @can('create', App\Models\Thread::class)<div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('thread.create') }}">Diskusi baru</a></div>@endcan
            </div>
            <div class="card-body">
                @forelse ($writings['threads'] as $thread)
                    <div class="d-flex justify-content-between border-bottom py-2 gap-3">
                        <a href="{{ route('site.forum.show', $thread->slug) }}">{{ $thread->title }}</a>
                        @if ($thread->status === 'hidden')<span class="badge badge-light-danger flex-shrink-0">Disembunyikan</span>@endif
                    </div>
                @empty
                    <div class="text-muted">Belum ada diskusi.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-12">
        <form method="POST" action="{{ route('profile.notifications') }}" class="card">
            @csrf @method('PATCH')
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <label class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="notify_forum_replies" value="1" @checked($user->notify_forum_replies)>
                    <span class="form-check-label">Kirim email bila ada balasan di diskusi saya</span>
                </label>
                <button class="btn btn-sm btn-primary" type="submit">Simpan preferensi</button>
            </div>
        </form>
    </div>
</div>
