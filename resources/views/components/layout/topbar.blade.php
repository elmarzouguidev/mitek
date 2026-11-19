@props(['site'])

@php($whatsappUrl = $site->whatsapp ? 'https://wa.me/'.$site->whatsapp : null)

<div class="bg-brand-950 text-xs text-brand-100" x-data="copyText(@js($site->email))">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="font-medium hover:text-white">WhatsApp</a>
            @endif
            <span class="hidden items-center gap-1 sm:flex">
                <a href="mailto:{{ $site->email }}" class="hover:text-white">{{ $site->email }}</a>
                <button type="button" @click="copy()" class="rounded p-1 text-brand-200 hover:bg-brand-900 hover:text-white" aria-label="{{ __('app.topbar.copy_email') }}">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="7" y="7" width="9" height="9" rx="1.5"/><path d="M4 13V5a1.5 1.5 0 0 1 1.5-1.5H13" stroke-linecap="round"/></svg>
                </button>
                <span class="sr-only" role="status" aria-live="polite" x-text="copied ? @js(__('app.topbar.copied')) : ''"></span>
                <span x-cloak x-show="copied" class="text-brand-200" aria-hidden="true">{{ __('app.topbar.copied') }}</span>
            </span>
        </div>
        <nav class="flex items-center gap-4" aria-label="{{ __('app.topbar.label') }}">
            <a href="{{ route('contact') }}" class="hover:text-white">{{ __('app.topbar.contact') }}</a>
            @if (nav_feature('callback'))
                <a href="{{ route('callback.create') }}" class="hover:text-white">{{ __('app.topbar.callback') }}</a>
            @endif
        </nav>
    </div>
</div>
