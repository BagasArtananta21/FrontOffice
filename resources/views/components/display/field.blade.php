@props(['name', 'label', 'required' => false])

<div>
    <label for="{{ $name }}" class="mb-2 block text-xl font-medium text-primary">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    {{ $slot }}

    @error($name)
        <p class="mt-2 text-lg text-danger">{{ $message }}</p>
    @enderror
</div>
