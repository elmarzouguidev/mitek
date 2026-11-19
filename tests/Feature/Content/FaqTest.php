<?php

use App\Models\Core\Content\FaqCategory;
use App\Models\Core\Content\FaqItem;
use App\Models\User\User;
use App\Support\Money;
use Inertia\Testing\AssertableInertia as Assert;

describe('storefront', function () {
    it('lists the active questions by category, in order', function () {
        $second = FaqCategory::factory()->create(['name' => 'Seconde catégorie', 'slug' => 'seconde', 'sort_order' => 2]);
        $first = FaqCategory::factory()->create(['name' => 'Première catégorie', 'slug' => 'premiere', 'sort_order' => 1]);
        FaqItem::factory()->for($second, 'category')->create(['question' => 'Question B ?', 'answer' => 'Réponse B']);
        FaqItem::factory()->for($first, 'category')->create(['question' => 'Question Z ?', 'sort_order' => 2]);
        FaqItem::factory()->for($first, 'category')->create(['question' => 'Question A ?', 'sort_order' => 1]);
        FaqItem::factory()->for($first, 'category')->inactive()->create(['question' => 'Question masquée ?']);

        $response = $this->get(route('faq'))->assertOk()
            ->assertSeeInOrder(['Première catégorie', 'Question A ?', 'Question Z ?', 'Seconde catégorie', 'Question B ?'])
            ->assertDontSee('Question masquée');

        assertHeadingOutline($response->getContent());
    });

    it('hides a category without active questions', function () {
        $empty = FaqCategory::factory()->create(['name' => 'Catégorie vide']);
        FaqItem::factory()->for($empty, 'category')->inactive()->create();

        $this->get(route('faq'))->assertDontSee('Catégorie vide');
    });

    it('fills the business values of the answers from the settings and renders Markdown safely', function () {
        $category = FaqCategory::factory()->create();
        FaqItem::factory()->for($category, 'category')->create(['question' => 'Paiement à la livraison ?', 'answer' => "Oui, jusqu'à **{{cod_limit}}** TTC.\n\n<script>alert(1)</script>"]);

        $this->get(route('faq'))
            ->assertSee('<strong>'.Money::fromCents(200000)->format().'</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    });

    it('describes all the questions in one FAQPage', function () {
        $category = FaqCategory::factory()->create();
        FaqItem::factory()->for($category, 'category')->create(['question' => 'Q1 ?', 'answer' => 'A1']);
        FaqItem::factory()->for($category, 'category')->create(['question' => 'Q2 ?', 'answer' => 'A2 **gras**']);
        FaqItem::factory()->for($category, 'category')->inactive()->create(['question' => 'Hidden ?']);

        $pages = jsonLdOfType($this->get(route('faq'))->getContent(), 'FAQPage');

        expect($pages)->toHaveCount(1)
            ->and($pages[0]['mainEntity'])->toHaveCount(2)
            ->and($pages[0]['mainEntity'][1]['name'])->toBe('Q2 ?')
            ->and($pages[0]['mainEntity'][1]['acceptedAnswer']['text'])->toBe('A2 gras');
    });
});

describe('admin', function () {
    beforeEach(fn () => $this->admin = User::factory()->admin()->create());

    it('keeps the FAQ screens away from guests and customers', function () {
        $item = FaqItem::factory()->create();
        $customer = User::factory()->customer()->create();

        $routes = [
            ['get', route('admin.faq.index')], ['get', route('admin.faq.categories.create')], ['post', route('admin.faq.categories.store')],
            ['put', route('admin.faq.categories.update', $item->category)], ['delete', route('admin.faq.categories.destroy', $item->category)],
            ['get', route('admin.faq.items.create')], ['post', route('admin.faq.items.store')], ['put', route('admin.faq.items.update', $item)], ['delete', route('admin.faq.items.destroy', $item)],
        ];

        foreach ($routes as [$method, $url]) {
            $this->$method($url)->assertRedirect(route('login'));
            $this->actingAs($customer)->$method($url)->assertForbidden();
            auth()->logout();
        }
    });

    it('lists categories with their questions', function () {
        $item = FaqItem::factory()->create(['question' => 'Une question ?']);

        $this->actingAs($this->admin)->get(route('admin.faq.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Faq/Index')->where('categories.0.name', $item->category->name)->where('categories.0.items.0.question', 'Une question ?'));
    });

    it('creates, updates and deletes a category', function () {
        $this->actingAs($this->admin)->post(route('admin.faq.categories.store'), ['name' => 'Retours', 'slug' => 'retours', 'sort_order' => 5])->assertRedirect(route('admin.faq.index'));
        $category = FaqCategory::where('slug', 'retours')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.faq.categories.update', $category), ['name' => 'Retours et SAV', 'slug' => 'retours', 'sort_order' => 6])->assertSessionHasNoErrors();
        expect($category->fresh()->name)->toBe('Retours et SAV');

        $this->actingAs($this->admin)->delete(route('admin.faq.categories.destroy', $category))->assertRedirect(route('admin.faq.index'));
        $this->assertModelMissing($category);
    });

    it('validates the category, with a unique lowercase slug', function () {
        FaqCategory::factory()->create(['slug' => 'taken']);

        $post = fn (array $data) => $this->actingAs($this->admin)->post(route('admin.faq.categories.store'), array_merge(['name' => 'N', 'slug' => 'ok-slug', 'sort_order' => 0], $data));

        $post(['slug' => 'taken'])->assertSessionHasErrors('slug');
        $post(['slug' => 'Bad Slug'])->assertSessionHasErrors('slug');
        $post(['name' => ''])->assertSessionHasErrors('name');
    });

    it('keeps its own slug valid when editing', function () {
        $category = FaqCategory::factory()->create(['slug' => 'mine']);

        $this->actingAs($this->admin)->put(route('admin.faq.categories.update', $category), ['name' => 'Renamed', 'slug' => 'mine', 'sort_order' => 0])->assertSessionHasNoErrors();
    });

    it('creates, updates and deletes a question', function () {
        $category = FaqCategory::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.faq.items.store'), ['faq_category_id' => $category->id, 'question' => 'Comment ça marche ?', 'answer' => 'Très bien.', 'sort_order' => 1, 'is_active' => true])
            ->assertRedirect(route('admin.faq.index'));
        $item = FaqItem::firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.faq.items.update', $item), ['faq_category_id' => $category->id, 'question' => 'Comment ça marche ?', 'answer' => 'Mieux.', 'sort_order' => 1, 'is_active' => false])->assertSessionHasNoErrors();
        expect($item->fresh()->answer)->toBe('Mieux.')->and($item->fresh()->is_active)->toBeFalse();

        $this->get(route('faq'))->assertDontSee('Comment ça marche ?');

        $this->actingAs($this->admin)->delete(route('admin.faq.items.destroy', $item));
        $this->assertModelMissing($item);
    });

    it('validates the question', function (array $overrides, string $field) {
        $category = FaqCategory::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.faq.items.store'), array_merge(['faq_category_id' => $category->id, 'question' => 'Q ?', 'answer' => 'A', 'sort_order' => 0], $overrides))->assertSessionHasErrors($field);
    })->with([
        'question' => [['question' => ''], 'question'],
        'answer' => [['answer' => ''], 'answer'],
        'unknown category' => [['faq_category_id' => 9999], 'faq_category_id'],
    ]);

    it('deletes the questions with their category', function () {
        $item = FaqItem::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.faq.categories.destroy', $item->category));

        $this->assertModelMissing($item);
    });
});
