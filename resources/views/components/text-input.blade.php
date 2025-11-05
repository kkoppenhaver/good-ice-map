@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-3 border-black focus:border-primary-600 focus:ring-0 focus:outline-none font-mono font-bold text-base px-4 py-2 w-full transition-all']) }}>
