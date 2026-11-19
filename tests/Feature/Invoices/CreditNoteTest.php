<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Mail\OrderCancelledMail;
use App\Models\Core\Invoice\CreditNote;
use App\Models\Core\Invoice\Invoice;
use App\Models\Core\Order\Order;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use App\Services\Core\Invoice\CreditNotePdf;
use App\Services\Core\Invoice\IssueCreditNote;
use App\Services\Core\Order\TransitionOrderStatus;
use App\Settings\GeneralSettings;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;

function paidInvoicedOrder(array $attributes = [], int $quantity = 2): Order
{
    $order = Order::factory()->status(OrderStatus::PaymentSubmitted)->withItem(Product::factory()->priced(100)->create(), $quantity)->withConsistentTotals()->withHistory()->create($attributes);

    return app(TransitionOrderStatus::class)->handle($order, OrderStatus::Paid);
}

function cancel(Order $order): Order
{
    return app(TransitionOrderStatus::class)->handle($order, OrderStatus::Cancelled, User::factory()->admin()->create());
}

describe('issuing', function () {
    it('issues a credit note when an invoiced order is cancelled', function () {
        $order = paidInvoicedOrder();

        cancel($order);

        $creditNote = $order->creditNote()->firstOrFail();
        expect($creditNote->number)->toBe('AV-'.now()->year.'-00001')
            ->and($creditNote->invoice_id)->toBe($order->invoice()->firstOrFail()->id)
            ->and($creditNote->issued_at->isToday())->toBeTrue();
    });

    it('reverses the invoice with negative amounts', function () {
        $order = paidInvoicedOrder();
        $order->refresh();

        cancel($order);

        $creditNote = $order->creditNote()->firstOrFail();
        expect($creditNote->subtotal_ht->cents)->toBe(-($order->subtotal_ht->cents + $order->shipping_ht->cents))
            ->and($creditNote->vat_total->cents)->toBe(-$order->vat_total->cents)
            ->and($creditNote->total_ttc->cents)->toBe(-$order->total_ttc->cents)
            ->and($creditNote->total_ttc->cents)->toBeLessThan(0);
    });

    it('issues nothing when the order was never invoiced', function () {
        $order = Order::factory()->withItem()->withHistory()->create();

        cancel($order);

        expect(CreditNote::count())->toBe(0)
            ->and(app(IssueCreditNote::class)->handle($order))->toBeNull();
    });

    it('issues a credit note for a cash on delivery order that was confirmed then cancelled', function () {
        $order = Order::factory()->method(PaymentMethod::CashOnDelivery)->withItem()->withConsistentTotals()->withHistory()->create();
        app(TransitionOrderStatus::class)->handle($order, OrderStatus::Processing);

        cancel($order);

        expect($order->creditNote()->exists())->toBeTrue();
    });

    it('issues a single credit note per order', function () {
        $order = cancel(paidInvoicedOrder());

        $again = app(IssueCreditNote::class)->handle($order);

        expect($again->id)->toBe($order->creditNote()->firstOrFail()->id)
            ->and(CreditNote::count())->toBe(1);
    });

    it('numbers credit notes without gaps and restarts each year', function () {
        $this->travelTo(now()->setDate(2026, 12, 30));
        $first = cancel(paidInvoicedOrder())->creditNote()->firstOrFail()->number;
        $second = cancel(paidInvoicedOrder())->creditNote()->firstOrFail()->number;

        $this->travelTo(now()->setDate(2027, 1, 3));
        $third = cancel(paidInvoicedOrder())->creditNote()->firstOrFail()->number;

        expect([$first, $second, $third])->toBe(['AV-2026-00001', 'AV-2026-00002', 'AV-2027-00001']);
    });

    it('keeps its numbering independent from the invoices', function () {
        paidInvoicedOrder();
        paidInvoicedOrder();
        $order = paidInvoicedOrder();

        cancel($order);

        expect($order->invoice()->firstOrFail()->number)->toBe('FA-'.now()->year.'-00003')
            ->and($order->creditNote()->firstOrFail()->number)->toBe('AV-'.now()->year.'-00001');
    });

    it('snapshots the seller details', function () {
        $settings = app(GeneralSettings::class);
        $settings->ice = '001234567000089';
        $settings->tp_number = 'TP-55';
        $settings->save();
        $order = cancel(paidInvoicedOrder());

        $settings->ice = '999999999999999';
        $settings->save();

        $html = app(CreditNotePdf::class)->html($order->creditNote()->firstOrFail());
        expect($html)->toContain('001234567000089')->toContain('TP-55')->not->toContain('999999999999999');
    });
});

describe('immutability', function () {
    it('never lets an issued invoice be edited or deleted', function () {
        $invoice = paidInvoicedOrder()->invoice()->firstOrFail();

        expect(fn () => $invoice->update(['number' => 'FA-1999-00001']))->toThrow(LogicException::class)
            ->and(fn () => $invoice->update(['seller' => ['name' => 'Someone else']]))->toThrow(LogicException::class)
            ->and(fn () => $invoice->delete())->toThrow(LogicException::class)
            ->and(Invoice::count())->toBe(1);
    });

    it('never lets an issued credit note be edited or deleted', function () {
        $creditNote = cancel(paidInvoicedOrder())->creditNote()->firstOrFail();

        expect(fn () => $creditNote->update(['total_ttc' => Money::fromCents(-1)]))->toThrow(LogicException::class)
            ->and(fn () => $creditNote->delete())->toThrow(LogicException::class)
            ->and(CreditNote::count())->toBe(1);
    });

    it('still lets the cached PDF path be recorded', function () {
        $invoice = paidInvoicedOrder()->invoice()->firstOrFail();

        $invoice->update(['pdf_path' => 'invoices/custom.pdf']);

        expect($invoice->fresh()->pdf_path)->toBe('invoices/custom.pdf');
    });

    it('protects documents from the deletion of their order at the database level', function () {
        $order = cancel(paidInvoicedOrder());

        $order->delete();
    })->throws(QueryException::class);
});

describe('document', function () {
    it('shows the reference to the invoice, negative lines and the VAT breakdown', function () {
        $order = paidInvoicedOrder(['company_name' => 'ACME SARL', 'ice' => '001234567000089']);
        $invoice = $order->invoice()->firstOrFail();
        cancel($order);
        $creditNote = $order->fresh()->creditNote()->firstOrFail();

        $html = app(CreditNotePdf::class)->html($creditNote);

        expect($html)
            ->toContain('AVOIR')
            ->toContain($creditNote->number)
            ->toContain('Sur facture : '.$invoice->number)
            ->toContain($order->reference)
            ->toContain('ACME SARL')
            ->toContain('-'.$order->items()->first()->quantity)
            ->toContain($creditNote->total_ttc->format("\u{00A0}"))
            ->toContain('Taux 20 %')
            ->toContain('Annulation de la commande');
    });

    it('renders a real PDF cached on the private disk', function () {
        $creditNote = cancel(paidInvoicedOrder())->creditNote()->firstOrFail();
        $pdf = app(CreditNotePdf::class);

        $bytes = $pdf->contents($creditNote);

        expect(substr($bytes, 0, 5))->toBe('%PDF-')
            ->and($creditNote->fresh()->pdf_path)->toBe('credit-notes/'.now()->year.'/'.$creditNote->number.'.pdf')
            ->and($pdf->filename($creditNote))->toBe($creditNote->number.'.pdf');
    });
});

describe('visibility', function () {
    beforeEach(function () {
        $this->owner = User::factory()->customer()->create();
        $this->order = cancel(paidInvoicedOrder(['user_id' => $this->owner->id]));
    });

    it('serves the PDF through the signed link, the owner account or an admin', function () {
        $this->get(route('orders.credit-note', $this->order))->assertForbidden();
        $this->get(URL::signedRoute('orders.credit-note', $this->order))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->owner)->get(route('orders.credit-note', $this->order))->assertOk();
        $this->actingAs(User::factory()->customer()->create())->get(route('orders.credit-note', $this->order))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.orders.credit-note', $this->order))->assertOk();
    });

    it('keeps the admin route away from customers and guests', function () {
        $this->get(route('admin.orders.credit-note', $this->order))->assertRedirect(route('login'));
        $this->actingAs($this->owner)->get(route('admin.orders.credit-note', $this->order))->assertForbidden();
    });

    it('returns 404 when there is no credit note', function () {
        $order = Order::factory()->withItem()->create();

        $this->get(URL::signedRoute('orders.credit-note', $order))->assertNotFound();
    });

    it('links to the credit note from the order page and from the admin order', function () {
        $this->get(URL::signedRoute('orders.show', $this->order))->assertSee('Télécharger l\'avoir '.$this->order->creditNote()->first()->number);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.orders.show', $this->order))
            ->assertInertia(fn ($page) => $page->where('order.credit_note.number', $this->order->creditNote()->first()->number));
    });

    it('does not show a credit note link on an order without one', function () {
        $order = Order::factory()->withItem()->create();

        $this->get(URL::signedRoute('orders.show', $order))->assertDontSee('Télécharger l\'avoir');
    });
});

describe('cancellation email', function () {
    beforeEach(fn () => Mail::fake());

    it('is sent to the customer with the credit note attached when the order was invoiced', function () {
        $order = paidInvoicedOrder(['contact_email' => 'sara@example.com']);
        Mail::fake();

        cancel($order);

        Mail::assertSent(OrderCancelledMail::class, function (OrderCancelledMail $mail) {
            $creditNote = $mail->order->creditNote()->firstOrFail();
            $attachments = $mail->attachments();
            $mail->assertHasSubject('Commande '.$mail->order->reference.' annulée');
            $mail->assertSeeInHtml($creditNote->number);
            $mail->assertSeeInHtml($creditNote->total_ttc->format());

            return $mail->hasTo('sara@example.com')
                && count($attachments) === 1
                && $attachments[0]->as === $creditNote->number.'.pdf'
                && $attachments[0]->mime === 'application/pdf';
        });
    });

    it('is sent without attachment when nothing was invoiced', function () {
        $order = Order::factory()->withItem()->withHistory()->create(['contact_email' => 'sara@example.com']);

        cancel($order);

        Mail::assertSent(OrderCancelledMail::class, function (OrderCancelledMail $mail) {
            $mail->assertSeeInHtml('aucune facture n\'a été émise', false);

            return $mail->hasTo('sara@example.com') && $mail->attachments() === [];
        });
    });
});
