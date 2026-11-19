@props(['status'])

@php
    $classes = match ($status->tone()) {
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        default => 'bg-brand-50 text-brand-700 ring-brand-200',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset', $classes]) }}>{{ $status->label() }}</span>
