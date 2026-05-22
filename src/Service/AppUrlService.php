<?php

namespace App\Service;

/**
 * Canonical public base URL for links in emails, OAuth redirects, and CLI.
 */
final class AppUrlService
{
    public function __construct(
        private readonly string $appUrl,
    ) {
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->appUrl, '/');
    }

    public function isConfigured(): bool
    {
        return $this->getBaseUrl() !== '';
    }
}
