<?php

use App\Enums\Intervention;
use App\Enums\ServiceRequestStatus;
use App\Enums\Urgency;
use App\Models\Core\Service\Service;
use App\Models\Core\Service\ServiceRequest;
use App\Models\User\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function serviceFormPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Cybersécurité & protection',
        'icon' => 'shield',
        'summary' => 'Antivirus, audit et sauvegardes.',
        'bullets' => ['Antivirus professionnels', 'Audit de sécurité'],
        'content' => "## Détails\n\nTexte **détaillé**.",
        'meta_title' => null,
        'meta_description' => null,
        'sort_order' => 3,
        'is_active' => true,
    ], $overrides);
}

function makeServiceRequest(array $attributes = []): ServiceRequest
{
    return ServiceRequest::create(array_merge([
        'reference' => 'SR-'.strtoupper(fake()->unique()->bothify('??????')),
        'service_title' => 'Maintenance',
        'status' => ServiceRequestStatus::New,
        'contact_name' => 'Karim Alaoui',
        'company_name' => 'ACME SARL',
        'phone' => '0661234567',
        'email' => 'karim@acme.ma',
        'city' => 'Casablanca',
        'intervention' => Intervention::OnSite,
        'urgency' => Urgency::Soon,
        'message' => 'Trois postes ne démarrent plus.',
    ], $attributes));
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('keeps every service screen away from guests and customers', function () {
    $service = Service::factory()->create();
    $request = makeServiceRequest();
    $customer = User::factory()->customer()->create();

    $routes = [
        ['get', route('admin.services.index')], ['get', route('admin.services.create')], ['post', route('admin.services.store')],
        ['get', route('admin.services.edit', $service)], ['put', route('admin.services.update', $service)], ['delete', route('admin.services.destroy', $service)],
        ['get', route('admin.service-requests.index')], ['get', route('admin.service-requests.show', $request)], ['put', route('admin.service-requests.update', $request)],
    ];

    foreach ($routes as [$method, $url]) {
        $this->$method($url)->assertRedirect(route('login'));
        $this->actingAs($customer)->$method($url)->assertForbidden();
        auth()->logout();
    }
});

describe('services', function () {
    it('creates a service with a generated slug, bullets and SEO fields', function () {
        $this->actingAs($this->admin)->post(route('admin.services.store'), serviceFormPayload(['meta_title' => 'SEO title']))
            ->assertRedirect(route('admin.services.index'))->assertSessionHas('success');

        $service = Service::firstOrFail();
        expect($service->slug)->toBe('cybersecurite-protection')
            ->and($service->bullets)->toBe(['Antivirus professionnels', 'Audit de sécurité'])
            ->and($service->icon)->toBe('shield')
            ->and($service->meta_title)->toBe('SEO title');
    });

    it('validates the service', function (array $overrides, string $field) {
        $this->actingAs($this->admin)->post(route('admin.services.store'), serviceFormPayload($overrides))->assertSessionHasErrors($field);

        expect(Service::count())->toBe(0);
    })->with([
        'title' => [['title' => ''], 'title'],
        'unknown icon' => [['icon' => 'rocket'], 'icon'],
        'summary' => [['summary' => ''], 'summary'],
        'no bullet' => [['bullets' => []], 'bullets'],
        'empty bullet' => [['bullets' => ['']], 'bullets.0'],
        'meta description' => [['meta_description' => str_repeat('a', 321)], 'meta_description'],
    ]);

    it('updates and deactivates a service, which then disappears from the site', function () {
        $service = Service::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.services.update', $service), serviceFormPayload(['title' => 'Renamed', 'is_active' => false]))->assertSessionHas('success');

        expect($service->fresh()->title)->toBe('Renamed')->and($service->fresh()->is_active)->toBeFalse();
        $this->get(route('services.show', $service->slug))->assertNotFound();
    });

    it('lists services with their request counts', function () {
        $service = Service::factory()->create(['title' => 'Maintenance']);
        makeServiceRequest(['service_id' => $service->id]);
        makeServiceRequest(['service_id' => $service->id]);

        $this->actingAs($this->admin)->get(route('admin.services.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Services/Index')->where('services.0.title', 'Maintenance')->where('services.0.requests_count', 2));
    });

    it('exposes a service to the edit form', function () {
        $service = Service::factory()->create(['bullets' => ['A', 'B']]);

        $this->actingAs($this->admin)->get(route('admin.services.edit', $service))
            ->assertInertia(fn (Assert $page) => $page->component('Services/Form')->where('service.bullets', ['A', 'B'])->has('icons', count(Service::ICONS)));
    });

    it('deletes a service but keeps its requests with the remembered title', function () {
        $service = Service::factory()->create(['title' => 'Maintenance']);
        $request = makeServiceRequest(['service_id' => $service->id, 'service_title' => 'Maintenance']);

        $this->actingAs($this->admin)->delete(route('admin.services.destroy', $service))->assertRedirect(route('admin.services.index'));

        $this->assertModelMissing($service);
        expect($request->fresh()->service_id)->toBeNull()->and($request->fresh()->service_title)->toBe('Maintenance');
    });

    it('renders the detailed content as safe Markdown', function () {
        $service = Service::factory()->create(['content' => "## Détails\n\nTexte <script>alert(1)</script> **gras** [lien](javascript:alert(1))"]);

        $this->get(route('services.show', $service->slug))
            ->assertSee('<h2>Détails</h2>', false)
            ->assertSee('<strong>gras</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('javascript:alert', false);
    });
});

describe('service requests', function () {
    it('lists, searches and filters the requests', function () {
        makeServiceRequest(['reference' => 'SR-AAA111', 'contact_name' => 'Karim Alaoui', 'service_title' => 'Maintenance']);
        makeServiceRequest(['reference' => 'SR-BBB222', 'contact_name' => 'Sara Benali', 'company_name' => null, 'email' => 'sara@example.com', 'service_title' => 'Cloud', 'status' => ServiceRequestStatus::Done]);

        $refs = fn (array $query) => collect($this->actingAs($this->admin)->get(route('admin.service-requests.index', $query))->viewData('page')['props']['requests']['data'])->pluck('reference')->all();

        expect($refs([]))->toHaveCount(2)
            ->and($refs(['q' => 'karim']))->toBe(['SR-AAA111'])
            ->and($refs(['q' => 'cloud']))->toBe(['SR-BBB222'])
            ->and($refs(['status' => 'done']))->toBe(['SR-BBB222'])
            ->and($refs(['status' => 'new']))->toBe(['SR-AAA111']);
    });

    it('rejects unknown filters', function () {
        $this->actingAs($this->admin)->get(route('admin.service-requests.index', ['status' => 'bogus']))->assertSessionHasErrors('status');
    });

    it('shows the request in full', function () {
        $request = makeServiceRequest();

        $this->actingAs($this->admin)->get(route('admin.service-requests.show', $request))
            ->assertInertia(fn (Assert $page) => $page
                ->component('ServiceRequests/Show')
                ->where('serviceRequest.reference', $request->reference)
                ->where('serviceRequest.contact.email', 'karim@acme.ma')
                ->where('serviceRequest.intervention', 'Sur site')
                ->where('serviceRequest.urgency', 'Rapide')
                ->where('serviceRequest.message', 'Trois postes ne démarrent plus.')
                ->has('statuses', 5));
    });

    it('moves a request through its statuses and keeps internal notes', function (string $status) {
        $request = makeServiceRequest();

        $this->actingAs($this->admin)->put(route('admin.service-requests.update', $request), ['status' => $status, 'internal_notes' => 'Rappelé à 10h, RDV jeudi.'])
            ->assertSessionHas('success');

        expect($request->fresh()->status->value)->toBe($status)->and($request->fresh()->internal_notes)->toBe('Rappelé à 10h, RDV jeudi.');
    })->with(['contacted', 'quote_sent', 'done', 'closed']);

    it('validates the update', function () {
        $request = makeServiceRequest();

        $this->actingAs($this->admin)->put(route('admin.service-requests.update', $request), ['status' => 'bogus'])->assertSessionHasErrors('status');
        $this->actingAs($this->admin)->put(route('admin.service-requests.update', $request), ['status' => 'done', 'internal_notes' => str_repeat('a', 5001)])->assertSessionHasErrors('internal_notes');
    });

    it('never shows the internal notes to the public', function () {
        $service = Service::factory()->create();
        makeServiceRequest(['service_id' => $service->id, 'internal_notes' => 'SECRET-NOTE']);

        $this->get(route('services.show', $service->slug))->assertDontSee('SECRET-NOTE');
    });

    it('counts the new requests on the dashboard', function () {
        makeServiceRequest();
        makeServiceRequest();
        makeServiceRequest(['status' => ServiceRequestStatus::Done]);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertInertia(fn (Assert $page) => $page->where('stats.new_service_requests', 2));
    });
});
