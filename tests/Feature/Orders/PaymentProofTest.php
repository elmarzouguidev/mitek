<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Core\Order\Order;
use App\Models\Core\Order\OrderPayment;
use App\Models\User\User;
use App\Services\Core\Order\ReviewPayment;
use App\Settings\PaymentSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->order = Order::factory()->withItem()->withHistory()->create();
    $this->url = fn (Order $order) => URL::signedRoute('orders.proof.store', $order);
});

function uploadProof(mixed $test, Order $order, array $files)
{
    return $test->post(URL::signedRoute('orders.proof.store', $order), ['proofs' => $files]);
}

it('records the proof, keeps the file private and moves the order to payment submitted', function () {
    uploadProof($this, $this->order, [UploadedFile::fake()->image('slip.jpg'), UploadedFile::fake()->create('recto-verso.pdf', 200, 'application/pdf')])
        ->assertRedirect(URL::signedRoute('orders.show', $this->order))
        ->assertSessionHas('success');

    $order = $this->order->fresh();
    $payment = $order->payments()->firstOrFail();

    expect($order->status)->toBe(OrderStatus::PaymentSubmitted)
        ->and($payment->status)->toBe(PaymentStatus::Submitted)
        ->and($payment->amount->cents)->toBe($order->total_ttc->cents)
        ->and($payment->method)->toBe(PaymentMethod::BankTransfer)
        ->and($payment->getMedia(OrderPayment::PROOF))->toHaveCount(2)
        ->and($payment->getFirstMedia(OrderPayment::PROOF)->disk)->toBe('local')
        ->and($order->history()->reorder()->latest('id')->first()->to_status)->toBe(OrderStatus::PaymentSubmitted);

    Storage::disk('local')->assertExists($payment->getFirstMedia(OrderPayment::PROOF)->getPathRelativeToRoot());
    Storage::disk('public')->assertDirectoryEmpty('/');
});

it('works for deposits and cheques too', function (PaymentMethod $method) {
    $order = Order::factory()->method($method)->withItem()->withHistory()->create();

    uploadProof($this, $order, [UploadedFile::fake()->image('slip.png')])->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::PaymentSubmitted);
})->with([PaymentMethod::CashDeposit, PaymentMethod::Cheque]);

it('rejects files that are not jpg, png or pdf', function (string $name, string $mime) {
    uploadProof($this, $this->order, [UploadedFile::fake()->create($name, 10, $mime)])->assertSessionHasErrors('proofs.0');

    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and(OrderPayment::count())->toBe(0);
})->with([
    'php script' => ['shell.php', 'application/x-php'],
    'html' => ['page.html', 'text/html'],
    'svg' => ['logo.svg', 'image/svg+xml'],
    'executable' => ['setup.exe', 'application/octet-stream'],
]);

it('rejects a php file disguised with an image extension', function () {
    $path = tempnam(sys_get_temp_dir(), 'proof');
    file_put_contents($path, '<?php echo "pwned";');
    $disguised = new UploadedFile($path, 'proof.jpg', 'image/jpeg', null, true);

    uploadProof($this, $this->order, [$disguised])->assertSessionHasErrors('proofs.0');

    expect(OrderPayment::count())->toBe(0);
});

it('rejects oversized files, empty uploads and too many files', function () {
    uploadProof($this, $this->order, [UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('proofs.0');
    uploadProof($this, $this->order, [])->assertSessionHasErrors('proofs');
    uploadProof($this, $this->order, array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"), range(1, 5)))->assertSessionHasErrors('proofs');

    expect(OrderPayment::count())->toBe(0);
});

it('does not accept a proof for cash on delivery', function () {
    $order = Order::factory()->method(PaymentMethod::CashOnDelivery)->withItem()->create();

    uploadProof($this, $order, [UploadedFile::fake()->image('slip.jpg')])->assertSessionHasErrors('proof');

    expect(OrderPayment::count())->toBe(0);
});

it('does not accept a second proof while the first one is being reviewed', function () {
    uploadProof($this, $this->order, [UploadedFile::fake()->image('first.jpg')]);

    uploadProof($this, $this->order, [UploadedFile::fake()->image('second.jpg')])->assertSessionHasErrors('proof');

    expect(OrderPayment::count())->toBe(1);
});

it('does not accept proofs for paid or cancelled orders', function (OrderStatus $status) {
    $order = Order::factory()->status($status)->withItem()->create();

    uploadProof($this, $order, [UploadedFile::fake()->image('slip.jpg')])->assertSessionHasErrors('proof');
})->with([OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Cancelled]);

it('lets the customer send a new proof after a rejection and shows the reason', function () {
    $admin = User::factory()->admin()->create();
    uploadProof($this, $this->order, [UploadedFile::fake()->image('blurry.jpg')]);
    app(ReviewPayment::class)->reject($this->order->payments()->firstOrFail(), $admin, 'Image illisible, merci de renvoyer.');

    $this->get(URL::signedRoute('orders.show', $this->order))
        ->assertSee('Votre dernier justificatif a été refusé')
        ->assertSee('Image illisible, merci de renvoyer.');

    uploadProof($this, $this->order, [UploadedFile::fake()->image('clear.jpg')])->assertSessionHasNoErrors();

    expect($this->order->fresh()->status)->toBe(OrderStatus::PaymentSubmitted)
        ->and($this->order->payments()->count())->toBe(2);
});

it('shows the bank details from the settings with the amount and the delay', function () {
    $settings = app(PaymentSettings::class);
    $settings->rib = '123 456 789 000 000 000 00';
    $settings->save();

    $this->get(URL::signedRoute('orders.show', $this->order))
        ->assertSee('Banque Populaire')
        ->assertSee('STE MITEK SARL AU')
        ->assertSee('123 456 789 000 000 000 00')
        ->assertSee($this->order->total_ttc->format())
        ->assertSee('48 à 72 heures');
});

it('flags the missing RIB instead of inventing one', function () {
    $this->get(URL::signedRoute('orders.show', $this->order))->assertSee('RIB à communiquer');
});

it('shows the cheque payee and no bank account for cheque orders', function () {
    $order = Order::factory()->method(PaymentMethod::Cheque)->withItem()->create();

    $this->get(URL::signedRoute('orders.show', $order))
        ->assertSee('Chèque à l\'ordre de STE MITEK SARL AU')
        ->assertSee('8 à 20 jours')
        ->assertDontSee('RIB');
});

it('shows the cash on delivery explanation without an upload form', function () {
    $order = Order::factory()->method(PaymentMethod::CashOnDelivery)->withItem()->create();

    $this->get(URL::signedRoute('orders.show', $order))
        ->assertSee('Vous réglez à la livraison')
        ->assertDontSee('Envoyer le justificatif');
});
