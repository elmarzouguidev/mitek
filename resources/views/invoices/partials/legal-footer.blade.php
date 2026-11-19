@php
    $todo = fn ($value) => filled($value) ? e($value) : '<span class="todo">[À COMPLÉTER]</span>';
@endphp
<div class="footer">
    {{ $seller['name'] }}@if (! empty($seller['legal_form'])), {{ $seller['legal_form'] }}@endif · Capital : {!! $todo($seller['share_capital'] ?? null) !!}<br>
    Siège social : {!! $todo($seller['registered_address'] ?? $seller['address'] ?? null) !!}<br>
    RC : {!! $todo($seller['rc'] ?? null) !!} · IF : {!! $todo($seller['if'] ?? null) !!} · ICE : {!! $todo($seller['ice'] ?? null) !!} · TP : {!! $todo($seller['tp'] ?? null) !!} · CNSS : {!! $todo($seller['cnss'] ?? null) !!}<br>
    {{ $seller['email'] }}@if (! empty($seller['phone'])) · {{ $seller['phone'] }}@endif
</div>
