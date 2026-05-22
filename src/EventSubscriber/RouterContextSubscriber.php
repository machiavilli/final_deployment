<?php

namespace App\EventSubscriber;

use App\Service\AppUrlService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Forces absolute URL generation to use APP_URL (required behind Railway/proxies).
 */
final class RouterContextSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AppUrlService $appUrlService,
        private readonly RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->appUrlService->isConfigured()) {
            return;
        }

        $parts = parse_url($this->appUrlService->getBaseUrl());
        if ($parts === false) {
            return;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);

        $context = $this->router->getContext();
        $context->setScheme($scheme);
        $context->setHost($parts['host'] ?? 'localhost');

        if ($scheme === 'https') {
            $context->setHttpsPort($port);
        } else {
            $context->setHttpPort($port);
        }

        if (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/') {
            $context->setBaseUrl(rtrim($parts['path'], '/'));
        }
    }
}
