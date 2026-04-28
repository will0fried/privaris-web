<?php

namespace App\Service;

use App\Entity\Subscriber;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'inscription newsletter.
 *
 * - Envoie un email de double opt-in via Symfony Mailer (SMTP PlanetHoster)
 * - Synchronise l'abonné vers Brevo (ex-Sendinblue) via son API REST
 *
 * Brevo docs : https://developers.brevo.com/reference/createcontact
 */
class NewsletterService
{
    private const BREVO_API = 'https://api.brevo.com/v3';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urls,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly ?int $listId,
        private readonly bool $doubleOptIn,
        private readonly string $fromEmail = 'contact@privaris.fr',
        private readonly string $fromName = 'Privaris',
    ) {}

    public function sendDoubleOptIn(Subscriber $sub): void
    {
        if (!$this->doubleOptIn || !$sub->getConfirmationToken()) {
            return;
        }

        $confirmUrl = $this->urls->generate(
            'app_newsletter_confirm',
            ['token' => $sub->getConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($sub->getEmail())
            ->subject('Confirmez votre inscription à Privaris')
            ->text(sprintf(
                "Bonjour,\n\n".
                "Merci de vous être inscrit à la newsletter Privaris.\n".
                "Pour finaliser votre inscription, cliquez sur ce lien :\n\n%s\n\n".
                "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\n".
                "— L'équipe Privaris",
                $confirmUrl,
            ))
            ->html($this->renderHtml($confirmUrl));

        $this->mailer->send($email);
    }

    public function syncToProvider(Subscriber $sub): void
    {
        if (!$this->apiKey) {
            return; // Pas configuré — on skip silencieusement en dev
        }

        $payload = [
            'email'       => $sub->getEmail(),
            'attributes'  => array_filter([
                'FIRSTNAME' => $sub->getFirstName(),
                'SOURCE'    => $sub->getSource(),
            ]),
            'updateEnabled' => true,
        ];
        if ($this->listId) {
            $payload['listIds'] = [$this->listId];
        }

        try {
            $response = $this->httpClient->request('POST', self::BREVO_API.'/contacts', [
                'headers' => [
                    'api-key'      => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => $payload,
            ]);

            $status = $response->getStatusCode();
            if ($status >= 400) {
                $this->logger->warning('Brevo sync returned non-success', [
                    'status' => $status,
                    'body'   => $response->getContent(false),
                ]);
                return;
            }

            $data = $response->toArray(false);
            if (isset($data['id']) && is_int($data['id'])) {
                $sub->setExternalId($data['id']);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Brevo sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function renderHtml(string $confirmUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Confirmation</title></head>
<body style="font-family:-apple-system,Segoe UI,Inter,sans-serif;background:#fff;color:#0E1621;padding:32px">
  <div style="max-width:520px;margin:0 auto">
    <h1 style="color:#0A2540;font-size:22px;margin:0 0 24px">Confirmez votre inscription</h1>
    <p style="line-height:1.6">Merci de votre intérêt pour <strong>Privaris</strong>. Il ne reste qu'une étape pour finaliser votre abonnement.</p>
    <p style="margin:32px 0"><a href="{$confirmUrl}" style="display:inline-block;background:#0A2540;color:#fff;text-decoration:none;padding:12px 24px;border-radius:999px;font-weight:500">Confirmer mon email</a></p>
    <p style="color:#6B7685;font-size:13px;line-height:1.6">Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message — nous ne vous recontacterons pas.</p>
    <hr style="border:none;border-top:1px solid #EDEFF2;margin:32px 0">
    <p style="color:#6B7685;font-size:12px">Privaris — la cybersécurité claire, humaine et utile.</p>
  </div>
</body>
</html>
HTML;
    }
}
