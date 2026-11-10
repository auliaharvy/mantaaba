@forelse ($blocks as $i => $block)
    <x-dynamic-component :component="'site.block.' . str_replace('_', '-', $block['type'])" :data="$block['data']" :alt="$i % 2 === 1" :first="$i === 0" />
@empty
    <div class="container-site py-12"><x-site.empty-state title="Halaman ini belum berisi konten" /></div>
@endforelse
