@props(['site'])
@foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn'] as $network => $label)
    @if ($site->{$network})
        <a href="{{ $site->{$network} }}" target="_blank" rel="noopener" aria-label="{{ $label }}" class="hover:text-white"><x-site.icon :name="$network" :class="$attributes->get('class', 'h-5 w-5')" /></a>
    @endif
@endforeach
