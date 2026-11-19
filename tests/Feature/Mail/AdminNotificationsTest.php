<?php

use App\Enums\OrderStatus;
use App\Listeners\NotifyAdmin;
use App\Mail\AdminNotificationMail;
use App\Models\Core\Order\Order;
use App\Models\Core\Product\Product;
use App\Models\Core\Quote\QuoteRequest;
use App\Models\User\User;
use App\Services\Core\Order\TransitionOrderStatus;
use App\Settings\GeneralSettings;
use App\Support\Money;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    enableShipping(30);
});

it('notifies the team of a new order, at the configured address', function () {
    $settings = app(GeneralSettings::class);
    $settings->admin_notification_email = 'ops@mitek.ma';
    $settings->save();
    $product = Product::factory()->priced(100)->create(['stock_quantity' => 5]);

    $this->withSession(cartSession([[$product, 2]]))->post(route('checkout.store'), checkoutPayload(['company_name' => 'ACME SARL', 'is_company' => true]));

    $order = Order::firstOrFail();
    Mail::assertSent(AdminNotificationMail::class, function (AdminNotificationMail $mail) use ($order) {
        $mail->assertHasSubject('[Mitek.ma] Nouvelle commande '.$order->reference);
        $mail->assertSeeInHtml('ACME SARL');
        $mail->assertSeeInHtml($order->total_ttc->format());
        $mail->assertSeeInHtml(route('admin.orders.show', $order), false);

        return $mail->hasTo('ops@mitek.ma') && ! $mail->hasTo($order->contact_email);
    });
});

it('defaults to contact@mitek.ma', function () {
    $product = Product::factory()->create(['stock_quantity' => 5]);

    $this->withSession(cartSession([[$product, 1]]))->post(route('checkout.store'), checkoutPayload());

    Mail::assertSent(AdminNotificationMail::class, fn (AdminNotificationMail $mail) => $mail->hasTo('contact@mitek.ma'));
});

it('notifies the team when a proof is waiting for validation', function () {
    $order = Order::factory()->withItem()->withHistory()->create();

    $this->post(URL::signedRoute('orders.proof.store', $order), ['proofs' => [UploadedFile::fake()->image('slip.jpg')]]);

    Mail::assertSent(AdminNotificationMail::class, function (AdminNotificationMail $mail) use ($order) {
        $mail->assertHasSubject('[Mitek.ma] Justificatif à valider : '.$order->reference);
        $mail->assertSeeInHtml('Valider le paiement');

        return $mail->hasTo('contact@mitek.ma');
    });
});

it('does not notify the team for the other status changes', function (OrderStatus $from, OrderStatus $to) {
    $order = Order::factory()->status($from)->withItem()->create();

    app(TransitionOrderStatus::class)->handle($order, $to, User::factory()->admin()->create(), null, ['tracking_number' => 'T']);

    Mail::assertNotSent(AdminNotificationMail::class);
})->with([
    'paid to processing' => [OrderStatus::Paid, OrderStatus::Processing],
    'processing to shipped' => [OrderStatus::Processing, OrderStatus::Shipped],
    'pending to cancelled' => [OrderStatus::PendingPayment, OrderStatus::Cancelled],
]);

it('notifies the team of a new quote request', function () {
    $product = Product::factory()->priced(100)->create(['stock_quantity' => 500]);

    $this->withSession(cartSession([[$product, 60]]))->post(route('quotes.store'), [
        'contact_name' => 'Karim Alaoui', 'contact_email' => 'karim@acme.ma', 'contact_phone' => '0661234567', 'company_name' => 'ACME SARL',
    ]);

    $quote = QuoteRequest::firstOrFail();
    Mail::assertSent(AdminNotificationMail::class, function (AdminNotificationMail $mail) use ($quote) {
        $mail->assertHasSubject('[Mitek.ma] Nouvelle demande de devis '.$quote->reference);
        $mail->assertSeeInHtml('ACME SARL');
        $mail->assertSeeInHtml(route('admin.quotes.show', $quote), false);

        return $mail->hasTo('contact@mitek.ma');
    });
});

it('queues the notifications after commit', function () {
    $listener = new NotifyAdmin(app(GeneralSettings::class));

    expect($listener)->toBeInstanceOf(ShouldQueue::class)->and($listener->afterCommit)->toBeTrue();
});

it('does not email the customer in the admin notification', function () {
    $product = Product::factory()->priced(100)->create(['stock_quantity' => 5]);
    $this->withSession(cartSession([[$product, 1]]))->post(route('checkout.store'), checkoutPayload(['contact_email' => 'sara@example.com']));

    Mail::assertSent(AdminNotificationMail::class, fn (AdminNotificationMail $mail) => ! $mail->hasTo('sara@example.com'));
    expect(Money::zero()->isZero())->toBeTrue();
});
