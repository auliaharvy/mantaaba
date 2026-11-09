@php($site = App\Models\Setting::current())
<x-site.layout :title="$page->meta_title ?: $page->title" :description="$page->meta_description ?: ($page->excerpt ?: strip_tags($page->body))">
    <x-site.breadcrumb-header :title="$page->title" :crumbs="['Keanggotaan' => route('site.membership'), 'Layanan']" />
    <div class="container-site py-12">
        <div class="mx-auto max-w-4xl">
            <div class="prose-cms mb-10">{!! $page->body !!}</div>
            <div class="grid gap-6 md:grid-cols-2">
                @foreach ([
                    ['Pendaftaran Anggota Baru', 'Formulir pendaftaran anggota AKP2I diproses oleh Pengurus Pusat.', $site->url_registration, 'users'],
                    ['Pelayanan Anggota', 'Perpanjangan KTA, legalisir SK, dan peningkatan jenis keanggotaan.', $site->url_member_service, 'shield'],
                ] as [$title, $text, $url, $icon])
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="group flex flex-col rounded-lg border border-brand-border bg-white p-6 shadow-sm transition hover:border-brand-primary hover:shadow-md">
                        <x-site.icon :name="$icon" class="mb-4 h-10 w-10 text-brand-primary" />
                        <h2 class="text-xl">{{ $title }}</h2>
                        <p class="mt-2 flex-1 text-gray-600">{{ $text }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 font-bold text-brand-secondary">Buka layanan <x-site.icon name="external" class="h-4 w-4" /></span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-site.layout>
