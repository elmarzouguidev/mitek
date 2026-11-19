<x-layouts.app :title="__('app.auth.forgot_title')">
    <x-auth-card :title="__('app.auth.forgot_title')">
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <x-form.input name="email" type="email" :label="__('app.auth.email')" required autofocus />
            <x-form.button>{{ __('app.auth.submit_forgot') }}</x-form.button>
        </form>
    </x-auth-card>
</x-layouts.app>
