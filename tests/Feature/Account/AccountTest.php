<?php

use App\Models\Core\Customer\Address;
use App\Models\Core\Order\Order;
use App\Models\Core\Product\Product;
use App\Models\Core\Quote\QuoteRequest;
use App\Models\User\User;
use App\Services\Core\Invoice\IssueInvoice;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->customer = User::factory()->customer()->create(['name' => 'Sara Benali', 'email' => 'sara@example.com']);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function addressPayload(array $overrides = []): array
{
    return array_merge([
        'label' => 'Bureau',
        'contact_name' => 'Sara Benali',
        'phone' => '0612345678',
        'city' => 'Casablanca',
        'address' => '12 rue des Fleurs',
        'postal_code' => '20000',
        'is_default' => false,
    ], $overrides);
}

it('keeps every account page behind the login', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['account.index', 'account.orders', 'account.quotes', 'account.addresses', 'account.profile']);

it('adds an account link to the header for logged in users', function () {
    $this->get(route('home'))->assertDontSee('Mon compte');
    $this->actingAs($this->customer)->get(route('home'))->assertSee(route('account.index'), false)->assertSee('Mon compte');
});

describe('orders and quotes', function () {
    it('lists only the customer own orders, newest first, with invoice links', function () {
        $mine = Order::factory()->withItem()->create(['user_id' => $this->customer->id, 'reference' => 'MO-MINE']);
        Order::factory()->withItem()->create(['reference' => 'MO-OTHER']);
        Order::factory()->withItem()->create(['user_id' => User::factory()->customer()->create()->id, 'reference' => 'MO-STRANGER']);
        app(IssueInvoice::class)->handle($mine);

        $this->actingAs($this->customer)->get(route('account.orders'))
            ->assertOk()
            ->assertSee('MO-MINE')
            ->assertDontSee('MO-OTHER')
            ->assertDontSee('MO-STRANGER')
            ->assertSee(route('orders.show', $mine), false)
            ->assertSee(route('orders.invoice', $mine), false);
    });

    it('does not offer an invoice link before the invoice exists', function () {
        $order = Order::factory()->withItem()->create(['user_id' => $this->customer->id]);

        $this->actingAs($this->customer)->get(route('account.orders'))->assertDontSee(route('orders.invoice', $order), false);
    });

    it('paginates the orders', function () {
        Order::factory()->count(12)->create(['user_id' => $this->customer->id]);

        $this->actingAs($this->customer)->get(route('account.orders'))->assertSee('page=2', false);
    });

    it('lists only the customer own quotes', function () {
        QuoteRequest::create(['reference' => 'DV-MINE01', 'user_id' => $this->customer->id, 'status' => 'new', 'contact_name' => 'S', 'contact_email' => 's@example.com', 'contact_phone' => '0600000000']);
        QuoteRequest::create(['reference' => 'DV-OTHER1', 'status' => 'new', 'contact_name' => 'O', 'contact_email' => 'o@example.com', 'contact_phone' => '0600000000']);

        $this->actingAs($this->customer)->get(route('account.quotes'))->assertSee('DV-MINE01')->assertDontSee('DV-OTHER1');
    });

    it('shows recent orders and quotes on the overview', function () {
        Order::factory()->withItem()->create(['user_id' => $this->customer->id, 'reference' => 'MO-RECENT']);

        $this->actingAs($this->customer)->get(route('account.index'))->assertSee('Bonjour Sara Benali')->assertSee('MO-RECENT');
    });

    it('does not run extra queries per order', function () {
        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->customer)->get(route('account.orders'))->assertOk();
            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };

        Order::factory()->withItem()->create(['user_id' => $this->customer->id]);
        $count();
        $one = $count();
        Order::factory()->withItem()->count(8)->create(['user_id' => $this->customer->id]);
        $count();

        expect($count())->toBe($one);
    });
});

describe('addresses', function () {
    it('makes the first address the default one', function () {
        $this->actingAs($this->customer)->post(route('account.addresses.store'), addressPayload())->assertRedirect(route('account.addresses'));

        expect(Address::firstOrFail())->is_default->toBeTrue()->user_id->toBe($this->customer->id);
    });

    it('moves the default flag to the newest default address', function () {
        $this->actingAs($this->customer)->post(route('account.addresses.store'), addressPayload(['label' => 'First']));
        $this->actingAs($this->customer)->post(route('account.addresses.store'), addressPayload(['label' => 'Second', 'is_default' => true]));
        $this->actingAs($this->customer)->post(route('account.addresses.store'), addressPayload(['label' => 'Third']));

        expect($this->customer->addresses()->where('is_default', true)->pluck('label')->all())->toBe(['Second']);
    });

    it('lists addresses with the default first', function () {
        $this->actingAs($this->customer)->post(route('account.addresses.store'), addressPayload(['label' => 'Alpha']));
        $this->actingAs($this->customer)->post(route('account.addresses.store'), addressPayload(['label' => 'Zulu', 'is_default' => true]));

        $this->actingAs($this->customer)->get(route('account.addresses'))->assertSeeInOrder(['Zulu', 'Par défaut', 'Alpha']);
    });

    it('updates an address', function () {
        $address = Address::create([...addressPayload(), 'user_id' => $this->customer->id, 'is_default' => true]);

        $this->actingAs($this->customer)->put(route('account.addresses.update', $address), addressPayload(['city' => 'Rabat', 'label' => 'Siège']))->assertRedirect(route('account.addresses'));

        expect($address->fresh())->city->toBe('Rabat')->label->toBe('Siège')->is_default->toBeTrue();
    });

    it('promotes another address when the default one is deleted', function () {
        $default = Address::create([...addressPayload(['label' => 'A']), 'user_id' => $this->customer->id, 'is_default' => true]);
        $other = Address::create([...addressPayload(['label' => 'B']), 'user_id' => $this->customer->id]);

        $this->actingAs($this->customer)->delete(route('account.addresses.destroy', $default))->assertRedirect(route('account.addresses'));

        $this->assertModelMissing($default);
        expect($other->fresh()->is_default)->toBeTrue();
    });

    it('cannot see, edit or delete the address of another customer', function () {
        $foreign = Address::create([...addressPayload(), 'user_id' => User::factory()->customer()->create()->id]);

        $this->actingAs($this->customer)->get(route('account.addresses.edit', $foreign))->assertForbidden();
        $this->actingAs($this->customer)->put(route('account.addresses.update', $foreign), addressPayload(['city' => 'Hacked']))->assertForbidden();
        $this->actingAs($this->customer)->delete(route('account.addresses.destroy', $foreign))->assertForbidden();
        $this->actingAs($this->customer)->get(route('account.addresses'))->assertDontSee($foreign->address);

        expect($foreign->fresh()->city)->toBe('Casablanca');
    });

    it('validates the address', function (array $overrides, string $field) {
        $this->actingAs($this->customer)->post(route('account.addresses.store'), addressPayload($overrides))->assertSessionHasErrors($field);

        expect(Address::count())->toBe(0);
    })->with([
        'label' => [['label' => ''], 'label'],
        'phone' => [['phone' => '12'], 'phone'],
        'city' => [['city' => ''], 'city'],
        'address' => [['address' => ''], 'address'],
    ]);
});

describe('profile', function () {
    it('updates the identity and the company details', function () {
        $this->actingAs($this->customer)->put(route('account.profile.update'), [
            'name' => 'Sara B.', 'email' => 'sara.new@example.com', 'phone' => '0612345678',
            'company_name' => 'Benali SARL', 'ice' => '001234567000089', 'if_number' => 'IF-1', 'rc_number' => 'RC-1',
        ])->assertSessionHas('success');

        $profile = $this->customer->fresh()->profile;
        expect($this->customer->fresh())->name->toBe('Sara B.')->email->toBe('sara.new@example.com')
            ->and($profile)->company_name->toBe('Benali SARL')->ice->toBe('001234567000089')->if_number->toBe('IF-1')->rc_number->toBe('RC-1');
    });

    it('keeps a single profile per customer', function () {
        foreach (['A', 'B'] as $company) {
            $this->actingAs($this->customer)->put(route('account.profile.update'), ['name' => 'Sara', 'email' => 'sara@example.com', 'company_name' => $company]);
        }

        expect($this->customer->profile()->count())->toBe(1)->and($this->customer->profile->company_name)->toBe('B');
    });

    it('rejects an email used by someone else but accepts the own one', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->customer)->put(route('account.profile.update'), ['name' => 'Sara', 'email' => 'taken@example.com'])->assertSessionHasErrors('email');
        $this->actingAs($this->customer)->put(route('account.profile.update'), ['name' => 'Sara', 'email' => 'sara@example.com'])->assertSessionHasNoErrors();
    });

    it('validates the ICE and the phone', function () {
        $this->actingAs($this->customer)->put(route('account.profile.update'), ['name' => 'Sara', 'email' => 'sara@example.com', 'ice' => '123', 'phone' => 'abc'])
            ->assertSessionHasErrors(['ice', 'phone']);
    });

    it('changes the password after checking the current one', function () {
        $this->actingAs($this->customer)->put(route('account.password.update'), ['current_password' => 'wrong', 'password' => 'N3w-passw0rd!!', 'password_confirmation' => 'N3w-passw0rd!!'])
            ->assertSessionHasErrors('current_password');
        expect(Hash::check('password', $this->customer->fresh()->password))->toBeTrue();

        $this->actingAs($this->customer)->put(route('account.password.update'), ['current_password' => 'password', 'password' => 'N3w-passw0rd!!', 'password_confirmation' => 'N3w-passw0rd!!'])
            ->assertSessionHas('success');
        expect(Hash::check('N3w-passw0rd!!', $this->customer->fresh()->password))->toBeTrue();
    });

    it('requires the new password to be confirmed', function () {
        $this->actingAs($this->customer)->put(route('account.password.update'), ['current_password' => 'password', 'password' => 'N3w-passw0rd!!', 'password_confirmation' => 'different'])
            ->assertSessionHasErrors('password');
    });
});

describe('checkout prefill', function () {
    beforeEach(function () {
        enableShipping(30);
        $this->product = Product::factory()->create(['stock_quantity' => 5]);
    });

    it('pre-fills the form from the profile and the default address', function () {
        $this->customer->profile()->create(['phone' => '0600000001', 'company_name' => 'Benali SARL', 'ice' => '001234567000089', 'if_number' => 'IF-9', 'rc_number' => 'RC-9']);
        Address::create([...addressPayload(['label' => 'Other', 'city' => 'Fès']), 'user_id' => $this->customer->id]);
        Address::create([...addressPayload(['label' => 'Main', 'city' => 'Tanger', 'address' => '5 avenue du Port', 'contact_name' => 'Réception Benali']), 'user_id' => $this->customer->id, 'is_default' => true]);

        $this->actingAs($this->customer)->withSession(cartSession([[$this->product, 1]]))->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('value="Réception Benali"', false)
            ->assertSee('value="sara@example.com"', false)
            ->assertSee('value="Benali SARL"', false)
            ->assertSee('value="001234567000089"', false)
            ->assertSee('5 avenue du Port')
            ->assertSee("city: 'Tanger'", false)
            ->assertSee('isCompany: true', false);
    });

    it('falls back to the account name and email without profile or address', function () {
        $this->actingAs($this->customer)->withSession(cartSession([[$this->product, 1]]))->get(route('checkout.show'))
            ->assertSee('value="Sara Benali"', false)
            ->assertSee('value="sara@example.com"', false);
    });

    it('leaves the form empty for guests', function () {
        $this->withSession(cartSession([[$this->product, 1]]))->get(route('checkout.show'))->assertOk()->assertDontSee('sara@example.com');
    });
});
