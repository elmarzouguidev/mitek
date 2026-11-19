<?php

use App\Models\Core\Product\Product;
use App\Models\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
    $this->product = Product::factory()->create(['name' => 'Gallery Product']);
});

function addImages(Product $product, int $count): void
{
    foreach (range(1, $count) as $index) {
        $product->addMedia(UploadedFile::fake()->image("img-{$index}.jpg", 800, 800))->toMediaCollection(Product::GALLERY);
    }
}

it('uploads several images to the gallery', function () {
    $this->actingAs($this->admin)->post(route('admin.products.images.store', $this->product), [
        'images' => [UploadedFile::fake()->image('a.jpg', 900, 900), UploadedFile::fake()->image('b.png', 900, 900)],
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    expect($this->product->getMedia(Product::GALLERY))->toHaveCount(2);
});

it('rejects files that are not images', function () {
    $this->actingAs($this->admin)->post(route('admin.products.images.store', $this->product), [
        'images' => [UploadedFile::fake()->create('malware.php', 10, 'application/x-php')],
    ])->assertSessionHasErrors('images.0');

    $this->actingAs($this->admin)->post(route('admin.products.images.store', $this->product), [
        'images' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
    ])->assertSessionHasErrors('images.0');

    expect($this->product->getMedia(Product::GALLERY))->toHaveCount(0);
});

it('rejects oversized images', function () {
    $this->actingAs($this->admin)->post(route('admin.products.images.store', $this->product), [
        'images' => [UploadedFile::fake()->image('huge.jpg')->size(6000)],
    ])->assertSessionHasErrors('images.0');
});

it('limits a product to ten images', function () {
    addImages($this->product, 9);

    $this->actingAs($this->admin)->post(route('admin.products.images.store', $this->product), [
        'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
    ])->assertSessionHasErrors('images');

    expect($this->product->getMedia(Product::GALLERY))->toHaveCount(9);
});

it('reorders the gallery so the first image is the main one', function () {
    addImages($this->product, 3);
    [$first, $second, $third] = $this->product->getMedia(Product::GALLERY)->pluck('id')->all();

    $this->actingAs($this->admin)->put(route('admin.products.images.reorder', $this->product), ['ids' => [$third, $first, $second]])
        ->assertSessionHasNoErrors();

    expect($this->product->fresh()->getMedia(Product::GALLERY)->pluck('id')->all())->toBe([$third, $first, $second]);
});

it('refuses to reorder with ids that are not part of the gallery', function () {
    addImages($this->product, 2);
    $other = Product::factory()->create();
    addImages($other, 1);

    $this->actingAs($this->admin)->put(route('admin.products.images.reorder', $this->product), [
        'ids' => [$this->product->getMedia(Product::GALLERY)->first()->id, $other->getFirstMedia(Product::GALLERY)->id],
    ])->assertSessionHasErrors('ids.1');
});

it('deletes an image together with its files', function () {
    addImages($this->product, 2);
    $media = $this->product->getFirstMedia(Product::GALLERY);
    $path = $media->getPathRelativeToRoot();

    $this->actingAs($this->admin)->delete(route('admin.products.images.destroy', [$this->product, $media->id]))
        ->assertSessionHas('success');

    expect($this->product->fresh()->getMedia(Product::GALLERY))->toHaveCount(1);
    Storage::disk('public')->assertMissing($path);
});

it('cannot delete the image of another product through this product', function () {
    $other = Product::factory()->create();
    addImages($other, 1);
    $foreign = $other->getFirstMedia(Product::GALLERY);

    $this->actingAs($this->admin)->delete(route('admin.products.images.destroy', [$this->product, $foreign->id]))
        ->assertNotFound();

    expect($other->fresh()->getMedia(Product::GALLERY))->toHaveCount(1);
});

it('keeps image management away from customers and guests', function () {
    $url = route('admin.products.images.store', $this->product);

    $this->post($url)->assertRedirect(route('login'));
    $this->actingAs(User::factory()->customer()->create())->post($url)->assertForbidden();
});

it('shows the main image of each product in the admin list', function () {
    addImages($this->product, 1);

    $this->actingAs($this->admin)->get(route('admin.products.index'))
        ->assertInertia(fn ($page) => $page->where('products.data.0.thumb', fn ($url) => str_ends_with($url, '.webp')));
});
