@props(['announcement', 'hash'])

@if ($announcement->isVisible())
    <div data-announcement class="bg-brand-700 text-sm text-white" @if ($announcement->dismissible) x-data="announcement('{{ $hash }}')" @endif>
        <div class="mx-auto flex max-w-7xl items-center justify-center gap-3 px-4 py-2 text-center sm:px-6 lg:px-8">
            <p>
                {{ $announcement->text }}
                @if ($announcement->link_url && $announcement->link_label)
                    <a href="{{ $announcement->link_url }}" class="ml-1 font-semibold underline underline-offset-2 hover:no-underline" @if (str_starts_with($announcement->link_url, 'https://')) rel="noopener" @endif>{{ $announcement->link_label }}</a>
                @endif
            </p>
            @if ($announcement->dismissible)
                <button type="button" @click="dismiss()" class="shrink-0 rounded p-1 hover:bg-brand-800" aria-label="{{ __('app.announcement.close') }}">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                </button>
            @endif
        </div>
    </div>
@endif
