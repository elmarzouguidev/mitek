<form
    method="GET"
    action="{{ route('search') }}"
    role="search"
    class="relative"
    x-data="searchBox('{{ route('search.suggestions') }}', { search: @js(route('search')), all: @js(__('catalog.search.all_results')), none: @js(__('catalog.search.no_suggestions')), count: @js(__('catalog.search.results_count')) })"
    @click.outside="close()"
    @keydown.escape="onEscape()"
>
    <label for="site-search" class="sr-only">{{ __('catalog.search.label') }}</label>
    <div class="flex gap-2">
        <input
            id="site-search"
            type="search"
            name="q"
            value="{{ request()->routeIs('search') ? request('q') : '' }}"
            placeholder="{{ __('catalog.search.placeholder') }}"
            autocomplete="off"
            maxlength="100"
            role="combobox"
            aria-autocomplete="list"
            aria-haspopup="listbox"
            aria-controls="site-search-listbox"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-activedescendant="active >= 0 ? 'sb-opt-' + active : ''"
            x-model="query"
            @input="onInput()"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter="choose($event)"
            @keydown.tab="close()"
            class="block w-full rounded-lg border-0 bg-white px-4 py-2 text-sm shadow-xs ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-600"
        >
        <button type="submit" class="shrink-0 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 active:scale-[0.98]">{{ __('catalog.search.button') }}</button>
    </div>

    <p class="sr-only" role="status" aria-live="polite" x-text="status"></p>

    <div x-cloak x-show="open" class="absolute inset-x-0 top-full z-50 mt-1 max-h-[70vh] overflow-y-auto overscroll-contain rounded-lg bg-white shadow-lg ring-1 ring-slate-200">
        <ul id="site-search-listbox" role="listbox" aria-label="{{ __('catalog.search.suggestions') }}" class="py-1">
            <template x-for="group in groups" :key="group.key">
                <li role="presentation">
                    <p class="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="group.label"></p>
                    <ul role="presentation">
                        <template x-for="item in group.items" :key="item.index">
                            <li role="option" :id="'sb-opt-' + item.index" :aria-selected="active === item.index ? 'true' : 'false'" @mouseenter="active = item.index">
                                <a :href="item.url" tabindex="-1" class="flex items-center gap-3 px-3 py-2 text-sm" :class="active === item.index ? 'bg-brand-50' : ''">
                                    <img x-show="item.image" :src="item.image" alt="" class="size-10 shrink-0 rounded object-contain" loading="lazy">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-medium text-slate-900" x-text="item.label"></span>
                                        <span x-show="item.sub" class="block truncate text-xs text-slate-500" x-text="item.sub"></span>
                                    </span>
                                    <span x-show="item.price" class="shrink-0 font-semibold text-brand-800" x-text="item.price"></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </li>
            </template>
            <li x-show="!groups.length" class="px-3 py-3 text-sm text-slate-500" x-text="labels.none"></li>
            <li role="option" :id="'sb-opt-' + lastIndex" :aria-selected="active === lastIndex ? 'true' : 'false'" class="border-t border-slate-100" @mouseenter="active = lastIndex">
                <a :href="allUrl" tabindex="-1" class="block px-3 py-2.5 text-sm font-medium text-brand-700" :class="active === lastIndex ? 'bg-brand-50' : ''" x-text="allLabel"></a>
            </li>
        </ul>
    </div>
</form>
