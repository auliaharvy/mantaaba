@props(['data', 'alt' => false, 'first' => false])
<x-site.hero-slider :sliders="App\Models\Slider::where('is_active', true)->orderBy('sort_order')->get()" :site="App\Models\Setting::current()" :first="$first" />
