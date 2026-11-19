@php
    $input = 'mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600';
@endphp

<x-layouts.app :title="__('contact.title')" :description="__('contact.description', ['site' => $settings->site_name])">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-breadcrumb :items="[__('contact.title') => null]" />
        <h1 class="mt-5 text-3xl font-bold tracking-tight text-brand-900">{{ __('contact.title') }}</h1>
        <p class="mt-2 max-w-2xl text-slate-600">{{ __('contact.intro') }}</p>

        <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_22rem]">
            <section aria-labelledby="form-title">
                <h2 id="form-title" class="text-xl font-semibold text-brand-900">{{ __('contact.form.title') }}</h2>
                <form method="POST" action="{{ route('contact.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <x-honeypot />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700">{{ __('contact.form.name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', auth()->user()?->name) }}" required autocomplete="name" class="{{ $input }}">
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700">{{ __('contact.form.email') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" required autocomplete="email" class="{{ $input }}">
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-700">{{ __('contact.form.phone') }}</label>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" class="{{ $input }}">
                            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="subject" class="block text-sm font-medium text-slate-700">{{ __('contact.form.subject') }}</label>
                            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required class="{{ $input }}">
                            @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700">{{ __('contact.form.message') }}</label>
                        <textarea id="message" name="message" rows="6" required class="{{ $input }}">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-brand-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 active:scale-[0.98]">{{ __('contact.form.submit') }}</button>
                </form>
            </section>

            <aside class="h-fit rounded-2xl bg-slate-50 p-6" aria-labelledby="info-title">
                <h2 id="info-title" class="text-lg font-semibold text-brand-900">{{ __('contact.info.title') }}</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    @if ($settings->phone)
                        <div><dt class="font-medium text-slate-500">{{ __('contact.info.phone') }}</dt><dd><a href="tel:{{ preg_replace('/\s+/', '', $settings->phone) }}" class="font-medium text-brand-700 hover:underline">{{ $settings->phone }}</a></dd></div>
                    @endif
                    @if ($settings->whatsapp)
                        <div><dt class="font-medium text-slate-500">{{ __('contact.info.whatsapp') }}</dt><dd><a href="https://wa.me/{{ $settings->whatsapp }}" target="_blank" rel="noopener" class="font-medium text-brand-700 hover:underline">{{ __('contact.info.whatsapp_action') }}</a></dd></div>
                    @endif
                    <div><dt class="font-medium text-slate-500">{{ __('contact.info.email') }}</dt><dd><a href="mailto:{{ $settings->email }}" class="font-medium text-brand-700 hover:underline">{{ $settings->email }}</a></dd></div>
                    <div><dt class="font-medium text-slate-500">{{ __('contact.info.address') }}</dt><dd class="text-slate-800">{{ $settings->address }}</dd></div>
                </dl>
            </aside>
        </div>

        @if ($settings->maps_embed_url)
            <section class="mt-12" aria-labelledby="map-title">
                <h2 id="map-title" class="text-xl font-semibold text-brand-900">{{ __('contact.info.map') }}</h2>
                <iframe src="{{ $settings->maps_embed_url }}" title="{{ __('contact.info.map') }}" class="mt-4 h-80 w-full rounded-2xl border-0 ring-1 ring-slate-200" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </section>
        @endif
    </div>
</x-layouts.app>
