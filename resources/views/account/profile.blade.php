@php($input = 'mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600')

<x-account-layout :title="__('account.profile.title')" active="profile">
    <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section>
            <h2 class="text-xl font-semibold text-brand-900">{{ __('account.profile.identity') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">{{ __('account.profile.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required class="{{ $input }}">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">{{ __('account.profile.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required class="{{ $input }}">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700">{{ __('account.profile.phone') }}</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $profile?->phone) }}" class="{{ $input }}">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-brand-900">{{ __('account.profile.company') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('account.profile.company_hint') }}</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="company_name" class="block text-sm font-medium text-slate-700">{{ __('account.profile.company_name') }}</label>
                    <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $profile?->company_name) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="ice" class="block text-sm font-medium text-slate-700">{{ __('account.profile.ice') }}</label>
                    <input id="ice" name="ice" type="text" inputmode="numeric" maxlength="15" value="{{ old('ice', $profile?->ice) }}" class="{{ $input }}">
                    @error('ice')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="if_number" class="block text-sm font-medium text-slate-700">{{ __('account.profile.if') }}</label>
                    <input id="if_number" name="if_number" type="text" value="{{ old('if_number', $profile?->if_number) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="rc_number" class="block text-sm font-medium text-slate-700">{{ __('account.profile.rc') }}</label>
                    <input id="rc_number" name="rc_number" type="text" value="{{ old('rc_number', $profile?->rc_number) }}" class="{{ $input }}">
                </div>
            </div>
        </section>

        <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ __('account.profile.save') }}</button>
    </form>

    <form method="POST" action="{{ route('account.password.update') }}" class="mt-12 space-y-4 border-t border-slate-200 pt-8">
        @csrf
        @method('PUT')
        <h2 class="text-xl font-semibold text-brand-900">{{ __('account.profile.password_title') }}</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700">{{ __('account.profile.current_password') }}</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="{{ $input }}">
                @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">{{ __('account.profile.new_password') }}</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required class="{{ $input }}">
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">{{ __('account.profile.confirm_password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="{{ $input }}">
            </div>
        </div>
        <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ __('account.profile.save') }}</button>
    </form>
</x-account-layout>
