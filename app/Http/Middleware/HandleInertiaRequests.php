<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Inertia powers the back office only; the storefront stays plain Blade.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'admin';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => fn (): array => [
                'name' => app(GeneralSettings::class)->site_name,
            ],
            'auth' => fn (): array => [
                'user' => $request->user()?->only(['id', 'name', 'email']),
            ],
            'flash' => fn (): array => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
