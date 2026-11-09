<x-site.layout :title="$thread->title" :description="Str::limit(trim(strip_tags($thread->body)), 160)" type="article">
    <x-site.breadcrumb-header :title="$thread->title" :crumbs="['Forum' => route('site.forum'), Str::limit($thread->title, 40)]" />
    <div class="container-site py-12">
        <div class="mx-auto max-w-3xl">
            @if (session('success'))<x-site.alert class="mb-6">{{ session('success') }}</x-site.alert>@endif
            @if ($thread->status === 'hidden')<x-site.alert type="error" class="mb-6">Diskusi ini disembunyikan moderator dan hanya terlihat oleh Anda & moderator.</x-site.alert>@endif

            <article class="rounded-lg border border-brand-border bg-white p-6">
                <p class="mb-4 flex flex-wrap gap-x-3 text-sm text-gray-600">
                    <strong class="text-brand-heading">{{ $thread->user->name }}</strong>
                    <time datetime="{{ $thread->created_at->toIso8601String() }}">{{ $thread->created_at->translatedFormat('j F Y H:i') }}</time>
                    @if ($thread->category)<span>{{ $thread->category->name }}</span>@endif
                    <span>{{ $thread->views }}× dilihat</span>
                </p>
                <div class="prose-cms">{!! $thread->body !!}</div>
                <div class="mt-4 flex items-center justify-between gap-3">
                    @include('site.forum._report', ['type' => 'thread', 'id' => $thread->id])
                    @can('update', $thread)<a href="{{ route('thread.edit', $thread) }}" class="text-sm font-semibold text-brand-secondary">Ubah diskusi</a>@endcan
                </div>
            </article>

            <h2 class="mb-4 mt-10 text-xl">{{ $comments->total() }} komentar</h2>
            <div class="space-y-4">
                @foreach ($comments as $comment)
                    @include('site.forum._comment', ['comment' => $comment])
                    @if ($comment->replies->isNotEmpty())
                        <div class="ml-6 space-y-3 border-l-2 border-brand-border pl-4 sm:ml-10">
                            @foreach ($comment->replies as $reply)@include('site.forum._comment', ['comment' => $reply])@endforeach
                        </div>
                    @endif
                    @auth
                        @can('reply', $thread)
                            <details class="ml-6 sm:ml-10">
                                <summary class="cursor-pointer list-none text-sm font-semibold text-brand-secondary">Balas komentar ini</summary>
                                <form method="POST" action="{{ route('site.forum.comment', $thread->slug) }}" class="mt-2">
                                    @csrf <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                    <label class="sr-only" for="reply-{{ $comment->id }}">Balasan</label>
                                    <textarea id="reply-{{ $comment->id }}" name="body" rows="3" required maxlength="5000" class="w-full rounded-md border-brand-border text-sm"></textarea>
                                    <x-site.button size="sm" class="mt-2">Kirim balasan</x-site.button>
                                </form>
                            </details>
                        @endcan
                    @endauth
                @endforeach
            </div>
            <div class="mt-6">{{ $comments->links() }}</div>

            <section class="mt-10 rounded-lg border border-brand-border bg-brand-section-bg p-6">
                @if ($thread->is_locked)
                    <p class="font-semibold text-gray-700">Diskusi ini dikunci. Komentar baru tidak bisa ditambahkan.</p>
                @elseif (! auth()->check())
                    <p><a href="{{ route('login') }}" class="font-semibold text-brand-secondary underline">Masuk</a> untuk ikut berkomentar.</p>
                @elseif (auth()->user()->can('reply', $thread))
                    <form method="POST" action="{{ route('site.forum.comment', $thread->slug) }}">
                        @csrf
                        <label for="body" class="mb-2 block font-bold text-brand-heading">Tulis komentar</label>
                        <textarea id="body" name="body" rows="4" required maxlength="5000" class="w-full rounded-md border-brand-border focus:border-brand-primary focus:ring-brand-primary">{{ old('body') }}</textarea>
                        @error('body')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        <x-site.button class="mt-3">Kirim</x-site.button>
                    </form>
                @else
                    <p class="text-gray-700">Hanya anggota yang dapat menulis di forum.</p>
                @endif
            </section>
        </div>
    </div>
</x-site.layout>
