{{-- Field blok. Tanpa atribut `required` HTML: field bisa berada di <details> tertutup; validasi di server. --}}
@php
    $id = 'f' . substr(md5($name), 0, 10);
    $error = $errors->first($path);
    $wide = in_array($field['type'], ['rich', 'textarea', 'units', 'embed', 'url'], true);
    $label = $field['label'] . (empty($field['required']) ? '' : ' *');
@endphp
<div class="{{ $wide ? 'col-12' : 'col-md-6' }} mb-4">
    <label class="form-label fs-7" for="{{ $id }}">{{ $label }}</label>
    @switch($field['type'])
        @case('textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="3" class="form-control form-control-sm @if ($error) is-invalid @endif">{{ $value }}</textarea>
            @break
        @case('rich')
            <textarea id="{{ $id }}" name="{{ $name }}" class="block-rich">{{ $value }}</textarea>
            @break
        @case('image')
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            <div class="d-flex align-items-center gap-3">
                @if ($value)<img src="{{ Storage::disk('public')->url($value) }}" alt="" class="h-50px rounded">@endif
                <input id="{{ $id }}" type="file" name="{{ Str::replaceLast(']', '_upload]', $name) }}" accept="image/*" class="form-control form-control-sm @if ($errors->first($path . '_upload')) is-invalid @endif">
            </div>
            @if ($value)
                <label class="form-check form-check-sm mt-2"><input class="form-check-input" type="checkbox" name="{{ Str::replaceLast(']', '_remove]', $name) }}" value="1"> <span class="form-check-label fs-8">Hapus gambar</span></label>
            @endif
            @php $error = $error ?: $errors->first($path . '_upload'); @endphp
            @break
        @case('select')
        @case('icon')
        @case('gallery')
            @php
                $options = match ($field['type']) {
                    'icon' => array_combine(config('mantaaba.icons'), config('mantaaba.icons')),
                    'gallery' => App\Models\Gallery::latest('published_at')->pluck('title', 'id')->all(),
                    default => App\Support\Blocks::options($field),
                };
            @endphp
            <select id="{{ $id }}" name="{{ $name }}" class="form-select form-select-sm @if ($error) is-invalid @endif">
                @if ($field['type'] !== 'select' || empty($field['required']))<option value="">—</option>@endif
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
            @break
        @case('units')
            @php $units = App\Models\BusinessUnit::orderBy('sort_order')->pluck('name', 'id'); @endphp
            <div class="d-flex flex-wrap gap-4" id="{{ $id }}">
                @forelse ($units as $unitId => $unitName)
                    <label class="form-check form-check-sm"><input class="form-check-input" type="checkbox" name="{{ $name }}[]" value="{{ $unitId }}" @checked(in_array($unitId, (array) $value))> <span class="form-check-label fs-7">{{ $unitName }}</span></label>
                @empty
                    <span class="text-muted fs-7">Belum ada unit bisnis.</span>
                @endforelse
            </div>
            @break
        @default
            <input id="{{ $id }}" type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" name="{{ $name }}" value="{{ $value }}"
                @isset($field['min']) min="{{ $field['min'] }}" @endisset @isset($field['max']) max="{{ $field['max'] }}" @endisset
                class="form-control form-control-sm @if ($error) is-invalid @endif">
    @endswitch
    @if ($error)<div class="text-danger fs-8 mt-1">{{ $error }}</div>@endif
    @if (! empty($field['help']))<div class="form-text fs-8">{{ $field['help'] }}</div>@endif
</div>
