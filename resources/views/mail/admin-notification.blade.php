<x-mail::message>
# {{ $headline }}

@foreach ($lines as $line)
- {{ $line }}
@endforeach

<x-mail::button :url="$url">
{{ $actionLabel }}
</x-mail::button>

Notification automatique de {{ config('app.name') }}.
</x-mail::message>
