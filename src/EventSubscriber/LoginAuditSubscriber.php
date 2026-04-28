<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Enregistre les connexions dans le log d'audit sécurité.
 * Canal monolog dédié : "security_audit".
 */
class LoginAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $securityAuditLogger,
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $ip = $this->requestStack->getMainRequest()?->getClientIp() ?? 'unknown';

        $this->securityAuditLogger->info('login.success', [
            'user' => $user->getUserIdentifier(),
            'ip'   => $ip,
            'ua'   => $this->requestStack->getMainRequest()?->headers->get('User-Agent'),
        ]);

        if ($user instanceof User) {
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->em->flush();
        }
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $ip = $this->requestStack->getMainRequest()?->getClientIp() ?? 'unknown';
        $this->securityAuditLogger->warning('login.failure', [
            'user'   => $event->getRequest()->request->get('email'),
            'ip'     => $ip,
            'reason' => $event->getException()->getMessageKey(),
        ]);
    }
}
