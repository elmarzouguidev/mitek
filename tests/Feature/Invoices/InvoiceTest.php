<?php

use App\Enums\CarrierMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Core\Invoice\Invoice;
use App\Models\Core\Order\Order;
use App\Models\Core\Order\OrderItem;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use App\Services\Core\Invoice\InvoicePdf;
use App\Services\Core\Invoice\IssueInvoice;
use App\Services\Core\Order\TransitionOrderStatus;
use App\Settings\GeneralSettings;
use App\Support\Money;
use Illuminate\Support\Facades\Storage;

function payOrder(Order $order): Order
{
    return app(TransitionOrderStatus::class)->handle($order, OrderStatus::Paid);
}

function submittedPrepaidOrder(array $attributes = [], int $quantity = 2): Order
{
    return Order::factory()->status(OrderStatus::PaymentSubmitted)->withItem(Product::factory()->priced(100)->create(), $quantity)->withConsistentTotals()->create($attributes);
}

describe('issuing', function () {
    it('issues a numbered invoice when the payment is validated', function () {
        $order = payOrder(submittedPrepaidOrder());

        $invoice = $order->invoice()->firstOrFail();

        expect($invoice->number)->toBe('FA-'.now()->year.'-00001')
            ->and($invoice->issued_at->isToday())->toBeTrue();
    });

    it('numbers invoices sequentially without gaps', function () {
        $numbers = collect(range(1, 3))->map(fn () => payOrder(submittedPrepaidOrder())->invoice()->firstOrFail()->number)->all();

        expect($numbers)->toBe(['FA-'.now()->year.'-00001', 'FA-'.now()->year.'-00002', 'FA-'.now()->year.'-00003']);
    });

    it('restarts the sequence every year', function () {
        $this->travelTo(now()->setDate(2026, 12, 30));
        $last2026 = payOrder(submittedPrepaidOrder())->invoice()->firstOrFail()->number;

        $this->travelTo(now()->setDate(2027, 1, 2));
        $first2027 = payOrder(submittedPrepaidOrder())->invoice()->firstOrFail()->number;

        expect($last2026)->toBe('FA-2026-00001')
            ->and($first2027)->toBe('FA-2027-00001');
    });

    it('issues only one invoice per order', function () {
        $order = payOrder(submittedPrepaidOrder());

        $again = app(IssueInvoice::class)->handle($order);

        expect($again->id)->toBe($order->invoice()->firstOrFail()->id)
            ->and(Invoice::count())->toBe(1);
    });

    it('does not issue an invoice before the payment is validated', function () {
        $order = Order::factory()->withItem()->create();

        app(TransitionOrderStatus::class)->handle($order, OrderStatus::PaymentSubmitted);

        expect(Invoice::count())->toBe(0);
    });

    it('does not issue an invoice for a cancelled order', function () {
        $order = Order::factory()->withItem()->create();

        app(TransitionOrderStatus::class)->handle($order, OrderStatus::Cancelled);

        expect(Invoice::count())->toBe(0);
    });

    it('issues the invoice of a cash on delivery order when it is confirmed', function () {
        $order = Order::factory()->method(PaymentMethod::CashOnDelivery)->withItem()->create();

        app(TransitionOrderStatus::class)->handle($order, OrderStatus::Processing);

        expect($order->invoice()->exists())->toBeTrue();
    });

    it('does not issue a second invoice when a prepaid order moves to processing', function () {
        $order = payOrder(submittedPrepaidOrder());

        app(TransitionOrderStatus::class)->handle($order, OrderStatus::Processing);

        expect(Invoice::count())->toBe(1);
    });

    it('snapshots the seller details so later setting changes do not alter the invoice', function () {
        $settings = app(GeneralSettings::class);
        $settings->ice = '001234567000089';
        $settings->if_number = 'IF-1';
        $settings->rc_number = 'RC-1';
        $settings->save();

        $invoice = payOrder(submittedPrepaidOrder())->invoice()->firstOrFail();

        $settings->ice = '999999999999999';
        $settings->legal_name = 'Autre société';
        $settings->save();

        $html = app(InvoicePdf::class)->html($invoice->fresh());
        expect($html)->toContain('001234567000089')->toContain('STE MITEK SARL AU')
            ->not->toContain('999999999999999')->not->toContain('Autre société');
    });
});

describe('content', function () {
    it('shows the number, the buyer, the lines, the VAT breakdown and the totals', function () {
        $order = submittedPrepaidOrder(['company_name' => 'ACME SARL', 'ice' => '001234567000089', 'if_number' => 'IF-77', 'rc_number' => 'RC-88', 'shipping_city' => 'Fès'], 3);
        $order = payOrder($order);
        $invoice = $order->invoice()->firstOrFail();

        $html = app(InvoicePdf::class)->html($invoice);

        expect($html)
            ->toContain($invoice->number)
            ->toContain($order->reference)
            ->toContain('ACME SARL')
            ->toContain('ICE : 001234567000089')
            ->toContain('IF : IF-77')
            ->toContain('RC : RC-88')
            ->toContain('Fès')
            ->toContain($order->items()->first()->name)
            ->toContain($order->items()->first()->reference)
            ->toContain($order->subtotal_ht->plus($order->shipping_ht)->format("\u{00A0}"))
            ->toContain($order->vat_total->format("\u{00A0}"))
            ->toContain($order->total_ttc->format("\u{00A0}"))
            ->toContain('Virement bancaire')
            ->toContain('Réglée le');
    });

    it('breaks the VAT down per rate', function () {
        $order = Order::factory()->status(OrderStatus::PaymentSubmitted)->withItem(Product::factory()->priced(100)->create(), 1)->create();
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => null, 'reference' => 'RED-1', 'name' => 'Reduced VAT item', 'quantity' => 1,
            'unit_price_ht' => Money::fromDecimal(50), 'vat_rate' => 7, 'line_ht' => Money::fromDecimal(50), 'line_vat' => Money::fromDecimal('3.50'), 'line_ttc' => Money::fromDecimal('53.50'), 'weight_grams' => 0,
        ]);
        $order->update(['subtotal_ht' => Money::fromDecimal(150), 'vat_total' => Money::fromDecimal('28.50'), 'total_ttc' => Money::fromDecimal('203.50')]);

        $html = app(InvoicePdf::class)->html(payOrder($order)->invoice()->firstOrFail());

        expect($html)->toContain('Taux 7 %')->toContain('Taux 20 %')->toContain(Money::fromDecimal('3.50')->format("\u{00A0}"));
    });

    it('flags missing company identifiers instead of inventing them', function () {
        $html = app(InvoicePdf::class)->html(payOrder(submittedPrepaidOrder())->invoice()->firstOrFail());

        expect(substr_count($html, '[À COMPLÉTER]'))->toBe(9) // header: ICE, IF, RC; footer: capital, RC, IF, ICE, TP, CNSS
            ->and($html)->toContain('SARL AU')->toContain('Casablanca, Maroc');
    });

    it('shows a consumer as the buyer when there is no company', function () {
        $order = payOrder(submittedPrepaidOrder(['contact_name' => 'Sara Benali']));

        $html = app(InvoicePdf::class)->html($order->invoice()->firstOrFail());

        expect($html)->toContain('Sara Benali')->not->toContain('ICE : 0');
    });

    it('mentions the customer carrier and cash on delivery', function () {
        $order = Order::factory()->method(PaymentMethod::CashOnDelivery)->withItem()->withConsistentTotals()->create(['carrier_mode' => CarrierMode::Own]);
        app(TransitionOrderStatus::class)->handle($order, OrderStatus::Processing);

        $html = app(InvoicePdf::class)->html($order->invoice()->firstOrFail());

        expect($html)->toContain('Transporteur du client')->toContain('Paiement à la livraison');
    });
});

describe('pdf file', function () {
    it('renders a real PDF, stores it on the private disk and reuses it', function () {
        $invoice = payOrder(submittedPrepaidOrder())->invoice()->firstOrFail();
        $pdf = app(InvoicePdf::class);

        $bytes = $pdf->contents($invoice);

        expect(substr($bytes, 0, 5))->toBe('%PDF-')
            ->and(strlen($bytes))->toBeGreaterThan(5000)
            ->and($invoice->fresh()->pdf_path)->toBe('invoices/'.now()->year.'/'.$invoice->number.'.pdf');
        Storage::disk('local')->assertExists($invoice->fresh()->pdf_path);

        Storage::disk('local')->put($invoice->fresh()->pdf_path, 'cached-bytes');
        expect($pdf->contents($invoice->fresh()))->toBe('cached-bytes');
    });

    it('is named after the invoice number', function () {
        $invoice = payOrder(submittedPrepaidOrder())->invoice()->firstOrFail();

        expect(app(InvoicePdf::class)->filename($invoice))->toBe($invoice->number.'.pdf');
    });
});

describe('downloads', function () {
    beforeEach(function () {
        $this->owner = User::factory()->customer()->create();
        $this->order = payOrder(submittedPrepaidOrder(['user_id' => $this->owner->id]));
    });

    it('serves the PDF to a guest with the signed link', function () {
        $this->get(URL::signedRoute('orders.invoice', $this->order))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    });

    it('refuses a guest without a signature', function () {
        $this->get(route('orders.invoice', $this->order))->assertForbidden();
    });

    it('serves the owner and refuses other customers', function () {
        $this->actingAs($this->owner)->get(route('orders.invoice', $this->order))->assertOk();
        $this->actingAs(User::factory()->customer()->create())->get(route('orders.invoice', $this->order))->assertForbidden();
    });

    it('serves administrators', function () {
        $admin = User::factory()->admin()->create();

        $this->get(route('admin.orders.invoice', $this->order))->assertRedirect(route('login'));
        $this->actingAs($this->owner)->get(route('admin.orders.invoice', $this->order))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.orders.invoice', $this->order))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    });

    it('returns 404 while no invoice exists', function () {
        $order = Order::factory()->withItem()->create();

        $this->get(URL::signedRoute('orders.invoice', $order))->assertNotFound();
    });

    it('links to the invoice from the order page only once it exists', function () {
        $without = Order::factory()->withItem()->create();

        $this->get(URL::signedRoute('orders.show', $this->order))->assertSee('Télécharger la facture '.$this->order->invoice()->first()->number);
        $this->get(URL::signedRoute('orders.show', $without))->assertDontSee('Télécharger la facture');
    });

    it('shows the invoice in the admin order page', function () {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.orders.show', $this->order))
            ->assertInertia(fn ($page) => $page->where('order.invoice.number', $this->order->invoice()->first()->number));
    });
});
