<?php

namespace App\Entity;

use App\Repository\EpisodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Épisode de podcast. Format ~3-4 minutes, publié selon le calendrier éditorial.
 * Compatible avec les flux RSS iTunes / Apple Podcasts / Spotify.
 */
#[ORM\Entity(repositoryClass: EpisodeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_episode_published_at', columns: ['published_at'])]
#[UniqueEntity(fields: ['slug'])]
class Episode
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_PUBLISHED];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $number = null;

    #[ORM\Column(length: 8)]
    #[Assert\Regex('/^S\d+$/')]
    private string $season = 'S1';

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(length: 220, unique: true)]
    #[Assert\NotBlank]
    private ?string $slug = null;

    #[ORM\Column(length: 280)]
    #[Assert\NotBlank]
    private ?string $excerpt = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $transcript = null;

    /** Fichier audio : URL publique (upload manuel ou CDN). */
    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    #[Assert\Url]
    private ?string $audioUrl = null;

    /** Taille en octets (requis par le flux RSS iTunes). */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $audioSizeBytes = 0;

    /** Durée formatée iTunes : HH:MM:SS ou MM:SS. */
    #[ORM\Column(length: 16)]
    private string $duration = '00:03:30';

    #[ORM\Column(length: 80)]
    private string $audioMimeType = 'audio/mpeg';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImageUrl = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::STATUSES)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column]
    private bool $explicit = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** Type d'épisode iTunes : full | trailer | bonus */
    #[ORM\Column(length: 16)]
    private string $episodeType = 'full';

    /** Article associé (optionnel : un épisode peut accompagner un article du blog). */
    #[ORM\OneToOne(inversedBy: 'relatedEpisode', targetEntity: Article::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Article $article = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }

    public function getNumber(): ?int { return $this->number; }
    public function setNumber(int $v): static { $this->number = $v; return $this; }

    public function getSeason(): string { return $this->season; }
    public function setSeason(string $v): static { $this->season = $v; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $v): static { $this->title = $v; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $v): static { $this->slug = $v; return $this; }

    public function getExcerpt(): ?string { return $this->excerpt; }
    public function setExcerpt(string $v): static { $this->excerpt = $v; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $v): static { $this->description = $v; return $this; }

    public function getTranscript(): ?string { return $this->transcript; }
    public function setTranscript(?string $v): static { $this->transcript = $v; return $this; }

    public function getAudioUrl(): ?string { return $this->audioUrl; }
    public function setAudioUrl(string $v): static { $this->audioUrl = $v; return $this; }

    public function getAudioSizeBytes(): int { return $this->audioSizeBytes; }
    public function setAudioSizeBytes(int $v): static { $this->audioSizeBytes = $v; return $this; }

    public function getDuration(): string { return $this->duration; }
    public function setDuration(string $v): static { $this->duration = $v; return $this; }

    public function getAudioMimeType(): string { return $this->audioMimeType; }
    public function setAudioMimeType(string $v): static { $this->audioMimeType = $v; return $this; }

    public function getCoverImageUrl(): ?string { return $this->coverImageUrl; }
    public function setCoverImageUrl(?string $v): static { $this->coverImageUrl = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }

    public function isExplicit(): bool { return $this->explicit; }
    public function setExplicit(bool $v): static { $this->explicit = $v; return $this; }

    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(?\DateTimeImmutable $v): static { $this->publishedAt = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getEpisodeType(): string { return $this->episodeType; }
    public function setEpisodeType(string $v): static { $this->episodeType = $v; return $this; }

    public function getArticle(): ?Article { return $this->article; }
    public function setArticle(?Article $v): static { $this->article = $v; return $this; }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->publishedAt !== null
            && $this->publishedAt <= new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s E%02d · %s', $this->season, (int) $this->number, (string) $this->title);
    }
}
