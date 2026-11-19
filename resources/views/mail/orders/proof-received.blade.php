<x-mail::message>
# Justificatif reçu

Bonjour {{ $order->contact_name }},

Nous avons bien reçu votre justificatif de paiement pour la commande **{{ $order->reference }}** ({{ $order->total_ttc }} TTC).

Notre équipe le vérifie manuellement et vous préviendra dès que le paiement sera validé. Votre commande sera ensuite expédiée sous {{ app(\App\Settings\ShippingSettings::class)->dispatch_days }} jours.

<x-mail::button :url="$orderUrl">
Suivre ma commande
</x-mail::button>

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
