<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\CallbackRequested;
use App\Events\ContactMessageReceived;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Events\QuoteRequested;
use App\Events\ServiceRequested;
use App\Mail\AdminNotificationMail;
use App\Settings\GeneralSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    public function handleServiceRequested(ServiceRequested $event): void
    {
        $request = $event->request;

        $this->send(new AdminNotificationMail(
            __('Nouvelle demande de service :reference', ['reference' => $request->reference]),
            [
                'Service : '.$request->service_title,
                'Client : '.($request->company_name ?: $request->contact_name).' ('.$request->email.', '.$request->phone.')',
                'Intervention : '.$request->intervention->label().', urgence : '.$request->urgency->label(),
                'Ville : '.$request->city,
            ],
            route('admin.service-requests.show', $request),
            'Traiter la demande',
        ));
    }

    public function handleContactMessageReceived(ContactMessageReceived $event): void
    {
        $message = $event->message;

        $this->send(new AdminNotificationMail(
            __('Nouveau message : :subject', ['subject' => $message->subject]),
            [
                'De : '.$message->name.' ('.$message->email.')',
                Str::limit($message->message, 200),
            ],
            route('admin.messages.show', $message),
            'Lire le message',
        ));
    }

    public function handleCallbackRequested(CallbackRequested $event): void
    {
        $callback = $event->callback;

        $this->send(new AdminNotificationMail(
            __('Demande de rappel : :name', ['name' => $callback->name]),
            array_filter([
                'Téléphone : '.$callback->phone,
                'Sujet : '.$callback->subject->label(),
                'Créneau : '.$callback->time_slot->label(),
                $callback->note ? 'Précisions : '.Str::limit($callback->note, 200) : null,
            ]),
            route('admin.callbacks.show', $callback),
            'Traiter la demande',
        ));
    }

    private function send(AdminNotificationMail $mail): void
    {
        Mail::to($this->settings->admin_notification_email)->send($mail);
    }
}
