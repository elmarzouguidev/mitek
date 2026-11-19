<form
    method="GET"
    action="{{ route('search') }}"
    role="search"
    class="relative"
    x-data="searchBox('{{ route('search.suggestions') }}')"
    @click.outside="close()"
    @keydown.escape="close()"
>
    <label for="site-search" class="sr-only">{{ __('catalog.search.title') }}</label>
    <div class="flex gap-2">
        <input
            id="site-search"
            type="search"
            name="q"
            value="{{ request()->routeIs('search') ? request('q') : '' }}"
            placeholder="{{ __('catalog.search.placeholder') }}"
            autocomplete="off"
            maxlength="100"
            x-model="query"
            @input="onInput()"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter="choose($event)"
            class="block w-full rounded-lg border-0 bg-white px-4 py-2 text-sm shadow-xs ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600"
        >
        <button type="submit" class="shrink-0 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">{{ __('catalog.search.button') }}</button>
    </div>

    <ul x-cloak x-show="open" class="absolute inset-x-0 top-full z-40 mt-1 overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-slate-200" aria-label="{{ __('catalog.search.suggestions') }}">
        <template x-for="(result, index) in results" :key="result.url">
            <li>
                <a :href="result.url" class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-brand-50" :class="{ 'bg-brand-50': index === highlighted }">
                    <img x-show="result.image" :src="result.image" alt="" class="size-10 shrink-0 rounded object-contain" loading="lazy">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium text-slate-900" x-text="result.name"></span>
                        <span class="block truncate text-xs text-slate-500" x-text="[result.brand, result.reference].filter(Boolean).join(' · ')"></span>
                    </span>
                    <span class="shrink-0 font-semibold text-brand-800" x-text="result.price"></span>
                </a>
            </li>
        </template>
        <li class="border-t border-slate-100"><button type="submit" class="block w-full px-3 py-2 text-left text-sm font-medium text-brand-700 hover:bg-brand-50">{{ __('catalog.search.see_all') }}</button></li>
    </ul>
</form>
