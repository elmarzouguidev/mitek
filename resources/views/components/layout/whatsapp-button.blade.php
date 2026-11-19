@props(['site'])

@if ($url = whatsapp_url())
    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener noreferrer"
        data-whatsapp-float
        class="fixed bottom-4 left-4 z-40 flex items-center gap-3 rounded-full bg-emerald-600 p-3.5 text-white shadow-lg ring-1 ring-black/10 transition hover:bg-emerald-700 active:scale-[0.97] sm:bottom-5 sm:left-5 sm:py-3 sm:pl-3.5 sm:pr-5 motion-reduce:transition-none"
    >
        <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.4 15.1L2 22l5.05-1.6A9.9 9.9 0 1 0 12.04 2Zm0 18.1a8.2 8.2 0 0 1-4.2-1.15l-.3-.18-3 .95.97-2.9-.2-.3a8.2 8.2 0 1 1 6.73 3.58Zm4.5-6.1c-.25-.12-1.45-.72-1.68-.8-.22-.08-.39-.12-.55.12s-.63.8-.77.96-.28.18-.52.06a6.7 6.7 0 0 1-3.3-2.9c-.25-.43.25-.4.72-1.33.08-.16.04-.3-.02-.42s-.55-1.32-.75-1.8c-.2-.47-.4-.4-.55-.41h-.47c-.16 0-.42.06-.64.3s-.84.82-.84 2 .86 2.32.98 2.48c.12.16 1.7 2.6 4.1 3.65 1.5.65 2.1.7 2.86.6.46-.07 1.45-.6 1.65-1.17s.2-1.07.14-1.17-.22-.16-.47-.28Z"/></svg>
        <span class="sr-only sm:not-sr-only sm:leading-tight">
            <span class="block text-sm font-semibold">{{ __('app.whatsapp.chat') }}</span>
            @if (filled($site->whatsapp_hours))
                <span class="block text-xs text-emerald-50">{{ __('app.whatsapp.hours', ['hours' => $site->whatsapp_hours]) }}</span>
            @endif
        </span>
        <span class="sr-only">{{ __('app.whatsapp.new_tab') }}</span>
    </a>
@endif
