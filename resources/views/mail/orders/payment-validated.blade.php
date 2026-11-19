<x-mail::message>
@if ($isCod)
# Commande confirmée
@else
# Paiement validé
@endif

Bonjour {{ $order->contact_name }},

@if ($isCod)
Votre commande **{{ $order->reference }}** est confirmée. Vous réglerez {{ $order->total_ttc }} TTC à la livraison.
@else
Nous avons validé votre paiement de {{ $order->total_ttc }} TTC pour la commande **{{ $order->reference }}**.
@endif

Votre commande est en préparation et sera expédiée sous {{ app(\App\Settings\ShippingSettings::class)->dispatch_days }} jours. Vous recevrez un email avec le numéro de suivi.

@if ($order->invoice()->exists())
Votre facture est jointe à cet email (PDF).
@endif

<x-mail::button :url="$orderUrl">
Voir ma commande
</x-mail::button>

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
