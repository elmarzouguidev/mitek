@php
    $input = 'mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600';
    $faqItems = $faqCategory?->items ?? collect();
@endphp

<x-layouts.app :title="$service->meta_title ?: $service->title" :description="$service->meta_description ?: \App\Support\Seo::description($service->summary)" :schemas="[app(\App\Services\Seo\StructuredData::class)->service($service)]">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-breadcrumb :items="[__('services.index.title') => route('services.index'), $service->title => null]" />

        <div class="mt-6 grid gap-10 lg:grid-cols-[1fr_24rem]">
            <article>
                <span class="flex size-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-service-icon :name="$service->icon" class="size-7" /></span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-brand-900">{{ $service->title }}</h1>
                <p class="mt-3 text-lg text-slate-600">{{ $service->summary }}</p>
                <a href="#demande" class="mt-5 inline-flex rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 active:scale-[0.98] lg:hidden">{{ __('services.show.form_title') }}</a>

                <h2 class="mt-8 text-xl font-semibold text-brand-900">{{ __('services.show.includes') }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($service->bullets as $bullet)
                        <li class="flex items-start gap-3 text-slate-700">
                            <svg class="mt-0.5 size-5 shrink-0 text-brand-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                            {{ $bullet }}
                        </li>
                    @endforeach
                </ul>

                @if ($service->content)
                    <div class="prose-mitek mt-8 text-slate-700">{{ app(\App\Services\Content\ContentRenderer::class)->html($service->content) }}</div>
                @endif

                <x-faq-list class="mt-12" :items="$faqItems" :heading="__('services.show.faq')" :level="2" />

                @if ($others->isNotEmpty())
                    <nav class="mt-12" aria-label="{{ __('services.show.other') }}">
                        <h2 class="text-xl font-semibold text-brand-900">{{ __('services.show.other') }}</h2>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($others as $other)
                                <li><a href="{{ route('services.show', $other->slug) }}" class="inline-block rounded-full bg-slate-100 px-3 py-1.5 text-sm text-slate-700 transition hover:bg-brand-100 hover:text-brand-800">{{ $other->title }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </article>

            <aside id="demande" class="h-fit rounded-2xl bg-slate-50 p-6 lg:sticky lg:top-6" aria-labelledby="request-title">
                <h2 id="request-title" class="text-lg font-semibold text-brand-900">{{ __('services.show.form_title') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('services.show.form_intro') }}</p>

                <form method="POST" action="{{ route('services.request') }}" class="mt-5 space-y-4">
                    @csrf
                    <x-honeypot />

                    <div>
                        <label for="service" class="block text-sm font-medium text-slate-700">{{ __('services.show.service') }}</label>
                        <select id="service" name="service" class="{{ $input }}">
                            @foreach ($others->push($service)->sortBy('title') as $option)
                                <option value="{{ $option->slug }}" @selected(old('service', $service->slug) === $option->slug)>{{ $option->title }}</option>
                            @endforeach
                        </select>
                        @error('service')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-slate-700">{{ __('services.show.name') }}</label>
                        <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name', auth()->user()?->name) }}" required autocomplete="name" class="{{ $input }}">
                        @error('contact_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-slate-700">{{ __('services.show.company') }}</label>
                        <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" autocomplete="organization" class="{{ $input }}">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-700">{{ __('services.show.phone') }}</label>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" class="{{ $input }}">
                            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700">{{ __('services.show.email') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" required autocomplete="email" class="{{ $input }}">
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="city" class="block text-sm font-medium text-slate-700">{{ __('services.show.city') }}</label>
                        <input id="city" name="city" type="text" list="service-cities" value="{{ old('city') }}" required autocomplete="address-level2" class="{{ $input }}">
                        <datalist id="service-cities">@foreach ($cities as $city)<option value="{{ $city }}"></option>@endforeach</datalist>
                        @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <div>
                            <label for="intervention" class="block text-sm font-medium text-slate-700">{{ __('services.show.intervention') }}</label>
                            <select id="intervention" name="intervention" class="{{ $input }}">
                                @foreach ($interventions as $intervention)
                                    <option value="{{ $intervention->value }}" @selected(old('intervention') === $intervention->value)>{{ $intervention->label() }}</option>
                                @endforeach
                            </select>
                            @error('intervention')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="urgency" class="block text-sm font-medium text-slate-700">{{ __('services.show.urgency') }}</label>
                            <select id="urgency" name="urgency" class="{{ $input }}">
                                @foreach ($urgencies as $urgency)
                                    <option value="{{ $urgency->value }}" @selected(old('urgency', 'soon') === $urgency->value)>{{ $urgency->label() }}</option>
                                @endforeach
                            </select>
                            @error('urgency')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700">{{ __('services.show.message') }}</label>
                        <textarea id="message" name="message" rows="4" required class="{{ $input }}">{{ old('message') }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">{{ __('services.show.message_hint') }}</p>
                        @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 active:scale-[0.98]">{{ __('services.show.submit') }}</button>
                </form>
            </aside>
        </div>
    </div>
</x-layouts.app>
