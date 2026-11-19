@props(['name', 'label', 'type' => 'text'])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name) }}" @endif
        {{ $attributes->class([
            'mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 shadow-xs ring-1 ring-inset focus:ring-2 focus:ring-inset',
            'ring-slate-300 focus:ring-brand-600' => ! $errors->has($name),
            'ring-red-400 focus:ring-red-500' => $errors->has($name),
        ]) }}
    >
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
