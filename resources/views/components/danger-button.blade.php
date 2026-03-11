<button {{ $attributes->merge(['type' => 'submit', 'class' => 'px-4 py-2 bg-red-600 text-white font-bold uppercase text-sm border-3 border-black shadow-brutal hover:shadow-brutal-sm hover:translate-x-[-1px] hover:translate-y-[-1px] transition-all']) }}>
    {{ $slot }}
</button>
