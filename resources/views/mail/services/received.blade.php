<x-mail::message>
# Demande reçue

Bonjour {{ $serviceRequest->contact_name }},

Nous avons bien reçu votre demande de service **{{ $serviceRequest->reference }}** : **{{ $serviceRequest->service_title }}**.

- Intervention : {{ $serviceRequest->intervention->label() }}
- Urgence : {{ $serviceRequest->urgency->label() }}
- Ville : {{ $serviceRequest->city }}

Notre équipe vous recontacte au {{ $serviceRequest->phone }} ou à cette adresse email.

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
