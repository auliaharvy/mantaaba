<x-site.layout :title="$page->meta_title ?: $page->title" :description="$page->meta_description ?: ($page->excerpt ?: strip_tags($page->body))"
    :image="$page->cover ? Storage::disk('public')->url($page->cover) : null">
    <x-site.breadcrumb-header :title="$page->title" :crumbs="$crumbs" />
    <div class="container-site py-12">
        <article class="mx-auto max-w-3xl">
            @if ($page->cover)<img src="{{ Storage::disk('public')->url($page->cover) }}" alt="" class="mb-8 w-full rounded-lg">@endif
            <div class="prose-cms">{!! $page->body !!}</div>
        </article>
    </div>
</x-site.layout>
