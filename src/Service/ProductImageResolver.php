<?php

namespace App\Service;

/**
 * Resolves product image filenames to a public URL path that exists on disk.
 */
class ProductImageResolver
{
    private const IMAGE_DIRS = [
        'uploads/images/',
        'uploads/products/',
        'image/',
    ];

    public function __construct(
        private readonly string $projectDir,
    ) {}

    public function resolvePublicPath(?string $filename): ?string
    {
        if ($filename === null || trim($filename) === '') {
            return null;
        }

        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return $filename;
        }

        $publicDir = $this->projectDir . '/public';
        $clean = ltrim(str_replace('\\', '/', $filename), '/');

        if (str_contains($clean, 'uploads/') || str_starts_with($clean, 'image/')) {
            $full = $publicDir . '/' . $clean;
            if (is_file($full)) {
                return '/' . $clean;
            }
        }

        foreach (self::IMAGE_DIRS as $dir) {
            $full = $publicDir . '/' . $dir . $clean;
            if (is_file($full)) {
                return '/' . $dir . $clean;
            }
        }

        $base = pathinfo($clean, PATHINFO_FILENAME);
        $ext = pathinfo($clean, PATHINFO_EXTENSION);

        foreach (self::IMAGE_DIRS as $dir) {
            $globExt = $ext !== '' ? $ext : '*';
            $pattern = $publicDir . '/' . $dir . $base . '*.' . $globExt;
            $matches = glob($pattern) ?: [];
            if ($matches !== []) {
                $relative = str_replace('\\', '/', substr($matches[0], strlen($publicDir)));
                return $relative;
            }
        }

        return null;
    }
}
