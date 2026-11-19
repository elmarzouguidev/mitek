<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageRequest;
use App\Models\Core\Product\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImageController extends Controller
{
    public function store(ProductImageRequest $request, Product $product): RedirectResponse
    {
        foreach ($request->file('images') as $image) {
            $product->addMedia($image)->toMediaCollection(Product::GALLERY);
        }

        return back()->with('success', __('admin.products.images_added'));
    }

    /**
     * The first image of the list becomes the main image.
     */
    public function reorder(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $ids = $product->getMedia(Product::GALLERY)->pluck('id');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'size:'.$ids->count()],
            'ids.*' => ['integer', 'in:'.$ids->implode(',')],
        ]);

        Media::setNewOrder($validated['ids']);

        return back();
    }

    public function destroy(Product $product, int $media): RedirectResponse
    {
        Gate::authorize('update', $product);

        $product->getMedia(Product::GALLERY)->firstWhere('id', $media)?->delete() ?? abort(404);

        return back()->with('success', __('admin.products.image_deleted'));
    }
}
