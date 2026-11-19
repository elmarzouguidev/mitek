<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Core\Order\Order;
use App\Models\Core\Product\Product;
use App\Models\Core\Shipping\ShippingZone;
use App\Models\User\User;
use App\Settings\ShippingSettings;
use App\Support\Money;

beforeEach(function () {
    enableShipping(30);
    $this->product = Product::factory()->priced(100)->create(['name' => 'Toner noir', 'stock_quantity' => 10, 'weight_grams' => 500]);
});

function checkout(mixed $test, array $overrides = [])
{
    return $test->post(route('checkout.store'), checkoutPayload($overrides));
}

it('redirects an empty cart to the cart page', function () {
    $this->get(route('checkout.show'))->assertRedirect(route('cart.show'));
});

it('shows the checkout form with the cart summary and the cash on delivery limit', function () {
    $this->withSession(cartSession([[$this->product, 2]]))->get(route('checkout.show'))
        ->assertOk()
        ->assertSee('Toner noir')
        ->assertSee(Money::fromDecimal(240)->format())
        ->assertSee('Disponible jusqu\'à '.Money::fromDecimal(2000)->format().' TTC')
        ->assertSee('Virement bancaire')
        ->assertSee('Mon propre transporteur');
});

it('places an order, snapshots the lines and computes the totals', function () {
    $this->withSession(cartSession([[$this->product, 2]]));

    $response = checkout($this);

    $order = Order::with('items')->firstOrFail();

    $response->assertRedirect(URL::signedRoute('orders.show', $order));
    expect($order->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->payment_method)->toBe(PaymentMethod::BankTransfer)
        ->and($order->reference)->toStartWith('MO-')
        ->and($order->subtotal_ht->cents)->toBe(20000)
        ->and($order->shipping_ht->cents)->toBe(3000)
        ->and($order->vat_total->cents)->toBe(4600) // 40 on the items + 6 on the shipping
        ->and($order->total_ttc->cents)->toBe(27600)
        ->and($order->total_weight_grams)->toBe(1000)
        ->and($order->shipping_zone_name)->toBe('Default zone')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items[0]->name)->toBe('Toner noir')
        ->and($order->items[0]->quantity)->toBe(2)
        ->and($order->items[0]->unit_price_ht->cents)->toBe(10000)
        ->and($order->items[0]->line_ttc->cents)->toBe(24000);
});

it('keeps order lines unchanged when the catalogue changes later', function () {
    $this->withSession(cartSession([[$this->product, 1]]));
    checkout($this);

    $this->product->update(['name' => 'Renamed', 'price_ht' => Money::fromDecimal(999)]);
    $item = Order::firstOrFail()->items()->firstOrFail();

    expect($item->name)->toBe('Toner noir')
        ->and($item->unit_price_ht->cents)->toBe(10000);
});

it('applies the quantity tier to the order lines', function () {
    $product = Product::factory()->priced(100)->withTiers([5 => 80])->create(['stock_quantity' => 50]);
    $this->withSession(cartSession([[$product, 5]]));

    checkout($this);

    expect(Order::firstOrFail()->items()->firstOrFail()->unit_price_ht->cents)->toBe(8000);
});

it('reserves the stock, clears the cart and writes the first history entry', function () {
    $this->withSession(cartSession([[$this->product, 3]]));

    checkout($this);

    $order = Order::firstOrFail();
    expect($this->product->fresh()->stock_quantity)->toBe(7)
        ->and(session('cart'))->toBeNull()
        ->and($order->history()->count())->toBe(1)
        ->and($order->history()->first()->to_status)->toBe(OrderStatus::PendingPayment);
});

it('links the order to the logged in customer', function () {
    $customer = User::factory()->customer()->create();
    $this->withSession(cartSession([[$this->product, 1]]));

    $this->actingAs($customer)->post(route('checkout.store'), checkoutPayload());

    expect(Order::firstOrFail()->user_id)->toBe($customer->id);
});

it('lets a guest order without an account', function () {
    $this->withSession(cartSession([[$this->product, 1]]));

    checkout($this);

    expect(Order::firstOrFail()->user_id)->toBeNull();
});

it('stores the company details for B2B orders and ignores them otherwise', function () {
    $this->withSession(cartSession([[$this->product, 1]]));
    checkout($this, ['is_company' => true, 'company_name' => 'ACME SARL', 'ice' => '001234567000089', 'if_number' => '12345678', 'rc_number' => '98765']);

    $b2b = Order::firstOrFail();
    expect($b2b->company_name)->toBe('ACME SARL')
        ->and($b2b->ice)->toBe('001234567000089')
        ->and($b2b->if_number)->toBe('12345678')
        ->and($b2b->rc_number)->toBe('98765');

    $this->withSession(cartSession([[$this->product, 1]]));
    checkout($this, ['is_company' => false, 'company_name' => 'Ignored', 'ice' => '001234567000089']);

    expect(Order::latest('id')->firstOrFail()->company_name)->toBeNull();
});

it('validates the checkout form', function (array $overrides, string|array $errors) {
    $this->withSession(cartSession([[$this->product, 1]]));

    checkout($this, $overrides)->assertSessionHasErrors($errors);

    expect(Order::count())->toBe(0);
})->with([
    'name required' => [['contact_name' => ''], 'contact_name'],
    'invalid email' => [['contact_email' => 'nope'], 'contact_email'],
    'invalid phone' => [['contact_phone' => '123'], 'contact_phone'],
    'address required' => [['shipping_address' => ''], 'shipping_address'],
    'city required' => [['shipping_city' => ''], 'shipping_city'],
    'unknown payment method' => [['payment_method' => 'card'], 'payment_method'],
    'unknown carrier mode' => [['carrier_mode' => 'drone'], 'carrier_mode'],
    'company name required for B2B' => [['is_company' => true, 'company_name' => ''], 'company_name'],
    'ICE must have 15 digits' => [['is_company' => true, 'company_name' => 'ACME', 'ice' => '123'], 'ice'],
    'own carrier needs a note' => [['carrier_mode' => 'own', 'own_carrier_note' => ''], 'own_carrier_note'],
]);

it('accepts local and international Moroccan phone formats', function (string $phone) {
    $this->withSession(cartSession([[$this->product, 1]]));

    checkout($this, ['contact_phone' => $phone])->assertSessionHasNoErrors();
})->with(['local' => '0612345678', 'international' => '+212612345678', 'spaced' => '06 12 34 56 78']);

it('refuses to order an empty cart', function () {
    checkout($this)->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0);
});

it('refuses when the stock dropped since the cart was filled', function () {
    $this->withSession(cartSession([[$this->product, 5]]));
    $this->product->update(['stock_quantity' => 2]);

    checkout($this)->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0)
        ->and($this->product->fresh()->stock_quantity)->toBe(2);
});

it('refuses when shipping is not available for the weight', function () {
    ShippingZone::query()->delete();
    enableShipping(30)->rates()->delete();

    $this->withSession(cartSession([[$this->product, 1]]));

    checkout($this)->assertSessionHasErrors('shipping_city');

    expect(Order::count())->toBe(0);
});

it('uses the own carrier fee instead of the grid', function () {
    $settings = app(ShippingSettings::class);
    $settings->own_carrier_fee_cents = 5000;
    $settings->save();
    $this->withSession(cartSession([[$this->product, 1]]));

    checkout($this, ['carrier_mode' => 'own', 'own_carrier_note' => 'DHL, chauffeur Karim'])->assertSessionHasNoErrors();

    $order = Order::firstOrFail();
    expect($order->shipping_ht->cents)->toBe(5000)
        ->and($order->shipping_zone_id)->toBeNull()
        ->and($order->own_carrier_note)->toBe('DHL, chauffeur Karim');
});

it('does not keep the own carrier note when the order is shipped by Mitek', function () {
    $this->withSession(cartSession([[$this->product, 1]]));

    checkout($this, ['carrier_mode' => 'mitek', 'own_carrier_note' => 'ignored']);

    expect(Order::firstOrFail()->own_carrier_note)->toBeNull();
});

describe('cash on delivery', function () {
    it('is accepted up to 2,000 DH TTC, bound included', function () {
        ShippingZone::query()->delete();
        enableShipping(0);
        $product = Product::factory()->priced('1666.67')->create(['stock_quantity' => 5]); // 2,000.00 TTC exactly
        $this->withSession(cartSession([[$product, 1]]));

        checkout($this, ['payment_method' => 'cod'])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        expect($order->total_ttc->cents)->toBe(200000)
            ->and($order->payment_method)->toBe(PaymentMethod::CashOnDelivery);
    });

    it('is refused one centime above the limit, even when the form is tampered with', function () {
        ShippingZone::query()->delete();
        enableShipping(0);
        $product = Product::factory()->priced('1666.68')->create(['stock_quantity' => 5]); // 2,000.02 TTC
        $this->withSession(cartSession([[$product, 1]]));

        checkout($this, ['payment_method' => 'cod'])->assertSessionHasErrors('payment_method');

        expect(Order::count())->toBe(0)
            ->and($product->fresh()->stock_quantity)->toBe(5);
    });

    it('counts the shipping in the total compared to the limit', function () {
        ShippingZone::query()->delete();
        enableShipping(100); // 1,000 HT + 100 shipping HT = 1,320 TTC
        $product = Product::factory()->priced(1000)->create(['stock_quantity' => 5]);
        $this->withSession(cartSession([[$product, 1]]));
        checkout($this, ['payment_method' => 'cod'])->assertSessionHasNoErrors();

        $product2 = Product::factory()->priced(1600)->create(['stock_quantity' => 5]); // 1,920 + 144 shipping VAT incl. > 2,000
        $this->withSession(cartSession([[$product2, 1]]));
        checkout($this, ['payment_method' => 'cod'])->assertSessionHasErrors('payment_method');
    });

    it('stays available for other payment methods above the limit', function () {
        $product = Product::factory()->priced(5000)->create(['stock_quantity' => 5]);
        $this->withSession(cartSession([[$product, 1]]));

        checkout($this, ['payment_method' => 'bank_transfer'])->assertSessionHasNoErrors();

        expect(Order::count())->toBe(1);
    });
});

describe('estimate endpoint', function () {
    it('returns the shipping cost, the total and the usable payment methods', function () {
        $this->withSession(cartSession([[$this->product, 2]]))
            ->getJson(route('checkout.estimate', ['ville' => 'Casablanca', 'transporteur' => 'mitek']))
            ->assertOk()
            ->assertJsonPath('quote.shipping_ht.cents', 3000)
            ->assertJsonPath('quote.total_ttc.cents', 27600)
            ->assertJsonPath('quote.zone', 'Default zone')
            ->assertJsonPath('methods', ['bank_transfer', 'cash_deposit', 'cod', 'cheque']);
    });

    it('drops cash on delivery when the total exceeds the limit', function () {
        $product = Product::factory()->priced(5000)->create(['stock_quantity' => 5]);

        $this->withSession(cartSession([[$product, 1]]))
            ->getJson(route('checkout.estimate', ['ville' => 'Casablanca', 'transporteur' => 'mitek']))
            ->assertJsonPath('methods', ['bank_transfer', 'cash_deposit', 'cheque']);
    });

    it('explains when shipping is unavailable', function () {
        ShippingZone::query()->delete();

        $this->withSession(cartSession([[$this->product, 1]]))
            ->getJson(route('checkout.estimate', ['ville' => 'Ifrane', 'transporteur' => 'mitek']))
            ->assertUnprocessable()
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'ne livrons pas'));
    });

    it('rejects an empty cart and invalid input', function () {
        $this->getJson(route('checkout.estimate', ['ville' => 'Casablanca', 'transporteur' => 'mitek']))->assertUnprocessable();
        $this->withSession(cartSession([[$this->product, 1]]))
            ->getJson(route('checkout.estimate', ['transporteur' => 'drone']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ville', 'transporteur']);
    });
});

it('rejects submissions that fail the honeypot', function () {
    config(['honeypot.enabled' => true]);
    $this->withSession(cartSession([[$this->product, 1]]));

    checkout($this);

    expect(Order::count())->toBe(0);
});
