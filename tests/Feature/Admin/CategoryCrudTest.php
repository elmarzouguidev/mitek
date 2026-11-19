<?php

use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
});

it('keeps category management away from guests and customers', function () {
    $category = Category::factory()->create();
    $customer = User::factory()->customer()->create();

    foreach ([['get', route('admin.categories.index')], ['post', route('admin.categories.store')], ['put', route('admin.categories.update', $category)], ['delete', route('admin.categories.destroy', $category)]] as [$method, $url]) {
        $this->$method($url)->assertRedirect(route('login'));
        $this->actingAs($customer)->$method($url)->assertForbidden();
        auth()->logout();
    }
});

it('lists categories as a tree, children right after their parent', function () {
    $second = Category::factory()->create(['name' => 'Second family', 'sort_order' => 2]);
    $first = Category::factory()->create(['name' => 'First family', 'sort_order' => 1]);
    Category::factory()->childOf($first)->create(['name' => 'Child of first']);

    $response = $this->actingAs($this->admin)->get(route('admin.categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Categories/Index')
            ->where('categories.1.is_child', true));

    expect(collect($response->viewData('page')['props']['categories'])->pluck('name')->all())
        ->toBe(['First family', 'Child of first', 'Second family']);
});

it('creates a root category and a subcategory with SEO fields', function () {
    $this->actingAs($this->admin)->post(route('admin.categories.store'), [
        'name' => 'Mobilier',
        'description' => 'Chaises et bureaux.',
        'sort_order' => 4,
        'is_active' => true,
        'meta_title' => 'Mobilier de bureau',
        'meta_description' => 'Tout le mobilier.',
    ])->assertRedirect(route('admin.categories.index'));

    $root = Category::where('name', 'Mobilier')->firstOrFail();

    $this->actingAs($this->admin)->post(route('admin.categories.store'), [
        'name' => 'Chaises', 'parent_id' => $root->id, 'sort_order' => 1, 'is_active' => true,
    ])->assertSessionHasNoErrors();

    $child = Category::where('name', 'Chaises')->firstOrFail();

    expect($root->slug)->toBe('mobilier')
        ->and($root->meta_title)->toBe('Mobilier de bureau')
        ->and($child->parent_id)->toBe($root->id);
});

it('allows only two levels of categories', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();

    $this->actingAs($this->admin)->post(route('admin.categories.store'), [
        'name' => 'Too deep', 'parent_id' => $child->id, 'sort_order' => 0, 'is_active' => true,
    ])->assertSessionHasErrors('parent_id');

    expect(Category::where('name', 'Too deep')->exists())->toBeFalse();
});

it('forbids a category from being its own parent or becoming a child while it has children', function () {
    $root = Category::factory()->create();
    Category::factory()->childOf($root)->create();
    $other = Category::factory()->create();

    $payload = fn (array $overrides) => array_merge(['name' => $root->name, 'sort_order' => 0, 'is_active' => true], $overrides);

    $this->actingAs($this->admin)->put(route('admin.categories.update', $root), $payload(['parent_id' => $root->id]))
        ->assertSessionHasErrors('parent_id');

    $this->actingAs($this->admin)->put(route('admin.categories.update', $root), $payload(['parent_id' => $other->id]))
        ->assertSessionHasErrors('parent_id');
});

it('updates a category and can deactivate it', function () {
    $category = Category::factory()->create(['name' => 'Old']);

    $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
        'name' => 'Renamed', 'sort_order' => 3, 'is_active' => false,
    ])->assertRedirect(route('admin.categories.index'));

    $category->refresh();

    expect($category->name)->toBe('Renamed')
        ->and($category->is_active)->toBeFalse()
        ->and($category->slug)->toBe('old');
});

it('uploads, replaces and removes the category image', function () {
    $category = Category::factory()->create();
    $update = fn (array $extra) => $this->actingAs($this->admin)->put(route('admin.categories.update', $category), array_merge([
        'name' => $category->name, 'sort_order' => 0, 'is_active' => true,
    ], $extra));

    $update(['image' => UploadedFile::fake()->image('one.jpg', 800, 600)])->assertSessionHasNoErrors();
    expect($category->fresh()->getMedia('image'))->toHaveCount(1);

    $update(['image' => UploadedFile::fake()->image('two.jpg', 800, 600)]);
    expect($category->fresh()->getMedia('image'))->toHaveCount(1)
        ->and($category->fresh()->getFirstMedia('image')->file_name)->toBe('two.jpg');

    $update(['remove_image' => true]);
    expect($category->fresh()->getMedia('image'))->toHaveCount(0);
});

it('rejects non image uploads for the category image', function () {
    $this->actingAs($this->admin)->post(route('admin.categories.store'), [
        'name' => 'Bad', 'sort_order' => 0, 'is_active' => true,
        'image' => UploadedFile::fake()->create('script.php', 5, 'application/x-php'),
    ])->assertSessionHasErrors('image');
});

it('deletes an empty category', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($category);
});

it('refuses to delete a category that has products or subcategories', function () {
    $withProducts = Category::factory()->create();
    Product::factory()->for($withProducts)->create();
    $withChildren = Category::factory()->create();
    Category::factory()->childOf($withChildren)->create();

    foreach ([$withProducts, $withChildren] as $category) {
        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))->assertSessionHas('error');
        $this->assertModelExists($category);
    }
});

it('exposes the root categories as possible parents in the form', function () {
    $root = Category::factory()->create();
    Category::factory()->childOf($root)->create();

    $this->actingAs($this->admin)->get(route('admin.categories.edit', $root))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Categories/Form')
            ->where('category.id', $root->id)
            ->where('parents', []));
});
