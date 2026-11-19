<?php

use App\Models\Core\Order\Order;
use App\Models\User\User;
use App\Services\Core\Customer\LinkGuestOrders;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function verificationUrl(User $user): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]);
}

describe('linking rules', function () {
    it('links earlier guest orders of the same email once the email is verified', function () {
        $guest = Order::factory()->create(['contact_email' => 'sara@example.com']);
        $user = User::factory()->unverified()->customer()->create(['email' => 'sara@example.com']);

        $this->actingAs($user)->get(verificationUrl($user))->assertRedirect(route('account.index'));

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
            ->and($guest->fresh()->user_id)->toBe($user->id);
    });

    it('does not link anything while the email is unverified', function () {
        $guest = Order::factory()->create(['contact_email' => 'victim@example.com']);
        $attacker = User::factory()->unverified()->customer()->create(['email' => 'victim@example.com']);

        expect(app(LinkGuestOrders::class)->handle($attacker))->toBe(0)
            ->and($guest->fresh()->user_id)->toBeNull();

        event(new Registered($attacker));

        expect($guest->fresh()->user_id)->toBeNull();
    });

    it('does not expose the orders to an unverified account', function () {
        Order::factory()->withItem()->create(['contact_email' => 'victim@example.com', 'reference' => 'MO-VICTIM']);
        $attacker = User::factory()->unverified()->customer()->create(['email' => 'victim@example.com']);

        $this->actingAs($attacker)->get(route('account.orders'))->assertDontSee('MO-VICTIM');
    });

    it('matches the email regardless of case', function () {
        $guest = Order::factory()->create(['contact_email' => 'Sara.Benali@Example.com']);
        $user = User::factory()->customer()->create(['email' => 'sara.benali@example.com']);

        expect(app(LinkGuestOrders::class)->handle($user))->toBe(1)
            ->and($guest->fresh()->user_id)->toBe($user->id);
    });

    it('never touches orders of other emails or orders that already belong to someone', function () {
        $other = Order::factory()->create(['contact_email' => 'other@example.com']);
        $owner = User::factory()->customer()->create();
        $owned = Order::factory()->create(['contact_email' => 'sara@example.com', 'user_id' => $owner->id]);
        $user = User::factory()->customer()->create(['email' => 'sara@example.com']);

        app(LinkGuestOrders::class)->handle($user);

        expect($other->fresh()->user_id)->toBeNull()
            ->and($owned->fresh()->user_id)->toBe($owner->id);
    });

    it('links on account creation when the email is already verified', function () {
        $guest = Order::factory()->create(['contact_email' => 'sara@example.com']);
        $user = User::factory()->customer()->create(['email' => 'sara@example.com']);

        event(new Registered($user));

        expect($guest->fresh()->user_id)->toBe($user->id);
    });

    it('shows the linked orders in the account', function () {
        Order::factory()->withItem()->create(['contact_email' => 'sara@example.com', 'reference' => 'MO-GUEST-1']);
        $user = User::factory()->unverified()->customer()->create(['email' => 'sara@example.com']);

        $this->actingAs($user)->get(verificationUrl($user));

        $this->actingAs($user->fresh())->get(route('account.orders'))->assertSee('MO-GUEST-1');
    });
});

describe('verification flow', function () {
    it('sends the verification email when registering and asks to confirm', function () {
        Notification::fake();

        $this->post('/inscription', ['name' => 'Sara', 'email' => 'sara@example.com', 'password' => 'Str0ng-passw0rd!', 'password_confirmation' => 'Str0ng-passw0rd!'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        Notification::assertSentTo(User::where('email', 'sara@example.com')->firstOrFail(), VerifyEmail::class);
    });

    it('does not link orders at registration, only after the click', function () {
        Notification::fake();
        $guest = Order::factory()->create(['contact_email' => 'sara@example.com']);

        $this->post('/inscription', ['name' => 'Sara', 'email' => 'sara@example.com', 'password' => 'Str0ng-passw0rd!', 'password_confirmation' => 'Str0ng-passw0rd!']);

        expect($guest->fresh()->user_id)->toBeNull();
    });

    it('rejects a tampered or unsigned verification link', function () {
        $user = User::factory()->unverified()->customer()->create();

        $this->actingAs($user)->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();
        $this->actingAs($user)->get(verificationUrl($user).'x')->assertForbidden();
        $this->actingAs(User::factory()->unverified()->customer()->create())->get(verificationUrl($user))->assertForbidden();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('shows a banner with a resend action to unverified accounts only', function () {
        $unverified = User::factory()->unverified()->customer()->create();

        $this->actingAs($unverified)->get(route('account.index'))->assertSee('Votre adresse email n\'est pas confirmée.')->assertSee(route('verification.send'), false);
        $this->actingAs(User::factory()->customer()->create())->get(route('account.index'))->assertDontSee('n\'est pas confirmée');
    });

    it('resends the verification email, throttled', function () {
        Notification::fake();
        $user = User::factory()->unverified()->customer()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status');

        Notification::assertSentTo($user, VerifyEmail::class);
    });

    it('does not resend to a verified account and sends it to the account page', function () {
        Notification::fake();
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect(route('account.index'));
        $this->actingAs($user)->get(route('verification.notice'))->assertRedirect(route('account.index'));

        Notification::assertNothingSent();
    });

    it('shows the notice page to an unverified account', function () {
        $user = User::factory()->unverified()->customer()->create(['email' => 'sara@example.com']);

        $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertSee('sara@example.com')->assertSee('Renvoyer l\'email');
    });

    it('requires a login', function () {
        $this->get(route('verification.notice'))->assertRedirect(route('login'));
        $this->post(route('verification.send'))->assertRedirect(route('login'));
    });

    it('writes the verification email in French', function () {
        $user = User::factory()->unverified()->create();

        $mail = (new VerifyEmail)->toMail($user);

        expect($mail->subject)->toBe('Confirmez votre adresse email')
            ->and(implode(' ', $mail->introLines))->toContain('Cliquez sur le bouton');
    });
});
