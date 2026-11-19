<x-mail::message>
# Votre commande est expédiée

Bonjour {{ $order->contact_name }},

Votre commande **{{ $order->reference }}** a été expédiée à {{ $order->shipping_city }}.

@if ($order->tracking_number)
**Numéro de suivi :** {{ $order->tracking_number }}@if ($order->tracking_carrier) ({{ $order->tracking_carrier }})@endif

@endif
@if ($order->carrier_mode === \App\Enums\CarrierMode::Own)
Le colis est prêt à être récupéré par votre transporteur.
@endif

<x-mail::button :url="$orderUrl">
Suivre ma commande
</x-mail::button>

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
