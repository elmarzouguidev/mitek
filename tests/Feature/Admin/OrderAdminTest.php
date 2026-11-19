<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Core\Order\Order;
use App\Models\Core\Order\OrderPayment;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
});

/**
 * An order whose customer already uploaded a proof (status "payment submitted").
 */
function submittedOrder(array $attributes = []): Order
{
    $order = Order::factory()->withItem()->withHistory()->status(OrderStatus::PaymentSubmitted)->create($attributes);
    $payment = $order->payments()->create(['method' => $order->payment_method, 'amount' => $order->total_ttc, 'status' => PaymentStatus::Submitted]);
    $payment->addMedia(UploadedFile::fake()->image('slip.jpg'))->toMediaCollection(OrderPayment::PROOF);

    return $order;
}

it('keeps every order screen away from guests and customers', function () {
    $order = submittedOrder();
    $payment = $order->payments()->firstOrFail();
    $customer = User::factory()->customer()->create(['id' => 999]);

    $routes = [
        ['get', route('admin.orders.index')],
        ['get', route('admin.orders.show', $order)],
        ['put', route('admin.orders.status', $order)],
        ['post', route('admin.orders.proofs.store', $order)],
        ['get', route('admin.orders.proofs.show', [$payment, $payment->getFirstMedia(OrderPayment::PROOF)->id])],
        ['post', route('admin.orders.payments.validate', $payment)],
        ['post', route('admin.orders.payments.reject', $payment)],
    ];

    foreach ($routes as [$method, $url]) {
        $this->$method($url)->assertRedirect(route('login'));
        $this->actingAs($customer)->$method($url)->assertForbidden();
        auth()->logout();
    }

    expect($order->fresh()->status)->toBe(OrderStatus::PaymentSubmitted);
});

describe('listing', function () {
    it('lists orders, newest first, with searchable fields', function () {
        Order::factory()->create(['reference' => 'MO-OLD', 'created_at' => now()->subDay()]);
        Order::factory()->create(['reference' => 'MO-NEW', 'contact_name' => 'Karim Alaoui', 'contact_email' => 'karim@example.com', 'contact_phone' => '0661000000', 'company_name' => 'ACME SARL']);

        $names = fn (array $query) => collect($this->actingAs($this->admin)->get(route('admin.orders.index', $query))
            ->viewData('page')['props']['orders']['data'])->pluck('reference')->all();

        expect($names([]))->toBe(['MO-NEW', 'MO-OLD'])
            ->and($names(['q' => 'karim']))->toBe(['MO-NEW'])
            ->and($names(['q' => 'karim@example']))->toBe(['MO-NEW'])
            ->and($names(['q' => '0661']))->toBe(['MO-NEW'])
            ->and($names(['q' => 'acme']))->toBe(['MO-NEW'])
            ->and($names(['q' => 'MO-OLD']))->toBe(['MO-OLD']);
    });

    it('filters by status and payment method', function () {
        Order::factory()->create(['reference' => 'MO-A']);
        Order::factory()->status(OrderStatus::Paid)->method(PaymentMethod::Cheque)->create(['reference' => 'MO-B']);

        $names = fn (array $query) => collect($this->actingAs($this->admin)->get(route('admin.orders.index', $query))
            ->viewData('page')['props']['orders']['data'])->pluck('reference')->all();

        expect($names(['status' => 'paid']))->toBe(['MO-B'])
            ->and($names(['payment_method' => 'cheque']))->toBe(['MO-B'])
            ->and($names(['status' => 'pending_payment', 'payment_method' => 'cheque']))->toBe([]);
    });

    it('rejects unknown filter values and sort columns', function () {
        $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'bogus']))->assertSessionHasErrors('status');
        $this->actingAs($this->admin)->get(route('admin.orders.index', ['sort' => 'password']))->assertSessionHasErrors('sort');
    });

    it('paginates and formats the rows', function () {
        Order::factory()->count(17)->create();

        $this->actingAs($this->admin)->get(route('admin.orders.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Orders/Index')
                ->has('orders.data', 15)
                ->where('orders.meta.total', 17)
                ->has('orders.data.0.status.label')
                ->has('orders.data.0.total_ttc'));
    });
});

describe('detail', function () {
    it('exposes items, payments with proof links, history and the allowed actions', function () {
        $order = submittedOrder(['company_name' => 'ACME SARL', 'ice' => '001234567000089']);

        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Orders/Show')
                ->where('order.reference', $order->reference)
                ->where('order.status.value', 'payment_submitted')
                ->where('order.company.ice', '001234567000089')
                ->has('order.items', 1)
                ->has('order.history', 1)
                ->has('order.payments', 1)
                ->where('order.payments.0.status', 'submitted')
                ->has('order.payments.0.proofs', 1)
                ->where('order.payments.0.proofs.0.is_image', true)
                ->where('order.next_statuses.0.value', 'cancelled')
                ->where('order.accepts_proof', false));
    });

    it('only offers manual statuses, never the payment ones', function () {
        $paid = Order::factory()->status(OrderStatus::Paid)->create();
        $cod = Order::factory()->method(PaymentMethod::CashOnDelivery)->create();

        $this->actingAs($this->admin)->get(route('admin.orders.show', $paid))
            ->assertInertia(fn (Assert $page) => $page->where('order.next_statuses.0.value', 'processing')->where('order.next_statuses.1.value', 'cancelled'));

        $this->actingAs($this->admin)->get(route('admin.orders.show', $cod))
            ->assertInertia(fn (Assert $page) => $page->where('order.next_statuses.0.value', 'processing')->where('order.next_statuses.1.value', 'cancelled'));
    });

    it('does not run extra queries per item or payment', function () {
        $count = function (Order $order) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->admin)->get(route('admin.orders.show', $order))->assertOk();
            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };

        $small = submittedOrder();
        $big = submittedOrder();
        foreach (range(1, 5) as $index) {
            Order::factory()->withItem(Product::factory()->create(), 2)->create();
        }
        $big->items()->createMany(Order::factory()->withItem()->create()->items()->get()->map->only(['reference', 'name', 'quantity', 'unit_price_ht', 'vat_rate', 'line_ht', 'line_vat', 'line_ttc'])->all());

        $count($small); // warms the settings cache
        $smallQueries = $count($small);

        expect($count($big))->toBe($smallQueries);
    });
});

describe('payment review', function () {
    it('validates a payment: payment validated, order paid, history by the admin', function () {
        $order = submittedOrder();
        $payment = $order->payments()->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.orders.payments.validate', $payment), ['note' => 'Virement vu sur le relevé'])
            ->assertSessionHas('success');

        $order->refresh();
        $payment->refresh();
        $entry = $order->history()->reorder()->latest('id')->first();

        expect($order->status)->toBe(OrderStatus::Paid)
            ->and($order->paid_at)->not->toBeNull()
            ->and($payment->status)->toBe(PaymentStatus::Validated)
            ->and($payment->reviewed_by)->toBe($this->admin->id)
            ->and($payment->reviewed_at)->not->toBeNull()
            ->and($payment->admin_note)->toBe('Virement vu sur le relevé')
            ->and($entry->to_status)->toBe(OrderStatus::Paid)
            ->and($entry->user_id)->toBe($this->admin->id);
    });

    it('rejects a payment with a mandatory reason and sends the order back to pending payment', function () {
        $order = submittedOrder();
        $payment = $order->payments()->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.orders.payments.reject', $payment), ['note' => ''])->assertSessionHasErrors('note');
        expect($order->fresh()->status)->toBe(OrderStatus::PaymentSubmitted);

        $this->actingAs($this->admin)->post(route('admin.orders.payments.reject', $payment), ['note' => 'Montant incorrect'])->assertSessionHas('success');

        expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment)
            ->and($payment->fresh()->status)->toBe(PaymentStatus::Rejected)
            ->and($payment->fresh()->admin_note)->toBe('Montant incorrect')
            ->and($order->fresh()->paid_at)->toBeNull();
    });

    it('cannot review the same payment twice', function () {
        $order = submittedOrder();
        $payment = $order->payments()->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.orders.payments.validate', $payment));
        $this->actingAs($this->admin)->post(route('admin.orders.payments.validate', $payment))->assertSessionHasErrors('payment');
        $this->actingAs($this->admin)->post(route('admin.orders.payments.reject', $payment), ['note' => 'late'])->assertSessionHasErrors('payment');

        expect($order->fresh()->status)->toBe(OrderStatus::Paid)
            ->and($order->history()->where('to_status', 'paid')->count())->toBe(1);
    });

    it('lets an administrator attach a proof on behalf of the customer', function () {
        $order = Order::factory()->withItem()->withHistory()->create();

        $this->actingAs($this->admin)->post(route('admin.orders.proofs.store', $order), ['proofs' => [UploadedFile::fake()->image('whatsapp.jpg')]])
            ->assertSessionHas('success');

        expect($order->fresh()->status)->toBe(OrderStatus::PaymentSubmitted)
            ->and($order->payments()->firstOrFail()->getMedia(OrderPayment::PROOF))->toHaveCount(1)
            ->and($order->history()->reorder()->latest('id')->first()->user_id)->toBe($this->admin->id);
    });
});

describe('proof files', function () {
    it('streams the proof to administrators with hardening headers', function () {
        $payment = submittedOrder()->payments()->firstOrFail();
        $media = $payment->getFirstMedia(OrderPayment::PROOF);

        $this->actingAs($this->admin)->get(route('admin.orders.proofs.show', [$payment, $media->id]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');
    });

    it('does not serve a file of another payment', function () {
        $first = submittedOrder()->payments()->firstOrFail();
        $second = submittedOrder()->payments()->firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.orders.proofs.show', [$first, $second->getFirstMedia(OrderPayment::PROOF)->id]))->assertNotFound();
    });

    it('stores proofs outside the public disk', function () {
        $media = submittedOrder()->payments()->firstOrFail()->getFirstMedia(OrderPayment::PROOF);

        expect($media->disk)->toBe('local')
            ->and(config('filesystems.disks.local.root'))->not->toContain('public');
    });
});

describe('status changes', function () {
    it('moves a paid order to processing then ships it with a tracking number', function () {
        $order = Order::factory()->status(OrderStatus::Paid)->withItem()->withHistory()->create();

        $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => 'processing'])->assertSessionHas('success');
        $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => 'shipped'])->assertSessionHasErrors('tracking_number');
        expect($order->fresh()->status)->toBe(OrderStatus::Processing);

        $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => 'shipped', 'tracking_number' => 'AMANA-123', 'tracking_carrier' => 'Amana', 'note' => 'Colis remis au transporteur'])
            ->assertSessionHas('success');

        $order->refresh();
        expect($order->status)->toBe(OrderStatus::Shipped)
            ->and($order->tracking_number)->toBe('AMANA-123')
            ->and($order->tracking_carrier)->toBe('Amana')
            ->and($order->shipped_at)->not->toBeNull();

        $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => 'delivered'])->assertSessionHas('success');
        expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
    });

    it('reports an illegal transition instead of changing the order', function () {
        $order = Order::factory()->status(OrderStatus::Paid)->create();

        $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => 'delivered'])->assertSessionHas('error');

        expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    });

    it('cannot mark an order paid by hand', function (string $status) {
        $order = Order::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => $status])->assertSessionHasErrors('status');

        expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
    })->with(['paid', 'payment_submitted', 'pending_payment', 'bogus']);

    it('confirms a cash on delivery order', function () {
        $order = Order::factory()->method(PaymentMethod::CashOnDelivery)->withItem()->withHistory()->create();

        $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => 'processing'])->assertSessionHas('success');

        expect($order->fresh()->status)->toBe(OrderStatus::Processing);
    });

    it('cancels an order and restocks it', function () {
        $product = Product::factory()->create(['stock_quantity' => 6]);
        $order = Order::factory()->withItem($product, 4)->withHistory()->create();

        $this->actingAs($this->admin)->put(route('admin.orders.status', $order), ['status' => 'cancelled', 'note' => 'Client injoignable'])->assertSessionHas('success');

        expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
            ->and($product->fresh()->stock_quantity)->toBe(10)
            ->and($order->history()->reorder()->latest('id')->first()->note)->toBe('Client injoignable');
    });
});
