<?php

use App\Models\User\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('forbids customers from the back office', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('renders the dashboard for administrators through Inertia', function () {
    User::factory()->customer()->count(2)->create();

    $this->actingAs(User::factory()->admin()->create(['name' => 'Admin Mitek']))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.customers', 2)
            ->where('auth.user.name', 'Admin Mitek')
            ->where('app.name', 'Mitek.ma'));
});

it('does not serve the admin shell to search engines', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertSee('noindex', false);
});
