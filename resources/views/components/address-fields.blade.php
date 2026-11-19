@props(['address' => null, 'cities' => []])

@php($input = 'mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600')

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="label" class="block text-sm font-medium text-slate-700">{{ __('account.addresses.label') }}</label>
        <input id="label" name="label" type="text" value="{{ old('label', $address?->label) }}" required class="{{ $input }}">
        @error('label')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="contact_name" class="block text-sm font-medium text-slate-700">{{ __('account.addresses.contact_name') }}</label>
        <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name', $address?->contact_name ?? auth()->user()->name) }}" required class="{{ $input }}">
        @error('contact_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="phone" class="block text-sm font-medium text-slate-700">{{ __('account.addresses.phone') }}</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone', $address?->phone) }}" required class="{{ $input }}">
        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="city" class="block text-sm font-medium text-slate-700">{{ __('account.addresses.city') }}</label>
        <input id="city" name="city" type="text" list="account-cities" value="{{ old('city', $address?->city) }}" required class="{{ $input }}">
        <datalist id="account-cities">@foreach ($cities as $city)<option value="{{ $city }}"></option>@endforeach</datalist>
        @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="address" class="block text-sm font-medium text-slate-700">{{ __('account.addresses.address') }}</label>
        <textarea id="address" name="address" rows="2" required class="{{ $input }}">{{ old('address', $address?->address) }}</textarea>
        @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="postal_code" class="block text-sm font-medium text-slate-700">{{ __('account.addresses.postal_code') }}</label>
        <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $address?->postal_code) }}" class="{{ $input }}">
    </div>
    <label class="flex items-end gap-2 pb-2 text-sm text-slate-700">
        <input type="hidden" name="is_default" value="0">
        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address?->is_default)) class="rounded text-brand-700 focus:ring-brand-600">
        {{ __('account.addresses.make_default') }}
    </label>
</div>
