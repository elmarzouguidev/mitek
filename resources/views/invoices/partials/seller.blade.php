@php
    $todo = fn ($value) => filled($value) ? e($value) : '<span class="todo">[À COMPLÉTER]</span>';
@endphp
<img src="{{ $logo }}" alt="{{ $seller['site'] ?? 'Mitek.ma' }}" style="height: 48px;"><br><br>
<strong>{{ $seller['name'] }}</strong>@if (! empty($seller['legal_form'])) <span class="muted">({{ $seller['legal_form'] }})</span>@endif<br>
{{ $seller['address'] }}<br>
{{ $seller['email'] }}@if (! empty($seller['phone'])) · {{ $seller['phone'] }}@endif<br>
<span class="muted">
    ICE : {!! $todo($seller['ice'] ?? null) !!} · IF : {!! $todo($seller['if'] ?? null) !!} · RC : {!! $todo($seller['rc'] ?? null) !!}
</span>
