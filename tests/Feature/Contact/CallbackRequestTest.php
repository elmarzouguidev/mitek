<?php

use App\Enums\CallbackStatus;
use App\Events\CallbackRequested;
use App\Mail\AdminNotificationMail;
use App\Models\Core\Content\CallbackRequest;
use App\Models\User\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function callbackPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sara Benali',
        'phone' => '0612345678',
        'subject' => 'quote',
        'time_slot' => 'afternoon',
        'note' => 'Plutôt après 15 h, merci.',
    ], $overrides);
}

beforeEach(function () {
    Mail::fake();
});

describe('storefront', function () {
    it('serves the no-JavaScript page, noindex, with one h1 and the form', function () {
        $response = $this->get(route('callback.create'))
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertSee('name="time_slot"', false)
            ->assertSee('<meta name="robots" content="noindex,follow">', false);

        assertHeadingOutline($response->getContent());
    });

    it('links to the page from the header, and opens the dialog everywhere except on the page itself', function () {
        $home = $this->get(route('home'))
            ->assertSee('Être rappelé')
            ->assertSee('href="'.route('callback.create').'"', false)
            ->assertSee('aria-labelledby="callback-modal-title"', false)
            ->assertSee('aria-modal="true"', false);

        expect(substr_count($home->getContent(), 'id="cb-m-phone"'))->toBe(1);

        $page = $this->get(route('callback.create'))->assertDontSee('callback-modal-title', false);
        expect(substr_count($page->getContent(), 'id="cb-phone"'))->toBe(1);
    });

    it('stores a request, redirects with a confirmation and tells the team', function () {
        Event::fake([CallbackRequested::class]);

        $this->post(route('callback.store'), callbackPayload())
            ->assertRedirect(route('callback.create'))
            ->assertSessionHas('success');

        $callback = CallbackRequest::sole();
        expect($callback->name)->toBe('Sara Benali')
            ->and($callback->status)->toBe(CallbackStatus::New)
            ->and($callback->time_slot->value)->toBe('afternoon');

        Event::assertDispatched(CallbackRequested::class, fn (CallbackRequested $event) => $event->callback->is($callback));
    });

    it('answers JSON for the dialog', function () {
        $this->postJson(route('callback.store'), callbackPayload())
            ->assertCreated()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'demande de rappel'));

        expect(CallbackRequest::count())->toBe(1);
    });

    it('answers validation errors as JSON 422', function () {
        $this->postJson(route('callback.store'), callbackPayload(['phone' => '12', 'subject' => 'nope']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'subject']);

        expect(CallbackRequest::count())->toBe(0);
    });

    it('validates the form', function (array $overrides, string $field) {
        $this->post(route('callback.store'), callbackPayload($overrides))->assertSessionHasErrors($field);

        expect(CallbackRequest::count())->toBe(0);
    })->with([
        'name' => [['name' => ''], 'name'],
        'phone missing' => [['phone' => ''], 'phone'],
        'phone invalid' => [['phone' => '12'], 'phone'],
        'subject' => [['subject' => 'hack'], 'subject'],
        'slot' => [['time_slot' => 'midnight'], 'time_slot'],
        'note too long' => [['note' => str_repeat('a', 501)], 'note'],
    ]);

    it('keeps the note optional', function () {
        $this->post(route('callback.store'), callbackPayload(['note' => null]))->assertRedirect();

        expect(CallbackRequest::sole()->note)->toBeNull();
    });

    it('is protected by the honeypot', function () {
        config(['honeypot.enabled' => true]);

        $this->post(route('callback.store'), callbackPayload());

        expect(CallbackRequest::count())->toBe(0);
    });

    it('is rate limited to five attempts a minute', function () {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('callback.store'), callbackPayload(['name' => '']));
        }

        $this->post(route('callback.store'), callbackPayload())->assertStatus(429);
        expect(CallbackRequest::count())->toBe(0);
    });

    it('is not crawled and not in the sitemap', function () {
        $this->get('/robots.txt')->assertSee('Disallow: /rappel');
    });

    it('notifies the admin email with the phone, subject and slot', function () {
        $this->post(route('callback.store'), callbackPayload(['name' => 'Karim', 'phone' => '0600112233', 'note' => 'Urgent']));

        $callback = CallbackRequest::sole();

        Mail::assertSent(AdminNotificationMail::class, function (AdminNotificationMail $mail) use ($callback) {
            $mail->assertHasSubject('[Mitek.ma] Demande de rappel : Karim');
            $mail->assertSeeInHtml('0600112233');
            $mail->assertSeeInHtml('Un devis');
            $mail->assertSeeInHtml('Après-midi');
            $mail->assertSeeInHtml('Urgent');
            $mail->assertSeeInHtml(route('admin.callbacks.show', $callback), false);

            return $mail->hasTo('contact@mitek.ma');
        });
    });
});

describe('admin', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->callback = CallbackRequest::factory()->create(['name' => 'Sara Benali', 'phone' => '0612345678']);
    });

    it('keeps the screens away from guests and customers', function () {
        $customer = User::factory()->customer()->create();

        foreach ([['get', route('admin.callbacks.index')], ['get', route('admin.callbacks.show', $this->callback)], ['put', route('admin.callbacks.update', $this->callback)]] as [$method, $url]) {
            $this->$method($url)->assertRedirect(route('login'));
            $this->actingAs($customer)->$method($url, ['status' => 'closed'])->assertForbidden();
            auth()->logout();
        }

        expect($this->callback->fresh()->status)->toBe(CallbackStatus::New);
    });

    it('lists, searches and filters by status', function () {
        CallbackRequest::factory()->called()->create(['name' => 'Karim Idrissi', 'phone' => '0699887766']);

        $names = fn (array $query) => collect($this->actingAs($this->admin)->get(route('admin.callbacks.index', $query))->viewData('page')['props']['callbacks']['data'])->pluck('name')->all();

        expect($names([]))->toHaveCount(2)
            ->and($names(['status' => 'new']))->toBe(['Sara Benali'])
            ->and($names(['status' => 'called']))->toBe(['Karim Idrissi'])
            ->and($names(['q' => 'karim']))->toBe(['Karim Idrissi'])
            ->and($names(['q' => '0699']))->toBe(['Karim Idrissi'])
            ->and($names(['q' => '%']))->toBe([]);

        $this->actingAs($this->admin)->get(route('admin.callbacks.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Callbacks/Index')->where('callbacks.meta.total', 2));
    });

    it('shows a request', function () {
        $this->actingAs($this->admin)->get(route('admin.callbacks.show', $this->callback))
            ->assertInertia(fn (Assert $page) => $page->component('Callbacks/Show')->where('callback.name', 'Sara Benali')->where('callback.status', 'new'));
    });

    it('records when the customer was called, once', function () {
        $this->actingAs($this->admin)->put(route('admin.callbacks.update', $this->callback), ['status' => 'called', 'internal_notes' => 'Laissé un message'])
            ->assertSessionHas('success');

        $first = $this->callback->fresh();
        expect($first->status)->toBe(CallbackStatus::Called)
            ->and($first->contacted_at)->not->toBeNull()
            ->and($first->internal_notes)->toBe('Laissé un message');

        $this->travel(2)->hours();
        $this->actingAs($this->admin)->put(route('admin.callbacks.update', $this->callback), ['status' => 'closed']);

        expect($this->callback->fresh()->contacted_at->equalTo($first->contacted_at))->toBeTrue();
    });

    it('rejects an unknown status', function () {
        $this->actingAs($this->admin)->put(route('admin.callbacks.update', $this->callback), ['status' => 'x'])->assertSessionHasErrors('status');
    });

    it('counts new requests on the dashboard', function () {
        CallbackRequest::factory()->called()->create();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertInertia(fn (Assert $page) => $page->where('stats.new_callbacks', 1));
    });
});
