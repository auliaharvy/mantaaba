@props(['name', 'label', 'type' => 'text', 'required' => false, 'value' => null])
<div class="mb-4">
    <label for="f-{{ $name }}" class="mb-1 block text-sm font-semibold">{{ $label }}@if ($required)<span class="text-red-700"> *</span>@endif</label>
    <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password') value="{{ old($name, $value) }}" @endif @required($required)
        {{ $attributes->merge(['class' => 'w-full rounded-md border-brand-border focus:border-brand-primary focus:ring-brand-primary' . ($errors->has($name) ? ' border-red-600' : '')]) }}
        @error($name) aria-invalid="true" aria-describedby="e-{{ $name }}" @enderror>
    @error($name)<p id="e-{{ $name }}" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
