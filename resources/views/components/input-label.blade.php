@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-black text-sm uppercase tracking-wide text-black mb-2']) }}>
    {{ $value ?? $slot }}
</label>
