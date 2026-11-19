<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Events\QuoteRequested;
use App\Mail\AdminNotificationMail;
use App\Settings\GeneralSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the team (the "admin notification email" setting) when something needs handling:
 * a new order, a proof of payment to validate, a quote request.
 */
class NotifyAdmin implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly GeneralSettings $settings) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order;

        $this->send(new AdminNotificationMail(
            __('Nouvelle commande :reference', ['reference' => $order->reference]),
            [
                'Client : '.($order->company_name ?: $order->contact_name).' ('.$order->contact_email.')',
                'Total : '.$order->total_ttc->format().' TTC',
                'Paiement : '.$order->payment_method->label(),
                'Livraison : '.$order->shipping_city,
            ],
            route('admin.orders.show', $order),
            'Voir la commande',
        ));
    }

    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        if ($event->to !== OrderStatus::PaymentSubmitted) {
            return;
        }

        $order = $event->order;

        $this->send(new AdminNotificationMail(
            __('Justificatif à valider : :reference', ['reference' => $order->reference]),
            [
                'Client : '.($order->company_name ?: $order->contact_name),
                'Montant : '.$order->total_ttc->format().' TTC',
                'Mode de paiement : '.$order->payment_method->label(),
            ],
            route('admin.orders.show', $order),
            'Valider le paiement',
        ));
    }

    public function handleQuoteRequested(QuoteRequested $event): void
    {
        $quote = $event->quote;

        $this->send(new AdminNotificationMail(
            __('Nouvelle demande de devis :reference', ['reference' => $quote->reference]),
            [
                'Client : '.($quote->company_name ?: $quote->contact_name).' ('.$quote->contact_email.')',
                'Articles : '.$quote->items()->count(),
            ],
            route('admin.quotes.show', $quote),
            'Répondre au devis',
        ));
    }

    private function send(AdminNotificationMail $mail): void
    {
        Mail::to($this->settings->admin_notification_email)->send($mail);
    }
}
