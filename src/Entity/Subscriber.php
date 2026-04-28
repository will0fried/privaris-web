<?php

namespace App\Entity;

use App\Repository\SubscriberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubscriberRepository::class)]
#[UniqueEntity(fields: ['email'])]
class Subscriber
{
    public const STATUS_PENDING     = 'pending';       // double opt-in pas encore confirmé
    public const STATUS_CONFIRMED   = 'confirmed';     // abonné actif
    public const STATUS_UNSUBSCRIBED = 'unsubscribed'; // désinscrit
    public const STATUS_BOUNCED     = 'bounced';       // email invalide
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_UNSUBSCRIBED, self::STATUS_BOUNCED];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::STATUSES)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $signupIp = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $source = null; // d'où vient l'inscription : home, article, footer...

    /** ID de l'abonné côté Brevo (si sync) */
    #[ORM\Column(nullable: true)]
    private ?int $externalId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $v): static { $this->email = strtolower(trim($v)); return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $v): static { $this->firstName = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }

    public function getConfirmationToken(): ?string { return $this->confirmationToken; }
    public function setConfirmationToken(?string $v): static { $this->confirmationToken = $v; return $this; }

    public function getConfirmedAt(): ?\DateTimeImmutable { return $this->confirmedAt; }
    public function setConfirmedAt(?\DateTimeImmutable $v): static { $this->confirmedAt = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getSignupIp(): ?string { return $this->signupIp; }
    public function setSignupIp(?string $v): static { $this->signupIp = $v; return $this; }

    public function getSource(): ?string { return $this->source; }
    public function setSource(?string $v): static { $this->source = $v; return $this; }

    public function getExternalId(): ?int { return $this->externalId; }
    public function setExternalId(?int $v): static { $this->externalId = $v; return $this; }

    public function __toString(): string { return (string) $this->email; }
}
