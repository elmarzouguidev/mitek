<?php

namespace App\Http\Controllers\Storefront;

use App\Events\ContactMessageReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactMessageForm;
use App\Models\Core\Content\ContactMessage;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(GeneralSettings $settings): View
    {
        return view('contact.show', ['settings' => $settings]);
    }

    public function store(ContactMessageForm $request): RedirectResponse
    {
        $message = ContactMessage::create($request->validated());

        ContactMessageReceived::dispatch($message);

        return redirect()->route('contact')->with('success', __('contact.sent'));
    }
}
