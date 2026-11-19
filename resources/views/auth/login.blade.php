<x-layouts.app :title="__('app.auth.login_title')">
    <x-auth-card :title="__('app.auth.login_title')">
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <x-form.input name="email" type="email" :label="__('app.auth.email')" required autofocus autocomplete="username" />
            <x-form.input name="password" type="password" :label="__('app.auth.password')" required autocomplete="current-password" />

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-slate-700">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                    {{ __('app.auth.remember') }}
                </label>
                <a href="{{ route('password.request') }}" class="text-brand-700 hover:underline">{{ __('app.auth.forgot_link') }}</a>
            </div>

            <x-form.button>{{ __('app.auth.submit_login') }}</x-form.button>
        </form>

        <x-slot:footer>
            {{ __('app.auth.no_account') }} <a href="{{ route('register') }}" class="font-medium text-brand-700 hover:underline">{{ __('app.nav.register') }}</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
