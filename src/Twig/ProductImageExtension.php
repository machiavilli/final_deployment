<?php

namespace App\Twig;

use App\Service\ProductImageResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ProductImageExtension extends AbstractExtension
{
    public function __construct(
        private readonly ProductImageResolver $imageResolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('product_image_url', $this->resolveUrl(...)),
        ];
    }

    public function resolveUrl(?string $filename): ?string
    {
        $path = $this->imageResolver->resolvePublicPath($filename);

        if ($path === null) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $path;
    }
}
