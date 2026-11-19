<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Listeners\SendOrderReceivedEmail;
use App\Listeners\SendOrderStatusEmail;
use App\Listeners\SendQuoteRepliedEmail;
use App\Mail\AdminNotificationMail;
use App\Mail\OrderReceivedMail;
use App\Mail\OrderShippedMail;
use App\Mail\PaymentProofReceivedMail;
use App\Mail\PaymentRejectedMail;
use App\Mail\PaymentValidatedMail;
use App\Models\Core\Order\Order;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use App\Services\Core\Order\ReviewPayment;
use App\Services\Core\Order\TransitionOrderStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->admin = User::factory()->admin()->create();
});

function orderWithProof(): Order
{
    $order = Order::factory()->status(OrderStatus::PaymentSubmitted)->withItem()->withConsistentTotals()->withHistory()->create(['contact_email' => 'sara@example.com']);
    $order->payments()->create(['method' => $order->payment_method, 'amount' => $order->total_ttc, 'status' => 'submitted']);

    return $order;
}

it('sends the order received email after checkout, with the payment instructions and a signed link', function () {
    enableShipping(30);
    $product = Product::factory()->priced(100)->create(['stock_quantity' => 5, 'name' => 'Toner noir']);

    $this->withSession(cartSession([[$product, 2]]))->post(route('checkout.store'), checkoutPayload(['contact_email' => 'sara@example.com']));

    $order = Order::firstOrFail();

    Mail::assertSent(OrderReceivedMail::class, function (OrderReceivedMail $mail) use ($order) {
        $mail->assertTo('sara@example.com');
        $mail->assertSeeInHtml('Merci pour votre commande');
        $mail->assertSeeInHtml($order->reference);
        $mail->assertSeeInHtml('Toner noir');
        $mail->assertSeeInHtml('Banque Populaire');
        $mail->assertSeeInHtml('RIB');
        $mail->assertSeeInHtml($order->total_ttc->format());
        $mail->assertSeeInHtml('signature=');

        return $mail->hasTo('sara@example.com') && $mail->order->is($order);
    });
    Mail::assertSent(OrderReceivedMail::class, 1);
});

it('adapts the payment paragraph to the method', function (PaymentMethod $method, string $expected) {
    $order = Order::factory()->method($method)->withItem()->withConsistentTotals()->create();

    (new OrderReceivedMail($order))->assertSeeInHtml($expected, false);
})->with([
    'cash on delivery' => [PaymentMethod::CashOnDelivery, 'Paiement à la livraison'],
    'cheque' => [PaymentMethod::Cheque, 'à l\'ordre de'],
    'transfer' => [PaymentMethod::BankTransfer, 'Titulaire'],
    'deposit' => [PaymentMethod::CashDeposit, 'environ 24 heures'],
]);

it('sends the proof received email when the customer uploads a proof', function () {
    $order = Order::factory()->withItem()->withHistory()->create(['contact_email' => 'sara@example.com']);

    $this->post(URL::signedRoute('orders.proof.store', $order), ['proofs' => [UploadedFile::fake()->image('slip.jpg')]]);

    Mail::assertSent(PaymentProofReceivedMail::class, fn (PaymentProofReceivedMail $mail) => $mail->hasTo('sara@example.com') && $mail->order->is($order));
    Mail::assertSent(PaymentProofReceivedMail::class, 1);
});

it('sends the payment validated email with the invoice attached', function () {
    $order = orderWithProof();

    app(ReviewPayment::class)->validate($order->payments()->firstOrFail(), $this->admin);

    Mail::assertSent(PaymentValidatedMail::class, function (PaymentValidatedMail $mail) use ($order) {
        $invoice = $order->invoice()->firstOrFail();
        $mail->assertHasSubject('Paiement validé - commande '.$order->reference);
        $mail->assertSeeInHtml('Paiement validé');
        $mail->assertSeeInHtml('Votre facture est jointe');
        $attachments = $mail->attachments();

        return count($attachments) === 1
            && $attachments[0]->as === $invoice->number.'.pdf'
            && $attachments[0]->mime === 'application/pdf'
            && $mail->hasTo($order->contact_email);
    });
});

it('confirms a cash on delivery order by email, with the invoice', function () {
    $order = Order::factory()->method(PaymentMethod::CashOnDelivery)->withItem()->withConsistentTotals()->withHistory()->create();

    app(TransitionOrderStatus::class)->handle($order, OrderStatus::Processing, $this->admin);

    Mail::assertSent(PaymentValidatedMail::class, function (PaymentValidatedMail $mail) {
        $mail->assertHasSubject('Commande '.$mail->order->reference.' confirmée');
        $mail->assertSeeInHtml('Commande confirmée');
        $mail->assertSeeInHtml('à la livraison');

        return count($mail->attachments()) === 1;
    });
});

it('tells the customer why the proof was rejected', function () {
    $order = orderWithProof();

    app(ReviewPayment::class)->reject($order->payments()->firstOrFail(), $this->admin, 'Image illisible');

    Mail::assertSent(PaymentRejectedMail::class, function (PaymentRejectedMail $mail) {
        $mail->assertSeeInHtml('Image illisible');
        $mail->assertSeeInHtml('Justificatif refusé');

        return true;
    });
    Mail::assertNotSent(PaymentValidatedMail::class);
});

it('sends the shipping email with the tracking number', function () {
    $order = Order::factory()->status(OrderStatus::Processing)->withItem()->create(['shipping_city' => 'Fès']);

    app(TransitionOrderStatus::class)->handle($order, OrderStatus::Shipped, $this->admin, null, ['tracking_number' => 'AMANA-123', 'tracking_carrier' => 'Amana']);

    Mail::assertSent(OrderShippedMail::class, function (OrderShippedMail $mail) {
        $mail->assertSeeInHtml('AMANA-123');
        $mail->assertSeeInHtml('Amana');
        $mail->assertSeeInHtml('Fès');

        return true;
    });
});

it('sends nothing for the other transitions', function (OrderStatus $from, OrderStatus $to) {
    $order = Order::factory()->status($from)->withItem()->create();

    app(TransitionOrderStatus::class)->handle($order, $to, $this->admin, null, ['tracking_number' => 'X']);

    Mail::assertNothingSent();
})->with([
    'paid to processing' => [OrderStatus::Paid, OrderStatus::Processing],
    'shipped to delivered' => [OrderStatus::Shipped, OrderStatus::Delivered],
]);

it('sends nothing when a transition is refused', function () {
    $order = Order::factory()->status(OrderStatus::PendingPayment)->withItem()->create();

    try {
        app(TransitionOrderStatus::class)->handle($order, OrderStatus::Delivered);
    } catch (Throwable) {
    }

    Mail::assertNothingSent();
});

it('sends one email per milestone through the whole life of a prepaid order', function () {
    enableShipping(30);
    $product = Product::factory()->priced(100)->create(['stock_quantity' => 5]);
    $this->withSession(cartSession([[$product, 1]]))->post(route('checkout.store'), checkoutPayload());
    $order = Order::firstOrFail();

    $this->post(URL::signedRoute('orders.proof.store', $order), ['proofs' => [UploadedFile::fake()->image('slip.jpg')]]);
    $this->actingAs($this->admin)->post(route('admin.orders.payments.validate', $order->payments()->firstOrFail()));
    $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => 'processing']);
    $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => 'shipped', 'tracking_number' => 'T-1']);

    $sent = fn (string $class) => Mail::sent($class)->count();

    expect($sent(OrderReceivedMail::class))->toBe(1)
        ->and($sent(PaymentProofReceivedMail::class))->toBe(1)
        ->and($sent(PaymentValidatedMail::class))->toBe(1)
        ->and($sent(OrderShippedMail::class))->toBe(1)
        ->and(Mail::sent(AdminNotificationMail::class)->count())->toBe(2) // new order, proof to validate
        ->and(Mail::sent(Mailable::class)->count())->toBe(6);
});

it('queues every email listener and sends it only after the transaction commits', function (string $class) {
    $listener = new $class;

    expect($listener)->toBeInstanceOf(ShouldQueue::class)
        ->and($listener->afterCommit)->toBeTrue();
})->with([SendOrderReceivedEmail::class, SendOrderStatusEmail::class, SendQuoteRepliedEmail::class]);
