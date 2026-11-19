<x-mail::message>
# Commande annulée

Bonjour {{ $order->contact_name }},

Votre commande **{{ $order->reference }}** a été annulée.

@if ($creditNote)
Comme elle avait été facturée, nous avons émis l'avoir **{{ $creditNote->number }}** de {{ $creditNote->total_ttc->format() }} TTC, joint à cet email (PDF). Il annule la facture correspondante.
@else
Aucun paiement n'avait été validé : aucune facture n'a été émise et rien n'est à rembourser.
@endif

Si vous pensez à une erreur ou souhaitez repasser commande, répondez simplement à cet email.

<x-mail::button :url="$orderUrl">
Voir ma commande
</x-mail::button>

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
