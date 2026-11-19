<button {{ $attributes->merge(['type' => 'submit'])->class('inline-flex w-full items-center justify-center rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700') }}>
    {{ $slot }}
</button>
