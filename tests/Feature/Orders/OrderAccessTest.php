<?php

use App\Models\Core\Order\Order;
use App\Models\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->owner = User::factory()->customer()->create();
    $this->order = Order::factory()->withItem()->withHistory()->create(['user_id' => $this->owner->id, 'contact_name' => 'Owner Name']);
    $this->guestOrder = Order::factory()->withItem()->withHistory()->create(['contact_name' => 'Guest Name']);
});

it('lets a guest open their order through the signed link', function () {
    $this->get(URL::signedRoute('orders.show', $this->guestOrder))
        ->assertOk()
        ->assertSee($this->guestOrder->reference)
        ->assertSee('Guest Name');
});

it('refuses a guest without a signature', function () {
    $this->get(route('orders.show', $this->guestOrder))->assertForbidden();
});

it('refuses a tampered or foreign signature', function () {
    $signedForOther = URL::signedRoute('orders.show', $this->guestOrder);
    $forged = str_replace($this->guestOrder->uuid, $this->order->uuid, $signedForOther);

    $this->get($forged)->assertForbidden();
    $this->get($signedForOther.'x')->assertForbidden();
});

it('does not expose orders by their numeric id', function () {
    $this->get('/commande/'.$this->guestOrder->id)->assertNotFound();
});

it('lets the owner open their order without a signature', function () {
    $this->actingAs($this->owner)->get(route('orders.show', $this->order))->assertOk()->assertSee('Owner Name');
});

it('refuses another customer', function () {
    $this->actingAs(User::factory()->customer()->create())->get(route('orders.show', $this->order))->assertForbidden();
});

it('refuses a logged in customer on a guest order without a signature', function () {
    $this->actingAs($this->owner)->get(route('orders.show', $this->guestOrder))->assertForbidden();
});

it('lets an administrator open any order', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('orders.show', $this->guestOrder))->assertOk();
});

it('protects the proof upload the same way', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->image('proof.jpg');

    $this->post(route('orders.proof.store', $this->guestOrder), ['proofs' => [$file]])->assertForbidden();
    $this->actingAs(User::factory()->customer()->create())
        ->post(route('orders.proof.store', $this->order), ['proofs' => [$file]])->assertForbidden();
});

it('shows the status, the totals and the billing details on the order page', function () {
    $order = Order::factory()->withItem()->withHistory()->create([
        'company_name' => 'ACME SARL', 'ice' => '001234567000089', 'if_number' => 'IF-77', 'rc_number' => 'RC-88',
        'tracking_number' => 'TRK-123', 'tracking_carrier' => 'Amana',
    ]);

    $this->get(URL::signedRoute('orders.show', $order))
        ->assertSee('En attente de paiement')
        ->assertSee('ACME SARL')
        ->assertSee('ICE : 001234567000089')
        ->assertSee('IF : IF-77')
        ->assertSee('RC : RC-88')
        ->assertSee('TRK-123');
});
