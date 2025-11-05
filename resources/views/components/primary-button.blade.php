<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 py-3 bg-primary-600 text-white font-black text-sm uppercase tracking-wider border-3 border-black shadow-brutal hover:shadow-brutal-lg hover:translate-x-[-2px] hover:translate-y-[-2px] focus:outline-none transition-all']) }}>
    {{ $slot }}
</button>
