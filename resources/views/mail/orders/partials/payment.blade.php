@php
    $payment = app(\App\Settings\PaymentSettings::class);
    $method = $order->payment_method;
@endphp
@if ($method === \App\Enums\PaymentMethod::CashOnDelivery)
**Paiement à la livraison** : vous réglez {{ $order->total_ttc }} TTC à la réception du colis. Notre équipe confirme votre commande avant l'expédition.
@elseif ($method === \App\Enums\PaymentMethod::Cheque)
**{{ $method->label() }}** : à l'ordre de **{{ $payment->cheque_payee }}**, montant **{{ $order->total_ttc }} TTC**. Envoyez-nous le recto et le verso depuis la page de votre commande. Encaissement sous {{ $payment->cheque_delay }}.
@else
**{{ $method->label() }}** : montant **{{ $order->total_ttc }} TTC**.

- Banque : {{ $payment->bank_name }}
- Titulaire : {{ $payment->account_holder }}
- RIB : {{ $payment->rib ?: 'à communiquer, contactez-nous' }}

Envoyez votre justificatif depuis la page de votre commande. Délai de validation : {{ $method === \App\Enums\PaymentMethod::BankTransfer ? $payment->bank_transfer_delay : $payment->cash_deposit_delay }}.
@endif
