<x-site.layout :title="$category?->name ?? 'Forum Diskusi'" description="Forum diskusi anggota AKP2I Jawa Barat seputar perpajakan dan profesi konsultan pajak.">
    <x-site.breadcrumb-header :title="$category?->name ?? 'Forum Diskusi'" :crumbs="$category ? ['Forum' => route('site.forum'), $category->name] : ['Forum']" />
    <div class="container-site py-12">
        @if (session('success'))<x-site.alert class="mb-6">{{ session('success') }}</x-site.alert>@endif
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="flex flex-1 flex-wrap gap-2" role="search">
                @if ($category)<input type="hidden" name="kategori" value="{{ $category->slug }}">@endif
                <label for="q" class="sr-only">Cari diskusi</label>
                <input id="q" name="q" value="{{ $q }}" placeholder="Cari judul diskusi" class="min-w-0 flex-1 rounded-md border-brand-border text-sm focus:border-brand-primary focus:ring-brand-primary sm:max-w-sm">
                <x-site.button size="sm"><x-site.icon name="search" class="h-4 w-4" /> Cari</x-site.button>
            </form>
            @auth
                @can('create', App\Models\Thread::class)<x-site.button :href="route('thread.create')" size="sm">Mulai Diskusi</x-site.button>@endcan
            @else
                <p class="text-sm text-gray-600"><a href="{{ route('login') }}" class="font-semibold text-brand-secondary underline">Masuk</a> untuk ikut berdiskusi.</p>
            @endauth
        </div>
        @if ($categories->isNotEmpty())
            <nav class="mb-6 flex flex-wrap gap-2" aria-label="Kategori forum">
                <a href="{{ route('site.forum') }}" class="rounded-full px-3 py-1 text-sm {{ $category ? 'border border-brand-border' : 'bg-brand-secondary text-white' }}">Semua</a>
                @foreach ($categories as $c)
                    <a href="{{ route('site.forum', ['kategori' => $c->slug]) }}" class="rounded-full px-3 py-1 text-sm {{ $category?->is($c) ? 'bg-brand-secondary text-white' : 'border border-brand-border' }}">{{ $c->name }}</a>
                @endforeach
            </nav>
        @endif
        @if ($threads->isEmpty())
            <x-site.empty-state title="Belum ada diskusi" icon="chat" />
        @else
            <ul class="divide-y divide-brand-border rounded-lg border border-brand-border bg-white">
                @foreach ($threads as $thread)
                    <li class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2">
                                @if ($thread->is_pinned)<span class="rounded bg-brand-section-bg px-2 py-0.5 text-xs font-bold text-brand-secondary">Disematkan</span>@endif
                                @if ($thread->is_locked)<span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-700">Terkunci</span>@endif
                                <a href="{{ route('site.forum.show', $thread->slug) }}" class="font-bold text-brand-heading hover:text-brand-secondary">{{ $thread->title }}</a>
                            </p>
                            <p class="mt-1 text-sm text-gray-600">{{ $thread->user->name }}{{ $thread->category ? ' · ' . $thread->category->name : '' }}</p>
                        </div>
                        <p class="flex flex-none gap-4 text-sm text-gray-600">
                            <span>{{ $thread->comments_count }} balasan</span>
                            <span>{{ $thread->last_activity_at?->diffForHumans() }}</span>
                        </p>
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $threads->links() }}</div>
        @endif
    </div>
</x-site.layout>
