@props(['product', 'limit' => 3])

@php
    $badges = array_slice(app(\App\Services\Core\Product\ProductBadges::class)->for($product), 0, $limit);
    $tones = [
        'red' => 'bg-red-600 text-white',
        'brand' => 'bg-brand-700 text-white',
        'green' => 'bg-emerald-600 text-white',
    ];
@endphp

@if ($badges !== [])
    <ul {{ $attributes->class('flex flex-wrap gap-1') }}>
        @foreach ($badges as $badge)
            <li class="rounded-md px-2 py-0.5 text-xs font-semibold shadow-xs {{ $tones[$badge['tone']] ?? 'bg-slate-700 text-white' }}" data-badge="{{ $badge['key'] }}">{{ $badge['label'] }}</li>
        @endforeach
    </ul>
@endif
