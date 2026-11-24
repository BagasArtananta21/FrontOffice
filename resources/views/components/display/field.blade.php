@props(['name', 'label', 'required' => false, 'for' => null])

<div>
    <label @if ($for !== false) for="{{ $name }}" @endif class="mb-2 block text-xl font-medium text-primary">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    {{ $slot }}

    @error($name)
        <p data-display-error class="mt-2 text-lg text-danger">{{ $message }}</p>
    @enderror
</div>
