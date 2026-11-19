<x-layouts.app robots="noindex,nofollow" :title="__('app.auth.reset_title')">
    <x-auth-card :title="__('app.auth.reset_title')">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">{{ __('app.auth.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', $request->query('email')) }}" required class="mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <x-form.input name="password" type="password" :label="__('app.auth.password')" required autocomplete="new-password" />
            <x-form.input name="password_confirmation" type="password" :label="__('app.auth.password_confirmation')" required autocomplete="new-password" />
            <x-form.button>{{ __('app.auth.submit_reset') }}</x-form.button>
        </form>
    </x-auth-card>
</x-layouts.app>
