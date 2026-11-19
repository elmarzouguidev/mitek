<x-mail::message>
# Justificatif refusé

Bonjour {{ $order->contact_name }},

Nous n'avons pas pu valider le justificatif envoyé pour la commande **{{ $order->reference }}**.

@if ($reason !== '')
**Motif :** {{ $reason }}
@endif

Merci d'envoyer un nouveau justificatif depuis la page de votre commande.

<x-mail::button :url="$orderUrl">
Envoyer un justificatif
</x-mail::button>

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
