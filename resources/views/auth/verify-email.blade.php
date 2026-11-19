<x-layouts.app robots="noindex,nofollow" :title="__('account.verification.title')">
    <x-auth-card :title="__('account.verification.title')">
        <p class="text-sm text-slate-600">{{ __('account.verification.intro', ['email' => auth()->user()->email]) }}</p>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-5">
            @csrf
            <x-form.button>{{ __('account.verification.resend') }}</x-form.button>
        </form>
    </x-auth-card>
</x-layouts.app>
