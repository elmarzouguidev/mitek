<?php

use App\Enums\UserRole;
use App\Models\User\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('shows the login and registration pages in French', function () {
    $this->get(route('login'))->assertOk()->assertSee('Se connecter');
    $this->get(route('register'))->assertOk()->assertSee('Créer mon compte');
});

it('logs a customer in and sends them to the home page', function () {
    $user = User::factory()->customer()->create();

    $this->post('/connexion', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('sends administrators to the back office after login', function () {
    $admin = User::factory()->admin()->create();

    $this->post('/connexion', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

it('rejects wrong credentials with a French message', function () {
    $user = User::factory()->create();

    $this->from(route('login'))
        ->post('/connexion', ['email' => $user->email, 'password' => 'wrong'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);

    $this->assertGuest();
});

it('throttles repeated failed logins', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post('/connexion', ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post('/connexion', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('registers a new customer with the customer role', function () {
    $this->post('/inscription', [
        'name' => 'Sara Benali',
        'email' => 'sara@example.com',
        'password' => 'Str0ng-passw0rd!',
        'password_confirmation' => 'Str0ng-passw0rd!',
    ])->assertRedirect(route('home'));

    $user = User::where('email', 'sara@example.com')->firstOrFail();

    expect($user->hasRole(UserRole::Customer->value))->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
    $this->assertAuthenticatedAs($user);
});

it('validates registration input', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post('/inscription', [
        'name' => '',
        'email' => 'taken@example.com',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['name', 'email', 'password']);
});

it('logs out', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

it('resets a forgotten password', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'N3w-passw0rd!!',
        'password_confirmation' => 'N3w-passw0rd!!',
    ])->assertRedirect(route('login'));

    expect(Hash::check('N3w-passw0rd!!', $user->fresh()->password))->toBeTrue();
});
