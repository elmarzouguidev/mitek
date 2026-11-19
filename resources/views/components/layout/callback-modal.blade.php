@unless (request()->routeIs('callback.*'))
    <div
        x-data="callbackModal({ errorMessage: @js(__('callback.error')), throttledMessage: @js(__('callback.throttled')) })"
        @callback:open.window="show($event.target)"
        @keydown.escape.window="open && hide()"
    >
        <div x-cloak x-show="open" x-transition.opacity.duration.200ms class="fixed inset-0 z-50 bg-slate-900/50" @click="hide()" aria-hidden="true"></div>

        <div
            x-cloak
            x-show="open"
            x-ref="panel"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-labelledby="callback-modal-title"
            class="fixed inset-0 z-50 flex items-end justify-center overscroll-contain sm:items-center sm:p-4"
            @click.self="hide()"
        >
            <div class="max-h-full w-full overflow-y-auto overscroll-contain rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-md sm:rounded-2xl sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <p id="callback-modal-title" class="text-xl font-semibold text-brand-900">{{ __('callback.title') }}</p>
                    <button type="button" x-ref="close" @click="hide()" class="-mr-2 -mt-2 rounded-lg p-2 text-slate-600 hover:bg-slate-100" aria-label="{{ __('callback.close') }}">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>

                <div x-show="!sent">
                    <p class="mb-4 mt-1 text-sm text-slate-600">{{ __('callback.intro') }}</p>
                    <x-callback-form :modal="true" />
                </div>

                <div x-show="sent" role="status" class="py-4">
                    <p class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200" x-text="message"></p>
                    <button type="button" @click="hide()" class="mt-4 rounded-lg px-5 py-2.5 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-50">{{ __('callback.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endunless
