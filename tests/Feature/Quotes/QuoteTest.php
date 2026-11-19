<?php

use App\Enums\QuoteStatus;
use App\Mail\QuoteRepliedMail;
use App\Models\Core\Product\Product;
use App\Models\Core\Quote\QuoteRequest;
use App\Models\User\User;
use App\Support\Money;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function quotePayload(array $overrides = []): array
{
    return array_merge([
        'contact_name' => 'Karim Alaoui',
        'contact_email' => 'karim@acme.ma',
        'contact_phone' => '0661234567',
        'company_name' => 'ACME SARL',
        'ice' => '001234567000089',
        'message' => 'Nous cherchons 200 unités avec livraison échelonnée.',
    ], $overrides);
}

beforeEach(function () {
    $this->product = Product::factory()->priced(100)->withTiers([50 => 80])->create(['name' => 'Ramette A4', 'stock_quantity' => 500]);
});

describe('request flow', function () {
    it('offers a quote request from the cart', function () {
        $this->withSession(cartSession([[$this->product, 3]]))->get(route('cart.show'))
            ->assertSee('Demander un devis')
            ->assertSee(route('quotes.create'), false);
    });

    it('needs a cart', function () {
        $this->get(route('quotes.create'))->assertRedirect(route('cart.show'));
        $this->post(route('quotes.store'), quotePayload())->assertSessionHasErrors('cart');

        expect(QuoteRequest::count())->toBe(0);
    });

    it('shows the cart lines with indicative prices and a pre-filled form for a logged in customer', function () {
        $customer = User::factory()->customer()->create(['name' => 'Sara Benali', 'email' => 'sara@example.com']);
        $customer->profile()->create(['phone' => '0612345678', 'company_name' => 'Benali SARL', 'ice' => '001234567000089']);

        $this->actingAs($customer)->withSession(cartSession([[$this->product, 60]]))->get(route('quotes.create'))
            ->assertOk()
            ->assertSee('Ramette A4')
            ->assertSee(Money::fromDecimal(80)->format())
            ->assertSee('value="Sara Benali"', false)
            ->assertSee('value="sara@example.com"', false)
            ->assertSee('value="Benali SARL"', false);
    });

    it('creates the request from the cart with the indicative tier price and keeps the cart', function () {
        $other = Product::factory()->priced(40)->create(['name' => 'Stylo', 'stock_quantity' => 500]);

        $response = $this->withSession(cartSession([[$this->product, 60], [$other, 10]]))->post(route('quotes.store'), quotePayload());

        $quote = QuoteRequest::with('items')->firstOrFail();

        $response->assertRedirect(URL::signedRoute('quotes.show', $quote));
        expect($quote->reference)->toStartWith('DV-')
            ->and($quote->status)->toBe(QuoteStatus::New)
            ->and($quote->user_id)->toBeNull()
            ->and($quote->company_name)->toBe('ACME SARL')
            ->and($quote->items)->toHaveCount(2)
            ->and($quote->items->firstWhere('reference', $this->product->reference)->quantity)->toBe(60)
            ->and($quote->items->firstWhere('reference', $this->product->reference)->unit_price_ht->cents)->toBe(8000)
            ->and(session('cart'))->not->toBeNull();
    });

    it('links the request to the logged in customer', function () {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->withSession(cartSession([[$this->product, 5]]))->post(route('quotes.store'), quotePayload());

        expect(QuoteRequest::firstOrFail()->user_id)->toBe($customer->id);
    });

    it('validates the form', function (array $overrides, string $field) {
        $this->withSession(cartSession([[$this->product, 5]]))->post(route('quotes.store'), quotePayload($overrides))->assertSessionHasErrors($field);

        expect(QuoteRequest::count())->toBe(0);
    })->with([
        'name' => [['contact_name' => ''], 'contact_name'],
        'email' => [['contact_email' => 'nope'], 'contact_email'],
        'phone' => [['contact_phone' => '12'], 'contact_phone'],
        'ice' => [['ice' => '123'], 'ice'],
        'message length' => [['message' => str_repeat('a', 2001)], 'message'],
    ]);

    it('is protected by the honeypot', function () {
        config(['honeypot.enabled' => true]);

        $this->withSession(cartSession([[$this->product, 5]]))->post(route('quotes.store'), quotePayload());

        expect(QuoteRequest::count())->toBe(0);
    });
});

describe('customer page', function () {
    beforeEach(function () {
        $this->owner = User::factory()->customer()->create();
        $this->quote = QuoteRequest::create(['reference' => 'DV-ABC123', 'user_id' => $this->owner->id, 'status' => QuoteStatus::New, 'contact_name' => 'Owner', 'contact_email' => 'o@example.com', 'contact_phone' => '0600000000', 'message' => 'Hello']);
        $this->quote->items()->create(['product_id' => $this->product->id, 'reference' => 'R1', 'name' => 'Ramette A4', 'quantity' => 200, 'unit_price_ht' => Money::fromDecimal(80)]);
    });

    it('opens with the signed link, the owner account or an admin, nobody else', function () {
        $this->get(route('quotes.show', $this->quote))->assertForbidden();
        $this->get(URL::signedRoute('quotes.show', $this->quote))->assertOk()->assertSee('DV-ABC123')->assertSee('Ramette A4')->assertSee('Nous étudions votre demande');
        $this->actingAs($this->owner)->get(route('quotes.show', $this->quote))->assertOk();
        $this->actingAs(User::factory()->customer()->create())->get(route('quotes.show', $this->quote))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('quotes.show', $this->quote))->assertOk();
    });

    it('does not expose quotes by numeric id', function () {
        $this->get('/devis/'.$this->quote->id)->assertNotFound();
    });

    it('shows the reply once the administrator answered', function () {
        $this->quote->update(['status' => QuoteStatus::Replied, 'admin_reply' => 'Remise de 15 % à partir de 200 unités.', 'replied_at' => now()]);

        $this->actingAs($this->owner)->get(route('quotes.show', $this->quote))
            ->assertSee('Notre réponse')
            ->assertSee('Remise de 15 % à partir de 200 unités.')
            ->assertSee('Réponse envoyée');
    });
});

describe('admin', function () {
    beforeEach(function () {
        Mail::fake();
        $this->admin = User::factory()->admin()->create();
        $this->quote = QuoteRequest::create(['reference' => 'DV-ADM001', 'status' => QuoteStatus::New, 'contact_name' => 'Karim Alaoui', 'contact_email' => 'karim@acme.ma', 'contact_phone' => '0661234567', 'company_name' => 'ACME SARL']);
        $this->quote->items()->create(['product_id' => $this->product->id, 'reference' => 'R1', 'name' => 'Ramette A4', 'quantity' => 200, 'unit_price_ht' => Money::fromDecimal(80)]);
    });

    it('keeps the screens away from guests and customers', function () {
        $customer = User::factory()->customer()->create();

        foreach ([['get', route('admin.quotes.index')], ['get', route('admin.quotes.show', $this->quote)], ['post', route('admin.quotes.reply', $this->quote)], ['put', route('admin.quotes.close', $this->quote)]] as [$method, $url]) {
            $this->$method($url)->assertRedirect(route('login'));
            $this->actingAs($customer)->$method($url)->assertForbidden();
            auth()->logout();
        }

        expect($this->quote->fresh()->status)->toBe(QuoteStatus::New);
    });

    it('lists, searches and filters the requests', function () {
        QuoteRequest::create(['reference' => 'DV-OLD001', 'status' => QuoteStatus::Replied, 'contact_name' => 'Someone Else', 'contact_email' => 's@example.com', 'contact_phone' => '0600000000']);

        $refs = fn (array $query) => collect($this->actingAs($this->admin)->get(route('admin.quotes.index', $query))->viewData('page')['props']['quotes']['data'])->pluck('reference')->all();

        expect($refs([]))->toHaveCount(2)
            ->and($refs(['q' => 'acme']))->toBe(['DV-ADM001'])
            ->and($refs(['q' => 'DV-OLD']))->toBe(['DV-OLD001'])
            ->and($refs(['status' => 'new']))->toBe(['DV-ADM001'])
            ->and($refs(['status' => 'replied']))->toBe(['DV-OLD001']);

        $this->actingAs($this->admin)->get(route('admin.quotes.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Quotes/Index')->where('quotes.meta.total', 2)->has('quotes.data.0.status.label'));
    });

    it('shows the request with its items and the customer', function () {
        $this->actingAs($this->admin)->get(route('admin.quotes.show', $this->quote))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Quotes/Show')
                ->where('quote.reference', 'DV-ADM001')
                ->where('quote.contact.email', 'karim@acme.ma')
                ->where('quote.company.name', 'ACME SARL')
                ->where('quote.items.0.quantity', 200)
                ->where('quote.items.0.unit_price_ht', Money::fromDecimal(80)->format()));
    });

    it('replies: status, author, date and an email to the customer with a signed link', function () {
        $this->actingAs($this->admin)->post(route('admin.quotes.reply', $this->quote), ['admin_reply' => 'Remise de 15 % à partir de 200 unités, livraison sous 5 jours.'])
            ->assertSessionHas('success');

        $quote = $this->quote->fresh();
        expect($quote->status)->toBe(QuoteStatus::Replied)
            ->and($quote->replied_by)->toBe($this->admin->id)
            ->and($quote->replied_at)->not->toBeNull();

        Mail::assertSent(QuoteRepliedMail::class, function (QuoteRepliedMail $mail) {
            $mail->assertHasSubject('Réponse à votre demande de devis DV-ADM001');
            $mail->assertSeeInHtml('Remise de 15 % à partir de 200 unités');
            $mail->assertSeeInHtml('Ramette A4');
            $mail->assertSeeInHtml('signature=');

            return $mail->hasTo('karim@acme.ma');
        });
    });

    it('requires a real reply', function (string $reply) {
        $this->actingAs($this->admin)->post(route('admin.quotes.reply', $this->quote), ['admin_reply' => $reply])->assertSessionHasErrors('admin_reply');

        expect($this->quote->fresh()->status)->toBe(QuoteStatus::New);
        Mail::assertNothingSent();
    })->with(['empty' => '', 'too short' => 'ok', 'too long' => str_repeat('a', 5001)]);

    it('lets the administrator answer again', function () {
        $this->actingAs($this->admin)->post(route('admin.quotes.reply', $this->quote), ['admin_reply' => 'Première réponse.']);
        $this->actingAs($this->admin)->post(route('admin.quotes.reply', $this->quote), ['admin_reply' => 'Réponse corrigée.']);

        expect($this->quote->fresh()->admin_reply)->toBe('Réponse corrigée.');
        Mail::assertSentCount(2);
    });

    it('closes a request and refuses further replies', function () {
        $this->actingAs($this->admin)->put(route('admin.quotes.close', $this->quote))->assertSessionHas('success');

        $this->actingAs($this->admin)->post(route('admin.quotes.reply', $this->quote), ['admin_reply' => 'Trop tard pour répondre.'])->assertSessionHasErrors('admin_reply');

        expect($this->quote->fresh()->status)->toBe(QuoteStatus::Closed)
            ->and($this->quote->fresh()->admin_reply)->toBeNull();
        Mail::assertNothingSent();
    });

    it('counts the requests waiting for a reply on the dashboard', function () {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertInertia(fn (Assert $page) => $page->where('stats.new_quotes', 1));

        $this->quote->update(['status' => QuoteStatus::Replied]);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertInertia(fn (Assert $page) => $page->where('stats.new_quotes', 0));
    });
});
