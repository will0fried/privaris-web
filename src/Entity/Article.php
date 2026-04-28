<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_article_published_at', columns: ['published_at'])]
#[ORM\Index(name: 'idx_article_status',       columns: ['status'])]
#[UniqueEntity(fields: ['slug'])]
class Article
{
    public const STATUS_DRAFT      = 'draft';
    public const STATUS_SCHEDULED  = 'scheduled';
    public const STATUS_PUBLISHED  = 'published';
    public const STATUS_ARCHIVED   = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    private ?string $title = null;

    #[ORM\Column(length: 220, unique: true)]
    #[Assert\NotBlank]
    private ?string $slug = null;

    #[ORM\Column(length: 280)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 280)]
    private ?string $excerpt = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImageAlt = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::STATUSES)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column]
    private bool $featured = false;

    #[ORM\Column]
    private bool $alert = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $seoTitle = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $seoDescription = null;

    #[ORM\Column]
    private int $readingMinutes = 3;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $author = null;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'articles')]
    #[ORM\JoinTable(name: 'article_tag')]
    private Collection $tags;

    #[ORM\OneToOne(targetEntity: Episode::class, mappedBy: 'article')]
    private ?Episode $relatedEpisode = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->tags = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $v): static { $this->title = $v; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $v): static { $this->slug = $v; return $this; }

    public function getExcerpt(): ?string { return $this->excerpt; }
    public function setExcerpt(string $v): static { $this->excerpt = $v; return $this; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(string $v): static { $this->content = $v; return $this; }

    public function getCoverImageUrl(): ?string { return $this->coverImageUrl; }
    public function setCoverImageUrl(?string $v): static { $this->coverImageUrl = $v; return $this; }

    public function getCoverImageAlt(): ?string { return $this->coverImageAlt; }
    public function setCoverImageAlt(?string $v): static { $this->coverImageAlt = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }

    public function isFeatured(): bool { return $this->featured; }
    public function setFeatured(bool $v): static { $this->featured = $v; return $this; }

    public function isAlert(): bool { return $this->alert; }
    public function setAlert(bool $v): static { $this->alert = $v; return $this; }

    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(?\DateTimeImmutable $v): static { $this->publishedAt = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getSeoTitle(): ?string { return $this->seoTitle; }
    public function setSeoTitle(?string $v): static { $this->seoTitle = $v; return $this; }

    public function getSeoDescription(): ?string { return $this->seoDescription; }
    public function setSeoDescription(?string $v): static { $this->seoDescription = $v; return $this; }

    public function getReadingMinutes(): int { return $this->readingMinutes; }
    public function setReadingMinutes(int $v): static { $this->readingMinutes = $v; return $this; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $v): static { $this->category = $v; return $this; }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $v): static { $this->author = $v; return $this; }

    /** @return Collection<int, Tag> */
    public function getTags(): Collection { return $this->tags; }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) $this->tags->add($tag);
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function getRelatedEpisode(): ?Episode { return $this->relatedEpisode; }
    public function setRelatedEpisode(?Episode $v): static { $this->relatedEpisode = $v; return $this; }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->publishedAt !== null
            && $this->publishedAt <= new \DateTimeImmutable();
    }

    public function __toString(): string { return (string) $this->title; }
}
