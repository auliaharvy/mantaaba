{{-- Satu field form. type: text|email|date|number|textarea|select|file. options: [value => label] untuk select. --}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'options' => [], 'placeholder' => null, 'help' => null])
@php($current = old($name, $value))
<div class="mb-6">
    <label class="form-label {{ $required ? 'required' : '' }}" for="field-{{ $name }}">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea class="form-control @error($name) is-invalid @enderror" id="field-{{ $name }}" name="{{ $name }}" rows="3" @required($required) {{ $attributes }}>{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select class="form-select @error($name) is-invalid @enderror" id="field-{{ $name }}" name="{{ $name }}" @required($required) {{ $attributes }}>
            <option value="">{{ $placeholder ?? '— Pilih —' }}</option>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @else
        <input class="form-control @error($name) is-invalid @enderror" id="field-{{ $name }}" type="{{ $type }}" name="{{ $name }}"
            @unless ($type === 'file') value="{{ $current instanceof \DateTimeInterface ? $current->format('Y-m-d') : $current }}" @endunless
            placeholder="{{ $placeholder }}" @required($required) {{ $attributes }}>
    @endif
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
