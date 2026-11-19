<x-mail::message>
# Réponse à votre demande de devis

Bonjour {{ $quote->contact_name }},

Voici notre réponse à votre demande **{{ $quote->reference }}** :

<x-mail::panel>
{{ $quote->admin_reply }}
</x-mail::panel>

<x-mail::table>
| Article | Quantité |
| :------ | -------: |
@foreach ($items as $item)
| {{ $item->name }} ({{ $item->reference }}) | {{ $item->quantity }} |
@endforeach
</x-mail::table>

<x-mail::button :url="$quoteUrl">
Voir ma demande
</x-mail::button>

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
