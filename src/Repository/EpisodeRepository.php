<?php

namespace App\Repository;

use App\Entity\Episode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Episode> */
class EpisodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Episode::class);
    }

    public function publishedQb(): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.publishedAt <= :now')
            ->setParameter('status', Episode::STATUS_PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.publishedAt', 'DESC');
    }

    /** @return Episode[] */
    public function findAllPublished(): array
    {
        return $this->publishedQb()->getQuery()->getResult();
    }

    /** @return Episode[] */
    public function findLatestPublished(int $limit = 6): array
    {
        return $this->publishedQb()->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findOneBySlugPublished(string $slug): ?Episode
    {
        return $this->publishedQb()
            ->andWhere('e.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()->getOneOrNullResult();
    }

    public function countPublished(): int
    {
        return (int) $this->publishedQb()
            ->select('COUNT(e.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findFirstPublishedAt(): ?\DateTimeInterface
    {
        $result = $this->publishedQb()
            ->select('MIN(e.publishedAt)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? new \DateTimeImmutable($result) : null;
    }
}
