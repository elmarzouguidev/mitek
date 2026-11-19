@props(['modal' => false])

@php
    $input = 'mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600';
    $prefix = $modal ? 'cb-m-' : 'cb-';
    $fields = ['name', 'phone', 'subject', 'time_slot', 'note'];
@endphp

<form
    method="POST"
    action="{{ route('callback.store') }}"
    class="space-y-4"
    @if ($modal) @submit.prevent="submit($event.target)" novalidate @endif
>
    @csrf
    <x-honeypot />

    <div>
        <label for="{{ $prefix }}name" class="block text-sm font-medium text-slate-700">{{ __('callback.form.name') }}</label>
        <input id="{{ $prefix }}name" name="name" type="text" value="{{ old('name', auth()->user()?->name) }}" required autocomplete="name" maxlength="150" class="{{ $input }}" @if ($modal) :aria-invalid="errors.name ? 'true' : 'false'" aria-describedby="{{ $prefix }}name-error" @endif>
        @if ($modal)
            <p id="{{ $prefix }}name-error" x-show="errors.name" x-text="errors.name?.[0]" class="mt-1 text-xs text-red-600"></p>
        @else
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @endif
    </div>

    <div>
        <label for="{{ $prefix }}phone" class="block text-sm font-medium text-slate-700">{{ __('callback.form.phone') }}</label>
        <input id="{{ $prefix }}phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel" maxlength="30" class="{{ $input }}" @if ($modal) :aria-invalid="errors.phone ? 'true' : 'false'" aria-describedby="{{ $prefix }}phone-hint {{ $prefix }}phone-error" @endif>
        <p id="{{ $prefix }}phone-hint" class="mt-1 text-xs text-slate-500">{{ __('callback.form.phone_hint') }}</p>
        @if ($modal)
            <p id="{{ $prefix }}phone-error" x-show="errors.phone" x-text="errors.phone?.[0]" class="mt-1 text-xs text-red-600"></p>
        @else
            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $prefix }}subject" class="block text-sm font-medium text-slate-700">{{ __('callback.form.subject') }}</label>
            <select id="{{ $prefix }}subject" name="subject" required class="{{ $input }}">
                @foreach (\App\Enums\CallbackSubject::cases() as $subject)
                    <option value="{{ $subject->value }}" @selected(old('subject', 'other') === $subject->value)>{{ $subject->label() }}</option>
                @endforeach
            </select>
            @if ($modal)
                <p x-show="errors.subject" x-text="errors.subject?.[0]" class="mt-1 text-xs text-red-600"></p>
            @else
                @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @endif
        </div>
        <div>
            <label for="{{ $prefix }}time_slot" class="block text-sm font-medium text-slate-700">{{ __('callback.form.slot') }}</label>
            <select id="{{ $prefix }}time_slot" name="time_slot" required class="{{ $input }}">
                @foreach (\App\Enums\CallbackSlot::cases() as $slot)
                    <option value="{{ $slot->value }}" @selected(old('time_slot', 'anytime') === $slot->value)>{{ $slot->label() }}</option>
                @endforeach
            </select>
            @if ($modal)
                <p x-show="errors.time_slot" x-text="errors.time_slot?.[0]" class="mt-1 text-xs text-red-600"></p>
            @else
                @error('time_slot')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @endif
        </div>
    </div>

    <div>
        <label for="{{ $prefix }}note" class="block text-sm font-medium text-slate-700">{{ __('callback.form.note') }}</label>
        <textarea id="{{ $prefix }}note" name="note" rows="3" maxlength="500" class="{{ $input }}">{{ old('note') }}</textarea>
        @if ($modal)
            <p x-show="errors.note" x-text="errors.note?.[0]" class="mt-1 text-xs text-red-600"></p>
        @else
            @error('note')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @endif
    </div>

    @if ($modal)
        <p x-show="formError" x-text="formError" role="alert" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800 ring-1 ring-red-200"></p>
    @endif

    <p class="text-xs text-slate-500">
        {{ __('callback.form.privacy') }}
        <a href="{{ route('pages.privacy') }}" class="underline hover:text-brand-700">{{ __('callback.form.privacy_link') }}</a>
    </p>

    <button type="submit" @if ($modal) :disabled="submitting" @endif class="w-full rounded-lg bg-brand-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 active:scale-[0.98] disabled:cursor-wait disabled:opacity-70 sm:w-auto">
        @if ($modal)
            <span x-text="submitting ? @js(__('callback.form.sending')) : @js(__('callback.form.submit'))">{{ __('callback.form.submit') }}</span>
        @else
            {{ __('callback.form.submit') }}
        @endif
    </button>
</form>
