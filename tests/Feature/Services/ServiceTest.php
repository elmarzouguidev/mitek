<?php

use App\Enums\ServiceRequestStatus;
use App\Mail\AdminNotificationMail;
use App\Mail\ServiceRequestReceivedMail;
use App\Models\Core\Content\FaqCategory;
use App\Models\Core\Content\FaqItem;
use App\Models\Core\Service\Service;
use App\Models\Core\Service\ServiceRequest;
use Illuminate\Support\Facades\Mail;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function servicePayload(Service $service, array $overrides = []): array
{
    return array_merge([
        'service' => $service->slug,
        'contact_name' => 'Karim Alaoui',
        'company_name' => 'ACME SARL',
        'phone' => '0661234567',
        'email' => 'karim@acme.ma',
        'city' => 'Casablanca',
        'intervention' => 'on_site',
        'urgency' => 'soon',
        'message' => 'Trois postes ne démarrent plus depuis lundi.',
    ], $overrides);
}

beforeEach(function () {
    $this->service = Service::factory()->create(['title' => 'Maintenance informatique', 'summary' => 'Réparation et maintenance.', 'bullets' => ['Diagnostic', 'Support à distance']]);
});

describe('pages', function () {
    it('lists the active services in order', function () {
        Service::factory()->create(['title' => 'Zeta service', 'sort_order' => 2]);
        Service::factory()->create(['title' => 'Alpha service', 'sort_order' => 1]);
        Service::factory()->inactive()->create(['title' => 'Hidden service']);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSeeInOrder(['Alpha service', 'Zeta service'])
            ->assertDontSee('Hidden service')
            ->assertSee(route('services.show', $this->service->slug), false);
    });

    it('shows a service with its bullet points, request form and links to the other services', function () {
        $other = Service::factory()->create(['title' => 'Autre service']);

        $this->get(route('services.show', $this->service->slug))
            ->assertOk()
            ->assertSee('Maintenance informatique')
            ->assertSee('Diagnostic')
            ->assertSee('Support à distance')
            ->assertSee('name="contact_name"', false)
            ->assertSee('name="intervention"', false)
            ->assertSee('name="urgency"', false)
            ->assertSee('Autre service')
            ->assertSee(route('services.show', $other->slug), false);
    });

    it('preselects the current service in the form', function () {
        Service::factory()->create(['title' => 'Autre service']);

        $this->get(route('services.show', $this->service->slug))
            ->assertSee('value="'.$this->service->slug.'" selected', false);
    });

    it('returns 404 for an inactive or unknown service', function () {
        $inactive = Service::factory()->inactive()->create();

        $this->get(route('services.show', $inactive->slug))->assertNotFound();
        $this->get(route('services.show', 'does-not-exist'))->assertNotFound();
    });

    it('shows the services FAQ on the service page', function () {
        $category = FaqCategory::factory()->create(['slug' => 'services', 'name' => 'Services informatiques']);
        FaqItem::factory()->for($category, 'category')->create(['question' => 'Intervenez-vous le week-end ?', 'answer' => 'Sur rendez-vous.']);
        FaqItem::factory()->for($category, 'category')->inactive()->create(['question' => 'Question masquée ?']);

        $this->get(route('services.show', $this->service->slug))
            ->assertSee('Intervenez-vous le week-end ?')
            ->assertDontSee('Question masquée')
            ->assertSee('FAQPage', false);
    });

    it('uses the SEO fields with fallbacks from the summary', function () {
        $custom = Service::factory()->create(['title' => 'Custom', 'meta_title' => 'Custom SEO title', 'meta_description' => 'Custom SEO description.']);

        $this->get(route('services.show', $custom->slug))
            ->assertSee('<title>Custom SEO title · Mitek.ma</title>', false)
            ->assertSee('<meta name="description" content="Custom SEO description.">', false);

        $this->get(route('services.show', $this->service->slug))
            ->assertSee('<title>Maintenance informatique · Mitek.ma</title>', false)
            ->assertSee('<meta name="description" content="Réparation et maintenance.">', false);
    });
});

describe('request form', function () {
    beforeEach(fn () => Mail::fake());

    it('stores the request, remembers the service title and confirms it to the customer and the team', function () {
        $this->post(route('services.request'), servicePayload($this->service))
            ->assertRedirect(route('services.show', $this->service->slug))
            ->assertSessionHas('success');

        $request = ServiceRequest::firstOrFail();
        expect($request->reference)->toStartWith('SR-')
            ->and($request->status)->toBe(ServiceRequestStatus::New)
            ->and($request->service_id)->toBe($this->service->id)
            ->and($request->service_title)->toBe('Maintenance informatique')
            ->and($request->contact_name)->toBe('Karim Alaoui')
            ->and($request->company_name)->toBe('ACME SARL')
            ->and($request->intervention->value)->toBe('on_site')
            ->and($request->urgency->value)->toBe('soon');

        Mail::assertSent(ServiceRequestReceivedMail::class, function (ServiceRequestReceivedMail $mail) {
            $mail->assertSeeInHtml('Maintenance informatique');
            $mail->assertSeeInHtml('Sur site');

            return $mail->hasTo('karim@acme.ma');
        });
        Mail::assertSent(AdminNotificationMail::class, function (AdminNotificationMail $mail) use ($request) {
            $mail->assertHasSubject('[Mitek.ma] Nouvelle demande de service '.$request->reference);
            $mail->assertSeeInHtml('ACME SARL');
            $mail->assertSeeInHtml(route('admin.service-requests.show', $request), false);

            return $mail->hasTo('contact@mitek.ma');
        });
    });

    it('lets the visitor pick another service than the page they are on', function () {
        $other = Service::factory()->create(['title' => 'Cybersécurité']);

        $this->post(route('services.request'), servicePayload($this->service, ['service' => $other->slug]))
            ->assertRedirect(route('services.show', $other->slug));

        expect(ServiceRequest::firstOrFail()->service_title)->toBe('Cybersécurité');
    });

    it('validates the form', function (array $overrides, string $field) {
        $this->post(route('services.request'), servicePayload($this->service, $overrides))->assertSessionHasErrors($field);

        expect(ServiceRequest::count())->toBe(0);
        Mail::assertNothingSent();
    })->with([
        'name' => [['contact_name' => ''], 'contact_name'],
        'phone' => [['phone' => '12'], 'phone'],
        'email' => [['email' => 'nope'], 'email'],
        'city' => [['city' => ''], 'city'],
        'intervention' => [['intervention' => 'teleport'], 'intervention'],
        'urgency' => [['urgency' => 'whenever'], 'urgency'],
        'message too short' => [['message' => 'Aide'], 'message'],
        'message too long' => [['message' => str_repeat('a', 3001)], 'message'],
        'unknown service' => [['service' => 'does-not-exist'], 'service'],
    ]);

    it('refuses an inactive service', function () {
        $inactive = Service::factory()->inactive()->create();

        $this->post(route('services.request'), servicePayload($this->service, ['service' => $inactive->slug]))->assertSessionHasErrors('service');
    });

    it('does not require a company', function () {
        $this->post(route('services.request'), servicePayload($this->service, ['company_name' => '']))->assertSessionHasNoErrors();

        expect(ServiceRequest::firstOrFail()->company_name)->toBeNull();
    });

    it('is protected by the honeypot', function () {
        config(['honeypot.enabled' => true]);

        $this->post(route('services.request'), servicePayload($this->service));

        expect(ServiceRequest::count())->toBe(0);
    });

    it('is rate limited', function () {
        foreach (range(1, 10) as $attempt) {
            $this->post(route('services.request'), servicePayload($this->service, ['contact_name' => '']));
        }

        $this->post(route('services.request'), servicePayload($this->service))->assertStatus(429);
        expect(ServiceRequest::count())->toBe(0);
    });

    it('escapes what the visitor typed when the form is shown again', function () {
        $this->from(route('services.show', $this->service->slug))
            ->post(route('services.request'), servicePayload($this->service, ['contact_name' => '"><script>alert(1)</script>', 'email' => 'bad']));

        $this->get(route('services.show', $this->service->slug))->assertDontSee('<script>alert(1)</script>', false);
    });
});
