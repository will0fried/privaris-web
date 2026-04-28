<?php

namespace App\Controller;

use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use App\Service\NewsletterService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/newsletter', name: 'app_newsletter_')]
class NewsletterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SubscriberRepository $subscribers,
        private readonly NewsletterService $newsletter,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
        private readonly RateLimiterFactory $newsletterSubscriptionLimiter,
        private readonly RequestStack $requestStack,
    ) {}

    #[Route('/subscribe', name: 'subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        $email = trim((string) $request->request->get('email', ''));
        $source = (string) $request->request->get('source', 'unknown');

        // Rate limiting
        $limiter = $this->newsletterSubscriptionLimiter->create($request->getClientIp() ?? 'anon');
        if (!$limiter->consume(1)->isAccepted()) {
            return new JsonResponse(['ok' => false, 'message' => 'Trop de tentatives. Réessayez dans quelques minutes.'], 429);
        }

        // Validation
        $violations = $this->validator->validate($email, [new NotBlank(), new Email()]);
        if (count($violations) > 0) {
            return new JsonResponse(['ok' => false, 'message' => 'Email invalide.'], 400);
        }

        // Idempotence : si déjà abonné, message approprié
        $existing = $this->subscribers->findOneByEmail($email);
        if ($existing) {
            if ($existing->getStatus() === Subscriber::STATUS_CONFIRMED) {
                return new JsonResponse(['ok' => true, 'message' => 'Vous êtes déjà inscrit. Merci !']);
            }
            if ($existing->getStatus() === Subscriber::STATUS_UNSUBSCRIBED) {
                $existing->setStatus(Subscriber::STATUS_PENDING);
                $existing->setConfirmationToken(bin2hex(random_bytes(24)));
                $this->em->flush();
                $this->newsletter->sendDoubleOptIn($existing);
                return new JsonResponse(['ok' => true, 'message' => 'Un email de confirmation vient de vous être envoyé.']);
            }
            // pending → renvoyer le mail de confirmation
            $this->newsletter->sendDoubleOptIn($existing);
            return new JsonResponse(['ok' => true, 'message' => 'Nous vous avons renvoyé l\'email de confirmation.']);
        }

        // Nouveau subscriber
        $sub = (new Subscriber())
            ->setEmail($email)
            ->setSource($source)
            ->setSignupIp($request->getClientIp())
            ->setConfirmationToken(bin2hex(random_bytes(24)))
            ->setStatus(Subscriber::STATUS_PENDING);

        $this->em->persist($sub);
        $this->em->flush();

        try {
            $this->newsletter->sendDoubleOptIn($sub);
            $this->newsletter->syncToProvider($sub);
        } catch (\Throwable $e) {
            $this->logger->error('Newsletter signup: provider sync failed', ['error' => $e->getMessage(), 'email' => $email]);
            // On ne casse pas l'UX si Brevo est down — la confirmation locale reste valable
        }

        return new JsonResponse(['ok' => true, 'message' => 'Merci ! Un email de confirmation vient de vous être envoyé.']);
    }

    #[Route('/confirm/{token}', name: 'confirm', requirements: ['token' => '[a-f0-9]{48}'])]
    public function confirm(string $token): Response
    {
        $sub = $this->subscribers->findOneByToken($token);
        if (!$sub) {
            throw $this->createNotFoundException();
        }

        if ($sub->getStatus() !== Subscriber::STATUS_CONFIRMED) {
            $sub->setStatus(Subscriber::STATUS_CONFIRMED)
                ->setConfirmedAt(new \DateTimeImmutable())
                ->setConfirmationToken(null);
            $this->em->flush();
        }

        return $this->render('newsletter/success.html.twig', ['subscriber' => $sub]);
    }

    #[Route('/unsubscribe/{token}', name: 'unsubscribe', requirements: ['token' => '[a-f0-9]{48}'])]
    public function unsubscribe(string $token): Response
    {
        $sub = $this->subscribers->findOneByToken($token);
        if ($sub && $sub->getStatus() !== Subscriber::STATUS_UNSUBSCRIBED) {
            $sub->setStatus(Subscriber::STATUS_UNSUBSCRIBED);
            $this->em->flush();
        }
        return $this->render('newsletter/unsubscribe.html.twig');
    }
}
