<x-site.layout title="Pengurus Cabang" description="Daftar Pengurus Cabang (PC) AKP2I di Jawa Barat beserta kontak dan jumlah anggota aktif.">
    <x-site.breadcrumb-header title="Pengurus Cabang" :crumbs="['Profil' => route('site.profile'), 'Pengurus Cabang']" />
    <div class="container-site py-12">
        @if ($branches->isEmpty())
            <x-site.empty-state title="Belum ada data cabang" icon="building" />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($branches as $branch)
                    <a href="{{ route('site.branch', $branch->slug) }}" class="group flex flex-col rounded-lg border border-brand-border bg-white p-6 shadow-sm transition hover:border-brand-primary hover:shadow-md">
                        <h2 class="text-lg group-hover:text-brand-secondary">{{ $branch->name }}</h2>
                        @if ($branch->city)<p class="mt-1 text-sm text-gray-600">{{ $branch->city }}</p>@endif
                        <div class="mt-4 flex-1 space-y-1 text-sm text-gray-700">
                            @if ($branch->phone)<p class="flex gap-2"><x-site.icon name="phone" class="h-4 w-4 flex-none text-brand-primary" />{{ $branch->phone }}</p>@endif
                            @if ($branch->email)<p class="flex gap-2 break-all"><x-site.icon name="mail" class="h-4 w-4 flex-none text-brand-primary" />{{ $branch->email }}</p>@endif
                        </div>
                        <p class="mt-4 flex items-center justify-between border-t border-brand-border pt-4">
                            <span class="text-sm text-gray-600"><span class="text-xl font-bold text-brand-heading" data-testid="active-members">{{ $branch->members_count }}</span> anggota aktif</span>
                            <x-site.icon name="arrow-right" class="h-5 w-5 text-brand-secondary" />
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-site.layout>
