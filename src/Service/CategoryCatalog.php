<?php

namespace App\Service;

/**
 * Canonical storefront categories for MVLLI.
 */
final class CategoryCatalog
{
    public const TOPS = 'Tops';
    public const BOTTOMS = 'Bottoms';
    public const ACCESSORIES = 'Accessories';
    public const BAGS = 'Bags';

    /** @return list<string> */
    public static function allowedNames(): array
    {
        return [
            self::TOPS,
            self::BOTTOMS,
            self::ACCESSORIES,
            self::BAGS,
        ];
    }

    public static function isAllowed(string $name): bool
    {
        return \in_array($name, self::allowedNames(), true);
    }

    /**
     * Map legacy category names to one of the four allowed categories.
     */
    public static function mapLegacyName(string $legacyName): string
    {
        $lower = strtolower($legacyName);

        if (str_contains($lower, 'bag')) {
            return self::BAGS;
        }

        if (str_contains($lower, 'accessor')) {
            return self::ACCESSORIES;
        }

        if (
            str_contains($lower, 'jean')
            || str_contains($lower, 'pant')
            || str_contains($lower, 'short')
            || str_contains($lower, 'bottom')
            || str_contains($lower, 'footwear')
            || str_contains($lower, 'shoe')
        ) {
            return self::BOTTOMS;
        }

        return self::TOPS;
    }
}
