<x-account-layout :title="__('account.addresses.edit')" active="addresses">
    <h2 class="text-xl font-semibold text-brand-900">{{ __('account.addresses.edit') }}</h2>
    <form method="POST" action="{{ route('account.addresses.update', $address) }}" class="mt-4 space-y-4">
        @csrf
        @method('PUT')
        <x-address-fields :address="$address" :cities="$cities" />
        <div class="flex items-center gap-4">
            <button type="submit" class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ __('account.addresses.save') }}</button>
            <a href="{{ route('account.addresses') }}" class="text-sm text-slate-600 hover:underline">←</a>
        </div>
    </form>
</x-account-layout>
