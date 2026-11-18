<?php

namespace App\Helpers;

use App\Models\Utilities\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityHelper
{
    /**
     * Log a custom activity
     */
    public static function log(
        string $description,
        ?Model $subject = null,
        string $logName = 'default',
        array $properties = [],
        string $event = 'custom',
        ?Model $causer = null,
    ): ActivityLog {
        $request = self::currentRequest();
        $properties = array_merge(
            self::requestContextProperties($request),
            $properties
        );
        // Outside an HTTP request there is no signed-in user to attribute the event to.
        $causer ??= $request ? auth()->user() : null;

        return ActivityLog::create([
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'causer_type' => $causer ? get_class($causer) : null,
            'causer_id' => $causer?->getKey(),
            'properties' => $properties,
            'event' => $event,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Log authentication events
     */
    public static function logAuth(string $event, ?Model $user = null, array $properties = []): ActivityLog
    {
        $descriptions = [
            'login' => 'Signed in successfully',
            'logout' => 'Signed out',
            'login_failed' => 'Failed sign-in attempt',
            'password_reset' => 'Password reset',
            'email_verified' => 'Email verified',
            'login_2fa_app' => 'Signed in using an authenticator app',
            'login_2fa_email' => 'Signed in using an email code',
            'login_2fa_recovery_code' => 'Signed in using a recovery code',
            'login_google' => 'Signed in with Google',
        ];

        return self::log(
            $descriptions[$event] ?? $event,
            $user,
            'authentication',
            $properties,
            $event
        );
    }

    /**
     * The HTTP request being handled, or null for system work. Console commands, the scheduler and queue
     * workers always have a placeholder request bound (path "/", GET) that was never routed; treating it
     * as a request would record them as web traffic.
     */
    private static function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        return app()->runningInConsole() && $request->route() === null ? null : $request;
    }

    private static function resolveSource(?Request $request): string
    {
        if (! $request) {
            return 'system';
        }

        return $request->is('api/*') ? 'api' : 'web';
    }

    private static function requestContextProperties(?Request $request): array
    {
        if (! $request) {
            return [
                'source' => 'system',
            ];
        }

        $properties = [
            'source' => self::resolveSource($request),
            'method' => strtoupper((string) $request->method()),
            'route' => ltrim((string) $request->path(), '/'),
        ];

        $authenticatedUser = $request->user();
        $deviceName = $request->input('device_name')
            ?: $request->header('X-Device-Name')
            ?: $request->header('X-Device-Id')
            ?: ($authenticatedUser && method_exists($authenticatedUser, 'currentAccessToken')
                ? $authenticatedUser->currentAccessToken()?->name
                : null);

        if (! empty($deviceName)) {
            $properties['device_name'] = (string) $deviceName;
        }

        return array_filter($properties, fn ($value) => $value !== null && $value !== '');
    }
}
