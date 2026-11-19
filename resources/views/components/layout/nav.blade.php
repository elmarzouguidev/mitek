@props(['menu', 'site'])

@php($whatsappUrl = $site->whatsapp ? 'https://wa.me/'.$site->whatsapp : null)

<nav class="hidden border-b border-slate-200 bg-white lg:block" aria-label="{{ __('app.nav.main') }}">
    <div class="relative mx-auto flex max-w-7xl items-center gap-1 px-4 sm:px-6 lg:px-8" x-data="megaMenu()" @keydown.escape="close(true)" @click.outside="close()" @focusout="focusOut($event)" @pointerenter="hoverIn($event)" @pointerleave="hoverOut($event)">
        @if ($menu !== [])
            <button type="button" x-ref="trigger" @click="toggle()" class="flex items-center gap-2 rounded-lg bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-800 hover:bg-brand-100" aria-controls="mega-menu" :aria-expanded="open ? 'true' : 'false'">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                {{ __('app.nav.all_products') }}
                <svg class="size-4 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.2 7.2a.75.75 0 0 1 1.06 0L10 10.94l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.26a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </button>

            <ul class="flex flex-wrap items-center">
                @foreach ($menu as $root)
                    <li><a href="{{ route('categories.show', $root['slug']) }}" class="block whitespace-nowrap px-3 py-2.5 text-sm font-medium text-slate-700 hover:text-brand-700">{{ $root['short_name'] }}</a></li>
                @endforeach
                <li><a href="{{ route('services.index') }}" class="block whitespace-nowrap px-3 py-2.5 text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('app.nav.services') }}</a></li>
            </ul>
        @else
            <a href="{{ route('services.index') }}" class="block px-3 py-2.5 text-sm font-semibold text-brand-700">{{ __('app.nav.services') }}</a>
        @endif

        <div class="ml-auto flex items-center gap-2 py-1.5">
            @if (nav_feature('pro'))
                <a href="{{ route('pro.show') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">{{ __('app.nav.pro') }}</a>
            @endif
            @if (nav_feature('quote'))
                <a href="{{ route('quotes.standalone') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 active:scale-[0.98]">{{ __('app.nav.quote') }}</a>
            @endif
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="rounded-lg p-2 text-emerald-600 hover:bg-emerald-50" aria-label="WhatsApp">
                    <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.4 15.1L2 22l5.05-1.6A9.9 9.9 0 1 0 12.04 2Zm0 18.1a8.2 8.2 0 0 1-4.2-1.15l-.3-.18-3 .95.97-2.9-.2-.3a8.2 8.2 0 1 1 6.73 3.58Zm4.5-6.1c-.25-.12-1.45-.72-1.68-.8-.22-.08-.39-.12-.55.12s-.63.8-.77.96-.28.18-.52.06a6.7 6.7 0 0 1-3.3-2.9c-.25-.43.25-.4.72-1.33.08-.16.04-.3-.02-.42s-.55-1.32-.75-1.8c-.2-.47-.4-.4-.55-.41h-.47c-.16 0-.42.06-.64.3s-.84.82-.84 2 .86 2.32.98 2.48c.12.16 1.7 2.6 4.1 3.65 1.5.65 2.1.7 2.86.6.46-.07 1.45-.6 1.65-1.17s.2-1.07.14-1.17-.22-.16-.47-.28Z"/></svg>
                </a>
            @endif
        </div>

        @if ($menu !== [])
            <div id="mega-menu" x-cloak x-show="open" class="absolute inset-x-4 top-full z-50 sm:inset-x-6 lg:inset-x-8">
                <div class="rounded-b-xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                    <div class="grid gap-x-8 gap-y-6 lg:grid-cols-5">
                        @foreach ($menu as $root)
                            <section aria-labelledby="mega-{{ $root['slug'] }}">
                                <p id="mega-{{ $root['slug'] }}" class="text-sm font-semibold text-brand-900">
                                    <a href="{{ route('categories.show', $root['slug']) }}" class="hover:text-brand-700">{{ $root['short_name'] }}</a>
                                    <span class="font-normal text-slate-400">({{ $root['count'] }})</span>
                                </p>
                                <ul class="mt-2 space-y-1.5 text-sm">
                                    @foreach ($root['children'] as $child)
                                        <li><a href="{{ route('categories.show', $child['slug']) }}" class="flex justify-between gap-2 text-slate-600 hover:text-brand-700"><span>{{ $child['name'] }}</span><span class="text-slate-400">{{ $child['count'] }}</span></a></li>
                                    @endforeach
                                </ul>
                                @foreach ($root['featured'] as $featured)
                                    <a href="{{ $featured['url'] }}" class="mt-4 flex items-center gap-3 rounded-lg bg-slate-50 p-2.5 ring-1 ring-slate-100 transition hover:bg-brand-50">
                                        @if ($featured['image'])
                                            <img src="{{ $featured['image'] }}" alt="" width="48" height="48" class="size-12 shrink-0 rounded object-contain" loading="lazy">
                                        @endif
                                        <span class="min-w-0 text-xs">
                                            <span class="block text-[0.65rem] font-semibold uppercase tracking-wide text-brand-600">{{ __('app.nav.featured') }}</span>
                                            <span class="block truncate font-medium text-slate-900">{{ $featured['name'] }}</span>
                                            <span class="block font-semibold text-brand-800">{{ $featured['price'] }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</nav>
