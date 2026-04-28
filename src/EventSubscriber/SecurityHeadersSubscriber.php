<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Security headers — appliqués au niveau PHP (défense en profondeur).
 *
 * Les mêmes headers sont posés par le .htaccess en prod (Apache/PlanetHoster),
 * mais le serveur PHP intégré (symfony serve, dev) ignore le .htaccess.
 * Ce subscriber garantit qu'on a les headers :
 *   - en dev (on teste dans des conditions réalistes)
 *   - en prod, même si Apache est mal configuré
 *
 * CSP volontairement assoupli en dev (web-profiler/wdt ont besoin d'inline).
 * En prod, on serre la vis.
 */
final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $kernelEnvironment,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // Ne pas polluer les réponses du profiler / wdt Symfony
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/_profiler') || str_starts_with($path, '/_wdt')) {
            return;
        }

        $headers = $response->headers;

        // Base — partout (dev + prod)
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=(), payment=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // HSTS : uniquement si HTTPS et en prod (sinon on bloque le dev local)
        if ($this->kernelEnvironment === 'prod' && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // CSP : la vraie se trouve dans .htaccess (plus restrictive).
        // On ne la réapplique pas ici pour éviter les conflits.
    }
}
