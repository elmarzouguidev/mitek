<x-layouts.app :title="__('callback.title')" :description="__('callback.description')" robots="noindex,follow">
    <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
        <x-breadcrumb :items="[__('callback.title') => null]" />
        <h1 class="mt-5 text-3xl font-bold tracking-tight text-brand-900">{{ __('callback.title') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('callback.intro') }}</p>

        <div class="mt-8">
            <x-callback-form />
        </div>
    </div>
</x-layouts.app>
