<?php

namespace App\Controller;

use App\Form\ContactType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly RateLimiterFactory $contactFormLimiter,
        private readonly LoggerInterface $logger,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {}

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Honeypot : le champ "website" est invisible pour un humain.
            // Si rempli, c'est un bot — on simule un succès sans rien envoyer.
            $honeypot = $form->get('website')->getData();
            if (!empty($honeypot)) {
                $this->logger->info('Contact form: honeypot triggered', [
                    'ip'      => $request->getClientIp(),
                    'ua'      => $request->headers->get('User-Agent'),
                    'trapped' => is_string($honeypot) ? mb_substr($honeypot, 0, 120) : '(non-string)',
                ]);
                // Même message que le vrai succès : on ne donne aucun indice au bot.
                $this->addFlash('success', 'Message envoyé. Je réponds dans les 48h.');
                return $this->redirectToRoute('app_contact');
            }

            $limiter = $this->contactFormLimiter->create($request->getClientIp() ?? 'anon');
            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Trop de messages envoyés. Réessayez dans une heure.');
                return $this->render('pages/contact.html.twig', ['form' => $form]);
            }

            $data = $form->getData();

            $message = (new Email())
                ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
                ->to($this->fromEmail)
                ->replyTo($data['email'])
                ->subject('[Privaris contact] '.$data['subject'])
                ->text(sprintf(
                    "De : %s <%s>\nSujet : %s\n\n%s",
                    $data['name'], $data['email'], $data['subject'], $data['message'],
                ));

            try {
                $this->mailer->send($message);
                $this->addFlash('success', 'Message envoyé. Je réponds dans les 48h.');
                return $this->redirectToRoute('app_contact');
            } catch (\Throwable $e) {
                $this->logger->error('Contact form: mailer failed', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Erreur d\'envoi. Réessayez dans un instant ou écrivez-nous directement à '.$this->fromEmail);
            }
        }

        return $this->render('pages/contact.html.twig', ['form' => $form]);
    }
}
