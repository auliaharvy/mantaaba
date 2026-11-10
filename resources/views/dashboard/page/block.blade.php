{{-- Satu blok di editor halaman. $key = kunci form, $def = definisi blok. --}}
@php
    $path = "blocks.{$key}";
    $data = $block['data'] ?? [];
    $preview = $data['title'] ?? $data['translation'] ?? $data['name'] ?? null;
@endphp
<div class="page-block card mb-5" id="blok-{{ $number - 1 }}">
    <div class="card-header flex-nowrap align-items-center gap-3 px-5 min-h-60px">
        <span class="block-handle cursor-move text-gray-500 fs-4" title="Seret untuk mengurutkan"><i class="fa fa-grip-vertical"></i></span>
        <div class="flex-grow-1" style="min-width: 0">
            <div class="fw-bolder"><span class="block-number text-muted">#{{ $number }}</span> {{ $def['label'] }}</div>
            @if ($preview)<div class="text-muted fs-8 text-truncate">{{ Str::limit(strip_tags($preview), 80) }}</div>@endif
        </div>
        <div class="form-check form-switch form-check-sm mb-0" title="Sembunyikan dari situs">
            <input class="form-check-input" type="checkbox" id="hide-{{ $key }}" name="blocks[{{ $key }}][hidden]" value="1" @checked(! empty($block['hidden']))>
            <label class="form-check-label fs-8 d-none d-md-inline" for="hide-{{ $key }}">Sembunyikan</label>
        </div>
        <button type="submit" name="_op" value="duplicate:{{ $key }}" class="btn btn-sm btn-icon btn-light" title="Duplikat"><i class="fa fa-copy"></i></button>
        <button type="submit" name="_op" value="remove:{{ $key }}" class="btn btn-sm btn-icon btn-light-danger" title="Hapus blok" data-confirm-op="Hapus blok {{ $def['label'] }}?"><i class="fa fa-trash"></i></button>
    </div>
    <input type="hidden" name="blocks[{{ $key }}][type]" value="{{ $block['type'] }}">
    <details @if ($errors->has("{$path}.*")) open @endif>
        <summary class="px-5 py-3 text-primary fs-7 fw-bold cursor-pointer">Isi blok</summary>
        <div class="card-body pt-2">
            @if ($block['type'] === 'slider')
                <p class="text-muted fs-7">Blok ini menampilkan slider aktif dari menu <a href="{{ route('slider.index') }}">Slider</a>.</p>
            @endif
            <div class="row">
                @foreach ($def['fields'] as $name => $field)
                    @include('dashboard.page.block-field', ['name' => "blocks[{$key}][data][{$name}]", 'path' => "{$path}.data.{$name}", 'field' => $field, 'value' => $data[$name] ?? null])
                @endforeach
            </div>
            @isset($def['items'])
                <div class="separator my-4"></div>
                <div class="fw-bolder mb-3">{{ $def['items']['label'] }} <span class="text-muted fs-8">(maks. {{ $def['items']['max'] }})</span></div>
                @foreach (array_values($data['items'] ?? []) as $j => $item)
                    <div class="border rounded p-4 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold fs-7">{{ $def['items']['label'] }} {{ $j + 1 }}</span>
                            <button type="submit" name="_op" value="remove-item:{{ $key }}:{{ $j }}" class="btn btn-sm btn-icon btn-light-danger" title="Hapus item"><i class="fa fa-times"></i></button>
                        </div>
                        <div class="row">
                            @foreach ($def['items']['fields'] as $name => $field)
                                @include('dashboard.page.block-field', ['name' => "blocks[{$key}][data][items][{$j}][{$name}]", 'path' => "{$path}.data.items.{$j}.{$name}", 'field' => $field, 'value' => $item[$name] ?? null])
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @if (count($data['items'] ?? []) < $def['items']['max'])
                    <button type="submit" name="_op" value="add-item:{{ $key }}" class="btn btn-sm btn-light-primary"><i class="fa fa-plus"></i> Tambah {{ Str::lower($def['items']['label']) }}</button>
                @endif
            @endisset
        </div>
    </details>
</div>
