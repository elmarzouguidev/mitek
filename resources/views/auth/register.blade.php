<x-layouts.app :title="__('app.auth.register_title')">
    <x-auth-card :title="__('app.auth.register_title')">
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <x-form.input name="name" :label="__('app.auth.name')" required autofocus autocomplete="name" />
            <x-form.input name="email" type="email" :label="__('app.auth.email')" required autocomplete="username" />
            <x-form.input name="password" type="password" :label="__('app.auth.password')" required autocomplete="new-password" />
            <x-form.input name="password_confirmation" type="password" :label="__('app.auth.password_confirmation')" required autocomplete="new-password" />

            <x-form.button>{{ __('app.auth.submit_register') }}</x-form.button>
        </form>

        <x-slot:footer>
            {{ __('app.auth.has_account') }} <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">{{ __('app.nav.login') }}</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
