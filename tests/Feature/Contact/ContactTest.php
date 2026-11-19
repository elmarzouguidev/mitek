<?php

use App\Mail\AdminNotificationMail;
use App\Models\Core\Content\ContactMessage;
use App\Models\User\User;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contactPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sara Benali',
        'email' => 'sara@example.com',
        'phone' => '0612345678',
        'subject' => 'Question sur un toner',
        'message' => 'Avez-vous le toner HP 312A en stock pour une livraison à Rabat ?',
    ], $overrides);
}

describe('page', function () {
    it('shows the form and the contact details from the settings', function () {
        $settings = app(GeneralSettings::class);
        $settings->phone = '0522 11 22 33';
        $settings->whatsapp = '212600000000';
        $settings->save();

        $response = $this->get(route('contact'))->assertOk()
            ->assertSee('name="subject"', false)
            ->assertSee('contact@mitek.ma')
            ->assertSee('Casablanca, Maroc')
            ->assertSee('0522 11 22 33')
            ->assertSee('tel:0522112233', false)
            ->assertSee('https://wa.me/212600000000', false);

        assertHeadingOutline($response->getContent());
    });

    it('hides the phone and WhatsApp while they are empty', function () {
        $this->get(route('contact'))->assertDontSee('tel:', false)->assertDontSee('wa.me', false)->assertDontSee('Écrire sur WhatsApp');
    });

    it('hides the map while the embed URL is empty and shows it once set', function () {
        $this->get(route('contact'))->assertDontSee('<iframe', false)->assertDontSee('Plan d\'accès');

        $settings = app(GeneralSettings::class);
        $settings->maps_embed_url = 'https://www.google.com/maps/embed?pb=!1m18';
        $settings->save();

        $this->get(route('contact'))
            ->assertSee('<iframe src="https://www.google.com/maps/embed?pb=!1m18"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('Plan d\'accès');
    });

    it('pre-fills the form for a logged in customer', function () {
        $user = User::factory()->customer()->create(['name' => 'Sara Benali', 'email' => 'sara@example.com']);

        $this->actingAs($user)->get(route('contact'))->assertSee('value="Sara Benali"', false)->assertSee('value="sara@example.com"', false);
    });
});

describe('form', function () {
    beforeEach(fn () => Mail::fake());

    it('stores the message and notifies the team', function () {
        $this->post(route('contact.store'), contactPayload())->assertRedirect(route('contact'))->assertSessionHas('success');

        $message = ContactMessage::firstOrFail();
        expect($message->name)->toBe('Sara Benali')->and($message->subject)->toBe('Question sur un toner')->and($message->read_at)->toBeNull();

        Mail::assertSent(AdminNotificationMail::class, function (AdminNotificationMail $mail) use ($message) {
            $mail->assertHasSubject('[Mitek.ma] Nouveau message : Question sur un toner');
            $mail->assertSeeInHtml('sara@example.com');
            $mail->assertSeeInHtml('toner HP 312A');
            $mail->assertSeeInHtml(route('admin.messages.show', $message), false);

            return $mail->hasTo('contact@mitek.ma');
        });
    });

    it('sends the notification to the configured address', function () {
        $settings = app(GeneralSettings::class);
        $settings->admin_notification_email = 'ops@mitek.ma';
        $settings->save();

        $this->post(route('contact.store'), contactPayload());

        Mail::assertSent(AdminNotificationMail::class, fn (AdminNotificationMail $mail) => $mail->hasTo('ops@mitek.ma') && ! $mail->hasTo('contact@mitek.ma'));
    });

    it('does not require a phone number', function () {
        $this->post(route('contact.store'), contactPayload(['phone' => '']))->assertSessionHasNoErrors();

        expect(ContactMessage::firstOrFail()->phone)->toBeNull();
    });

    it('validates the form', function (array $overrides, string $field) {
        $this->post(route('contact.store'), contactPayload($overrides))->assertSessionHasErrors($field);

        expect(ContactMessage::count())->toBe(0);
        Mail::assertNothingSent();
    })->with([
        'name' => [['name' => ''], 'name'],
        'email' => [['email' => 'nope'], 'email'],
        'phone' => [['phone' => '12'], 'phone'],
        'subject' => [['subject' => ''], 'subject'],
        'message too short' => [['message' => 'Hi'], 'message'],
        'message too long' => [['message' => str_repeat('a', 3001)], 'message'],
    ]);

    it('is protected by the honeypot', function () {
        config(['honeypot.enabled' => true]);

        $this->post(route('contact.store'), contactPayload());

        expect(ContactMessage::count())->toBe(0);
        Mail::assertNothingSent();
    });

    it('is rate limited', function () {
        foreach (range(1, 10) as $attempt) {
            $this->post(route('contact.store'), contactPayload(['name' => '']));
        }

        $this->post(route('contact.store'), contactPayload())->assertStatus(429);
        expect(ContactMessage::count())->toBe(0);
    });
});

describe('admin', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->message = ContactMessage::create(contactPayload());
    });

    it('keeps the screens away from guests and customers', function () {
        $customer = User::factory()->customer()->create();

        foreach ([['get', route('admin.messages.index')], ['get', route('admin.messages.show', $this->message)], ['put', route('admin.messages.unread', $this->message)], ['delete', route('admin.messages.destroy', $this->message)]] as [$method, $url]) {
            $this->$method($url)->assertRedirect(route('login'));
            $this->actingAs($customer)->$method($url)->assertForbidden();
            auth()->logout();
        }

        expect($this->message->fresh()->read_at)->toBeNull();
        $this->assertModelExists($this->message);
    });

    it('lists, searches and filters messages by read state', function () {
        ContactMessage::create(contactPayload(['name' => 'Karim', 'subject' => 'Autre sujet', 'email' => 'karim@acme.ma']))->update(['read_at' => now()]);

        $subjects = fn (array $query) => collect($this->actingAs($this->admin)->get(route('admin.messages.index', $query))->viewData('page')['props']['messages']['data'])->pluck('subject')->all();

        expect($subjects([]))->toHaveCount(2)
            ->and($subjects(['status' => 'unread']))->toBe(['Question sur un toner'])
            ->and($subjects(['status' => 'read']))->toBe(['Autre sujet'])
            ->and($subjects(['q' => 'karim']))->toBe(['Autre sujet'])
            ->and($subjects(['q' => 'toner']))->toBe(['Question sur un toner']);

        $this->actingAs($this->admin)->get(route('admin.messages.index'))->assertInertia(fn (Assert $page) => $page->component('Messages/Index')->where('messages.meta.total', 2));
    });

    it('marks a message as read when it is opened', function () {
        $this->actingAs($this->admin)->get(route('admin.messages.show', $this->message))
            ->assertInertia(fn (Assert $page) => $page->component('Messages/Show')->where('message.subject', 'Question sur un toner')->where('message.body', $this->message->message));

        expect($this->message->fresh()->read_at)->not->toBeNull();
    });

    it('keeps the original read date when opened again', function () {
        $this->actingAs($this->admin)->get(route('admin.messages.show', $this->message));
        $firstRead = $this->message->fresh()->read_at;

        $this->travel(2)->hours();
        $this->actingAs($this->admin)->get(route('admin.messages.show', $this->message));

        expect($this->message->fresh()->read_at->equalTo($firstRead))->toBeTrue();
    });

    it('marks a message as unread again and deletes it', function () {
        $this->message->update(['read_at' => now()]);

        $this->actingAs($this->admin)->put(route('admin.messages.unread', $this->message))->assertSessionHas('success');
        expect($this->message->fresh()->read_at)->toBeNull();

        $this->actingAs($this->admin)->delete(route('admin.messages.destroy', $this->message))->assertRedirect(route('admin.messages.index'));
        $this->assertModelMissing($this->message);
    });

    it('counts the unread messages on the dashboard', function () {
        ContactMessage::create(contactPayload());
        ContactMessage::create(contactPayload())->update(['read_at' => now()]);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertInertia(fn (Assert $page) => $page->where('stats.unread_messages', 2));
    });

    it('shows messages as plain text, never as HTML', function () {
        $message = ContactMessage::create(contactPayload(['message' => '<script>alert(1)</script> bonjour à tous']));

        $this->actingAs($this->admin)->get(route('admin.messages.show', $message))
            ->assertInertia(fn (Assert $page) => $page->where('message.body', '<script>alert(1)</script> bonjour à tous'));
    });
});
