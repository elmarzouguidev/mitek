<?php

namespace App\Http\Controllers\Storefront;

use App\Events\CallbackRequested;
use App\Http\Controllers\Controller;
use App\Http\Requests\CallbackRequestForm;
use App\Models\Core\Content\CallbackRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CallbackController extends Controller
{
    /**
     * The full-page form: the no-JavaScript version of the "Être rappelé" modal.
     */
    public function create(): View
    {
        return view('callback.create');
    }

    public function store(CallbackRequestForm $request): RedirectResponse|JsonResponse
    {
        $callback = CallbackRequest::create($request->validated());

        CallbackRequested::dispatch($callback);

        if ($request->expectsJson()) {
            return response()->json(['message' => __('callback.sent')], 201);
        }

        return redirect()->route('callback.create')->with('success', __('callback.sent'));
    }
}
