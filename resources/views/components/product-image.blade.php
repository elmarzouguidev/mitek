@props(['product', 'conversion' => 'card'])

@php($url = $product->getFirstMediaUrl(\App\Models\Core\Product\Product::GALLERY, $conversion))

@if ($url)
    <img src="{{ $url }}" alt="{{ $product->imageAlt() }}" {{ $attributes }}>
@else
    <div role="img" aria-label="{{ $product->imageAlt() }}" {{ $attributes->class('flex items-center justify-center bg-slate-100 text-slate-400') }}>
        <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.25 2.25M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5v-15a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v15a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V9.75Z"/></svg>
    </div>
@endif
