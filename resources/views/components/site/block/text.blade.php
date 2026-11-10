@props(['data', 'alt' => false, 'first' => false])
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site max-w-3xl">
        @if (! empty($data['title']))<x-site.section-heading :title="$data['title']" />@endif
        <div class="prose-cms">{!! $data['body'] !!}</div>
    </div>
</section>
