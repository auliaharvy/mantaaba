<x-dashboard.page :title="$blog->title" :crumbs="['Postingan', 'Berita & Artikel', 'Pratinjau']">
    <div class="card">
        <div class="card-header">
            <div class="card-title flex-column align-items-start">
                <span class="fs-3 fw-bolder">{{ $blog->title }}</span>
                <span class="text-muted fs-7 mt-1">{{ $blog->user->name }} · {{ App\Models\Blog::STATUSES[$blog->status] }}{{ $blog->category ? ' · ' . $blog->category->name : '' }}</span>
            </div>
            <div class="card-toolbar gap-2">
                @if ($blog->status === 'published')<a class="btn btn-sm btn-light" href="{{ route('site.news.show', $blog->slug) }}" target="_blank"><i class="fa fa-external-link-alt"></i> Lihat di situs</a>@endif
                @can('update', $blog)<a class="btn btn-sm btn-light-success" href="{{ route('blog.edit', $blog) }}"><i class="fa fa-pen"></i> {{ auth()->user()->can('publish', App\Models\Blog::class) && $blog->status === 'draft' ? 'Tinjau & terbitkan' : 'Ubah' }}</a>@endcan
                <a class="btn btn-sm btn-light-primary" href="{{ route('blog.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
        <div class="card-body">
            @if ($blog->thumbnail)<img src="{{ Storage::disk('public')->url($blog->thumbnail) }}" alt="" class="mw-100 rounded mb-6" style="max-height: 360px">@endif
            <div class="fs-6">{!! $blog->body !!}</div>
            @if ($blog->tags->isNotEmpty())
                <div class="mt-6">@foreach ($blog->tags as $tag)<span class="badge badge-light me-1">#{{ $tag->name }}</span>@endforeach</div>
            @endif
        </div>
    </div>
</x-dashboard.page>
