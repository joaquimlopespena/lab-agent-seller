<?php

namespace App\CustomerAuth;

use InvalidArgumentException;

final class CustomerAuthConfiguration
{
    public readonly int $magicLinkTtlMinutes;

    public readonly int $sessionTtlHours;

    public readonly string $cookieName;

    public readonly bool $cookieSecure;

    public readonly string $cookieSameSite;

    /**
     * @param  array{
     *     magic_link_ttl_minutes?: mixed,
     *     session_ttl_hours?: mixed,
     *     cookie?: array{name?: mixed, secure?: mixed, same_site?: mixed}
     * }  $configuration
     */
    public function __construct(array $configuration)
    {
        $this->magicLinkTtlMinutes = $this->positiveInteger(
            $configuration['magic_link_ttl_minutes'] ?? null,
            'customer_auth.magic_link_ttl_minutes',
        );
        $this->sessionTtlHours = $this->positiveInteger(
            $configuration['session_ttl_hours'] ?? null,
            'customer_auth.session_ttl_hours',
        );

        $cookie = $configuration['cookie'] ?? [];
        $cookieName = $cookie['name'] ?? null;
        $cookieSecure = $cookie['secure'] ?? null;
        $cookieSameSite = $cookie['same_site'] ?? null;

        if (! is_string($cookieName) || trim($cookieName) === '') {
            throw new InvalidArgumentException('customer_auth.cookie.name must be a non-empty string.');
        }

        if (! is_bool($cookieSecure)) {
            throw new InvalidArgumentException('customer_auth.cookie.secure must be a boolean.');
        }

        if (! in_array($cookieSameSite, ['lax', 'strict', 'none'], true)) {
            throw new InvalidArgumentException('customer_auth.cookie.same_site must be lax, strict, or none.');
        }

        $this->cookieName = $cookieName;
        $this->cookieSecure = $cookieSecure;
        $this->cookieSameSite = $cookieSameSite;
    }

    private function positiveInteger(mixed $value, string $key): int
    {
        $validated = filter_var($value, FILTER_VALIDATE_INT);

        if ($validated === false || $validated < 1) {
            throw new InvalidArgumentException("{$key} must be a positive integer.");
        }

        return $validated;
    }
}
