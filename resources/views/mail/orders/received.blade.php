<x-mail::message>
# Merci pour votre commande

Bonjour {{ $order->contact_name }},

Nous avons bien enregistré votre commande **{{ $order->reference }}**.

<x-mail::table>
| Article | Qté | Total TTC |
| :------ | :-: | --------: |
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | {{ $item->line_ttc }} |
@endforeach
| Livraison (HT) | | {{ $order->shipping_ht }} |
| **Total TTC** | | **{{ $order->total_ttc }}** |
</x-mail::table>

## Paiement

@include('mail.orders.partials.payment', ['order' => $order])

<x-mail::button :url="$orderUrl">
Voir ma commande
</x-mail::button>

Conservez cet email : le bouton ci-dessus vous permet de suivre votre commande et d'envoyer votre justificatif sans créer de compte.

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
