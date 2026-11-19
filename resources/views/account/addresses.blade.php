<x-account-layout :title="__('account.nav.addresses')" active="addresses">
    <h2 class="text-xl font-semibold text-brand-900">{{ __('account.addresses.title') }}</h2>

    @if ($addresses->isEmpty())
        <p class="mt-4 text-sm text-slate-500">{{ __('account.addresses.empty') }}</p>
    @else
        <ul class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach ($addresses as $address)
                <li class="rounded-xl p-4 ring-1 ring-slate-200">
                    <p class="font-semibold text-slate-900">{{ $address->label }}
                        @if ($address->is_default)<span class="ml-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">{{ __('account.addresses.default') }}</span>@endif
                    </p>
                    <address class="mt-1 text-sm not-italic text-slate-600">
                        {{ $address->contact_name }}<br>{{ $address->address }}<br>{{ $address->postal_code }} {{ $address->city }}<br>{{ $address->phone }}
                    </address>
                    <div class="mt-3 flex items-center gap-4 text-sm">
                        <a href="{{ route('account.addresses.edit', $address) }}" class="font-medium text-brand-700 hover:underline">{{ __('account.addresses.edit') }}</a>
                        <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm(@js(__('account.addresses.confirm_delete')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-red-600 hover:underline">{{ __('account.addresses.delete') }}</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <h3 class="mt-10 text-lg font-semibold text-brand-900">{{ __('account.addresses.add') }}</h3>
    <form method="POST" action="{{ route('account.addresses.store') }}" class="mt-4 space-y-4">
        @csrf
        <x-address-fields :cities="$cities" />
        <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ __('account.addresses.save') }}</button>
    </form>
</x-account-layout>
