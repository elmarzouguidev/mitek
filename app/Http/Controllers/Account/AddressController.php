<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Models\Core\Customer\Address;
use App\Support\MoroccanCities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses', [
            'addresses' => $request->user()->addresses()->get(),
            'cities' => MoroccanCities::all(),
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            $isDefault = $request->boolean('is_default') || ! $user->addresses()->exists();

            if ($isDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create([...$request->safe()->except('is_default'), 'is_default' => $isDefault]);
        });

        return redirect()->route('account.addresses')->with('success', __('account.addresses.saved'));
    }

    public function edit(Address $address): View
    {
        Gate::authorize('update', $address);

        return view('account.address-edit', ['address' => $address, 'cities' => MoroccanCities::all()]);
    }

    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        Gate::authorize('update', $address);

        DB::transaction(function () use ($request, $address): void {
            $isDefault = $request->boolean('is_default') || $address->is_default;

            if ($isDefault) {
                $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update([...$request->safe()->except('is_default'), 'is_default' => $isDefault]);
        });

        return redirect()->route('account.addresses')->with('success', __('account.addresses.saved'));
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        Gate::authorize('delete', $address);

        DB::transaction(function () use ($request, $address): void {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $request->user()->addresses()->first()?->update(['is_default' => true]);
            }
        });

        return redirect()->route('account.addresses')->with('success', __('account.addresses.deleted'));
    }
}
